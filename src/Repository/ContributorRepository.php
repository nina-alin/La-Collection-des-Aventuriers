<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\Enum\BookStatus;
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

    public function findForGlobalSearchWithStats(string $q, int $limit = 5): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('c AS contributor, COUNT(DISTINCT b.id) AS bookCount, MIN(b.frenchPublicationYear) AS minYear, MAX(b.frenchPublicationYear) AS maxYear')
            ->from(Contributor::class, 'c')
            ->leftJoin('c.contributions', 'contrib')
            ->leftJoin('contrib.book', 'b', 'WITH', 'b.status = :published')
            ->where('LOWER(c.firstName) LIKE :q OR LOWER(c.lastName) LIKE :q OR LOWER(c.pseudo) LIKE :q')
            ->setParameter('q', '%' . mb_strtolower($q) . '%')
            ->setParameter('published', BookStatus::PUBLISHED)
            ->groupBy('c.id')
            ->orderBy('bookCount', 'DESC')
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

    /**
     * @return array<array{contributor: Contributor, role: string, bookCount: int}>
     */
    public function findByNameSearchWithRoleAndCount(string $q, int $limit = 20): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('c AS contributor, contrib.role AS role, COUNT(DISTINCT b.id) AS bookCount')
            ->from(Contributor::class, 'c')
            ->innerJoin('c.contributions', 'contrib')
            ->innerJoin('contrib.book', 'b', 'WITH', 'b.status = :published')
            ->where('contrib.role IN (:roles)')
            ->setParameter('published', BookStatus::PUBLISHED)
            ->setParameter('roles', [ContributionRole::Author->value, ContributionRole::Illustrator->value])
            ->groupBy('c.id, contrib.role')
            ->orderBy('bookCount', 'DESC')
            ->addOrderBy('c.lastName', 'ASC');

        if ($q !== '') {
            $qb->andWhere('LOWER(c.firstName) LIKE :q OR LOWER(c.lastName) LIKE :q OR LOWER(c.pseudo) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $rows = $qb->setMaxResults($limit * 2)->getQuery()->getResult();

        $merged = [];
        foreach ($rows as $row) {
            $contributor = $row['contributor'];
            $id          = $contributor->getId()->toString();
            $role        = $row['role'];
            $bookCount   = (int) $row['bookCount'];

            if (!isset($merged[$id])) {
                $merged[$id] = ['contributor' => $contributor, 'role' => $role, 'bookCount' => $bookCount];
            } elseif ($role === ContributionRole::Author->value) {
                $merged[$id]['role']      = $role;
                $merged[$id]['bookCount'] = max($bookCount, $merged[$id]['bookCount']);
            }
        }

        uasort($merged, fn($a, $b) => $b['bookCount'] <=> $a['bookCount']);

        return array_values(array_slice($merged, 0, $limit));
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

    public function countWithPublishedBooks(): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(DISTINCT c.id)')
            ->from(Contributor::class, 'c')
            ->innerJoin('c.contributions', 'contrib')
            ->innerJoin('contrib.book', 'b', 'WITH', 'b.status = :published')
            ->setParameter('published', BookStatus::PUBLISHED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function slugify(string $text): string
    {
        return $this->slugger->slug($text)->lower()->toString();
    }
}
