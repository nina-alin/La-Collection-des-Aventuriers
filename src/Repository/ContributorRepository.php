<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\ContributorFilterState;
use App\Entity\Contribution;
use App\Entity\Contributor;
use App\Entity\Enum\BookStatus;
use App\Entity\Enum\ContributionRole;
use App\Entity\User;
use App\Entity\UserFollowedContributor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

    public function findPaginatedFiltered(ContributorFilterState $state, ?User $user = null): Paginator
    {
        $perPage = 12;
        $offset  = ($state->page - 1) * $perPage;

        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('c')
            ->from(Contributor::class, 'c')
            ->leftJoin('c.contributions', 'contrib')
            ->leftJoin('contrib.book', 'b', 'WITH', 'b.status = :published')
            ->leftJoin('b.collection', 'col')
            ->groupBy('c.id')
            ->setParameter('published', BookStatus::PUBLISHED);

        $this->applyFilters($qb, $state, $user);
        $this->applyOrder($qb, $state->sort);

        $qb->setFirstResult($offset)->setMaxResults($perPage);

        return new Paginator($qb->getQuery(), fetchJoinCollection: false);
    }

    /** @return string[] */
    public function findAvailableLetters(ContributorFilterState $state, ?User $user = null): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT UPPER(SUBSTRING(c.lastName, 1, 1)) AS letter')
            ->from(Contributor::class, 'c')
            ->leftJoin('c.contributions', 'contrib')
            ->leftJoin('contrib.book', 'b', 'WITH', 'b.status = :published')
            ->leftJoin('b.collection', 'col')
            ->setParameter('published', BookStatus::PUBLISHED)
            ->orderBy('letter', 'ASC');

        $letterState = new ContributorFilterState(
            role: $state->role,
            collectionIds: $state->collectionIds,
            periodMin: $state->periodMin,
            periodMax: $state->periodMax,
            nationality: $state->nationality,
            bookCountRange: $state->bookCountRange,
            onlyFollowed: $state->onlyFollowed,
            sort: $state->sort,
        );

        $this->applyFilters($qb, $letterState, $user);

        $rows = $qb->getQuery()->getScalarResult();

        return array_values(array_filter(array_column($rows, 'letter')));
    }

    public function findCardDataBatch(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'c.id AS cid',
                'COUNT(DISTINCT b.id) AS bookCount',
                'AVG(r.score) AS avgScore',
                'contrib.role AS role'
            )
            ->from(Contributor::class, 'c')
            ->leftJoin('c.contributions', 'contrib')
            ->leftJoin('contrib.book', 'b', 'WITH', 'b.status = :published')
            ->leftJoin('b.reviews', 'r')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->setParameter('published', BookStatus::PUBLISHED)
            ->groupBy('c.id, contrib.role')
            ->getQuery()
            ->getScalarResult();

        $map = [];
        foreach ($rows as $row) {
            $cid = (string) $row['cid'];
            if (!isset($map[$cid])) {
                $map[$cid] = ['bookCount' => 0, 'avgScore' => null, 'roles' => []];
            }
            $map[$cid]['bookCount'] = max($map[$cid]['bookCount'], (int) $row['bookCount']);
            if ($row['avgScore'] !== null && $map[$cid]['avgScore'] === null) {
                $map[$cid]['avgScore'] = round((float) $row['avgScore'], 1);
            } elseif ($row['avgScore'] !== null) {
                $map[$cid]['avgScore'] = round(
                    ((float) $map[$cid]['avgScore'] + (float) $row['avgScore']) / 2,
                    1
                );
            }
            if ($row['role'] !== null && !in_array($row['role'], $map[$cid]['roles'], true)) {
                $map[$cid]['roles'][] = $row['role'];
            }
        }

        return $map;
    }

    public function findTopCollectionsBatch(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'IDENTITY(contrib.contributor) AS cid',
                'col.id AS colId',
                'col.nom AS colNom',
                'COUNT(DISTINCT b.id) AS cnt'
            )
            ->from(Contributor::class, 'c')
            ->innerJoin('c.contributions', 'contrib')
            ->innerJoin('contrib.book', 'b', 'WITH', 'b.status = :published')
            ->innerJoin('b.collection', 'col')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->setParameter('published', BookStatus::PUBLISHED)
            ->groupBy('contrib.contributor, col.id, col.nom')
            ->orderBy('cnt', 'DESC')
            ->addOrderBy('col.id', 'DESC')
            ->getQuery()
            ->getScalarResult();

        $grouped = [];
        foreach ($rows as $row) {
            $cid = (string) $row['cid'];
            if (!isset($grouped[$cid])) {
                $grouped[$cid] = [];
            }
            if (count($grouped[$cid]) < 2) {
                $grouped[$cid][] = ['id' => $row['colId'], 'nom' => $row['colNom']];
            }
        }

        return $grouped;
    }

    /** @return array{auteur: int, traducteur: int, illustrateur: int, tous: int} */
    public function findRoleCounts(): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('contrib.role AS role', 'COUNT(DISTINCT c.id) AS cnt')
            ->from(Contributor::class, 'c')
            ->leftJoin('c.contributions', 'contrib')
            ->leftJoin('contrib.book', 'b', 'WITH', 'b.status = :published')
            ->setParameter('published', BookStatus::PUBLISHED)
            ->groupBy('contrib.role')
            ->getQuery()
            ->getScalarResult();

        $roleMap = [
            ContributionRole::Author->value      => 'auteur',
            ContributionRole::Illustrator->value => 'illustrateur',
            ContributionRole::Traductor->value   => 'traducteur',
        ];

        $counts = ['auteur' => 0, 'traducteur' => 0, 'illustrateur' => 0];
        foreach ($rows as $row) {
            $key = $roleMap[$row['role']] ?? null;
            if ($key !== null) {
                $counts[$key] = (int) $row['cnt'];
            }
        }

        $tous = (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(DISTINCT c.id)')
            ->from(Contributor::class, 'c')
            ->getQuery()
            ->getSingleScalarResult();

        $counts['tous'] = $tous;

        return $counts;
    }

    public function findForAutocomplete(string $q, int $maxPerRole = 5): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'c.slug AS slug',
                'c.firstName AS firstName',
                'c.lastName AS lastName',
                'c.portraitImage AS portraitImage',
                'contrib.role AS role',
                'COUNT(DISTINCT b.id) AS bookCount',
                'col.nom AS mainCollection',
                'AVG(r.score) AS averageScore'
            )
            ->from(Contributor::class, 'c')
            ->leftJoin('c.contributions', 'contrib')
            ->leftJoin('contrib.book', 'b', 'WITH', 'b.status = :published')
            ->leftJoin('b.collection', 'col')
            ->leftJoin('b.reviews', 'r')
            ->where(
                'LOWER(c.firstName) LIKE :q OR LOWER(c.lastName) LIKE :q'
            )
            ->setParameter('q', '%' . mb_strtolower($q) . '%')
            ->setParameter('published', BookStatus::PUBLISHED)
            ->groupBy('c.id, contrib.role, col.id')
            ->orderBy('bookCount', 'DESC')
            ->setMaxResults($maxPerRole * 3 * 6)
            ->getQuery()
            ->getScalarResult();

        $roleMap = [
            ContributionRole::Author->value      => 'auteur',
            ContributionRole::Illustrator->value => 'illustrateur',
            ContributionRole::Traductor->value   => 'traducteur',
        ];

        $groups = ['auteur' => [], 'traducteur' => [], 'illustrateur' => []];
        $seen   = [];

        foreach ($qb as $row) {
            $slug    = $row['slug'];
            $roleKey = $roleMap[$row['role']] ?? null;
            if ($roleKey === null) {
                continue;
            }
            $seenKey = $slug . '.' . $roleKey;
            if (isset($seen[$seenKey])) {
                continue;
            }
            if (count($groups[$roleKey]) >= $maxPerRole) {
                continue;
            }
            $seen[$seenKey] = true;
            $groups[$roleKey][] = [
                'slug'          => $slug,
                'firstName'     => $row['firstName'],
                'lastName'      => $row['lastName'],
                'portraitImage' => $row['portraitImage'],
                'role'          => $roleKey,
                'bookCount'     => (int) $row['bookCount'],
                'mainCollection' => $row['mainCollection'],
                'averageScore'  => $row['averageScore'] !== null ? round((float) $row['averageScore'], 1) : null,
            ];
        }

        return $groups;
    }

    private function applyFilters(\Doctrine\ORM\QueryBuilder $qb, ContributorFilterState $state, ?User $user = null): void
    {
        if ($state->onlyFollowed && $user !== null) {
            $qb->innerJoin(
                UserFollowedContributor::class, 'ufc',
                'WITH', 'ufc.contributor = c AND ufc.user = :followUser'
            )->setParameter('followUser', $user);
        }

        $roleMap = [
            'auteur'       => ContributionRole::Author->value,
            'traducteur'   => ContributionRole::Traductor->value,
            'illustrateur' => ContributionRole::Illustrator->value,
        ];

        if ($state->role !== 'tous' && isset($roleMap[$state->role])) {
            $qb->andWhere('contrib.role = :role')
               ->setParameter('role', $roleMap[$state->role]);
        }

        if ($state->letter !== null) {
            $qb->andWhere('UPPER(SUBSTRING(c.lastName, 1, 1)) = :letter')
               ->setParameter('letter', $state->letter);
        }

        if (!empty($state->collectionIds)) {
            $qb->andWhere('col.id IN (:collectionIds)')
               ->setParameter('collectionIds', $state->collectionIds);
        }

        if ($state->periodMin !== null) {
            $qb->andWhere('YEAR(c.birthDate) >= :periodMin OR YEAR(c.deathDate) >= :periodMin')
               ->setParameter('periodMin', $state->periodMin);
        }

        if ($state->periodMax !== null) {
            $qb->andWhere('YEAR(c.birthDate) <= :periodMax')
               ->setParameter('periodMax', $state->periodMax);
        }

        if ($state->nationality !== null) {
            $qb->andWhere('LOWER(c.nationality) = :nationality')
               ->setParameter('nationality', mb_strtolower($state->nationality));
        }

        if ($state->bookCountRange !== null) {
            [$min, $max] = match ($state->bookCountRange) {
                '1-5'  => [1, 5],
                '6-15' => [6, 15],
                '16-30' => [16, 30],
                '30+'  => [30, PHP_INT_MAX],
                default => [0, PHP_INT_MAX],
            };
            $subQb = $this->getEntityManager()->createQueryBuilder()
                ->select('IDENTITY(sc.contributor)')
                ->from(Contribution::class, 'sc')
                ->innerJoin('sc.book', 'sb', 'WITH', 'sb.status = :published2')
                ->groupBy('sc.contributor')
                ->having('COUNT(DISTINCT sb.id) >= :bcMin');
            $qb->setParameter('published2', BookStatus::PUBLISHED)
               ->setParameter('bcMin', $min);
            if ($max < PHP_INT_MAX) {
                $subQb->andHaving('COUNT(DISTINCT sb.id) <= :bcMax');
                $qb->setParameter('bcMax', $max);
            }
            $qb->andWhere('c.id IN (' . $subQb->getDQL() . ')');
        }
    }

    private function applyOrder(\Doctrine\ORM\QueryBuilder $qb, string $sort): void
    {
        match ($sort) {
            'ouvrages' => $qb->addSelect('COUNT(DISTINCT b.id) AS HIDDEN bookCount')
                             ->orderBy('bookCount', 'DESC')
                             ->addOrderBy('c.lastName', 'ASC'),
            'note'     => $qb->addSelect('AVG(r.score) AS HIDDEN avgScore')
                             ->leftJoin('b.reviews', 'r')
                             ->orderBy('avgScore', 'DESC')
                             ->addOrderBy('c.lastName', 'ASC'),
            default    => $qb->orderBy('c.lastName', 'ASC'),
        };
    }

    public function countFiltered(ContributorFilterState $state, ?User $user = null): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(DISTINCT c.id)')
            ->from(Contributor::class, 'c')
            ->leftJoin('c.contributions', 'contrib')
            ->leftJoin('contrib.book', 'b', 'WITH', 'b.status = :published')
            ->leftJoin('b.collection', 'col')
            ->setParameter('published', BookStatus::PUBLISHED);

        $this->applyFilters($qb, $state, $user);

        return (int) $qb->getQuery()->getSingleScalarResult();
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
