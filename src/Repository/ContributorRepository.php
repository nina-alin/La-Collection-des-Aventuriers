<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\Enum\ContributionRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @extends ServiceEntityRepository<Contributor>
 */
class ContributorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly SluggerInterface $slugger)
    {
        parent::__construct($registry, Contributor::class);
    }

    public function countAll(): int
    {
        return $this->count([]);
    }

    public function findForGlobalSearch(string $q, int $limit = 3): array
    {
        return $this->createQueryBuilder('c')
            ->where('LOWER(c.firstName) LIKE :q OR LOWER(c.lastName) LIKE :q OR LOWER(c.pseudo) LIKE :q')
            ->setParameter('q', '%' . mb_strtolower($q) . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findMostPopular(int $limit = 2): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('SIZE(c.contributions)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findBySlugAndRole(string $slug, ContributionRole $role): ?Contributor
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT c, contrib, b
                 FROM App\Entity\Contributor c
                 INNER JOIN c.contributions contrib
                 INNER JOIN contrib.book b
                 WHERE c.slug = :slug
                   AND contrib.role = :role
                 ORDER BY CASE WHEN b.frenchPublicationYear IS NULL THEN 9999 ELSE b.frenchPublicationYear END ASC,
                          b.title ASC'
            )
            ->setParameter('slug', $slug)
            ->setParameter('role', $role->value)
            ->getOneOrNullResult();
    }

    /**
     * @return array{
     *   contributor: Contributor,
     *   filteredContributions: list<\App\Entity\Contribution>,
     *   sagaGroups: list<array{slug: string, name: string, count: int}>,
     *   totalCount: int
     * }|null
     */
    public function findContributionsBySlug(
        string $slug,
        ?string $sagaFilter,
        string $sortOrder = 'chrono'
    ): ?array {
        $contributor = $this->getEntityManager()
            ->createQuery(
                'SELECT c, contrib, b, e
                 FROM App\Entity\Contributor c
                 INNER JOIN c.contributions contrib
                 INNER JOIN contrib.book b
                 INNER JOIN b.editor e
                 WHERE c.slug = :slug
                   AND contrib.role = :role'
            )
            ->setParameter('slug', $slug)
            ->setParameter('role', ContributionRole::Author->value)
            ->getOneOrNullResult();

        if ($contributor === null) {
            return null;
        }

        $allContributions = $contributor->getContributions()->toArray();

        // Build sagaGroups from full unfiltered list
        $sagaCounts = [];
        foreach ($allContributions as $contrib) {
            $sagaName = $contrib->getBook()->getSaga();
            if ($sagaName !== null) {
                $sagaCounts[$sagaName] = ($sagaCounts[$sagaName] ?? 0) + 1;
            }
        }

        $sagaGroups = [];
        foreach ($sagaCounts as $name => $count) {
            $sagaGroups[] = ['slug' => $this->slugify($name), 'name' => $name, 'count' => $count];
        }

        // Apply saga filter; unknown saga falls back to all
        $filteredContributions = $allContributions;
        if ($sagaFilter !== null) {
            $filtered = array_values(array_filter(
                $allContributions,
                fn ($contrib) => $this->slugify((string) $contrib->getBook()->getSaga()) === $sagaFilter
            ));
            if (!empty($filtered)) {
                $filteredContributions = $filtered;
            }
        }

        usort($filteredContributions, function ($a, $b) use ($sortOrder): int {
            if ($sortOrder === 'alpha') {
                return strcmp($a->getBook()->getTitle(), $b->getBook()->getTitle());
            }
            $yearA = $a->getBook()->getFrenchPublicationYear() ?? PHP_INT_MAX;
            $yearB = $b->getBook()->getFrenchPublicationYear() ?? PHP_INT_MAX;
            if ($yearA !== $yearB) {
                return $yearA <=> $yearB;
            }
            return strcmp($a->getBook()->getTitle(), $b->getBook()->getTitle());
        });

        return [
            'contributor'          => $contributor,
            'filteredContributions' => $filteredContributions,
            'sagaGroups'           => $sagaGroups,
            'totalCount'           => count($allContributions),
        ];
    }

    private function slugify(string $text): string
    {
        return $this->slugger->slug($text)->lower()->toString();
    }
}
