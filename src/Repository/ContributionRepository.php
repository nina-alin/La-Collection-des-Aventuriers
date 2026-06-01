<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Collection;
use App\Entity\Contribution;
use App\Entity\Contributor;
use App\Entity\Enum\ContributionRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contribution>
 */
class ContributionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contribution::class);
    }

    /**
     * Returns one row per (Contributor, ContributionRole) pair across all tomes
     * of the collection, sorted by count DESC.
     *
     * @return array<int, array{contributor: Contributor, role: ContributionRole, count: int}>
     */
    public function findRecurringByCollection(Collection $collection): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(co.contributor) AS contributorId, co.role, COUNT(co.id) AS tomeCount')
            ->from(Contribution::class, 'co')
            ->join('co.book', 'b')
            ->where('b.collection = :collection')
            ->andWhere('co.deletedAt IS NULL')
            ->andWhere('b.deletedAt IS NULL')
            ->groupBy('co.contributor, co.role')
            ->orderBy('tomeCount', 'DESC')
            ->setParameter('collection', $collection)
            ->getQuery()
            ->getResult();

        if (empty($rows)) {
            return [];
        }

        $contributorIds = array_unique(array_column($rows, 'contributorId'));
        $contributors = $this->getEntityManager()->createQueryBuilder()
            ->select('c')
            ->from(Contributor::class, 'c')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $contributorIds)
            ->getQuery()
            ->getResult();

        $contributorMap = [];
        foreach ($contributors as $contributor) {
            $contributorMap[$contributor->getId()->toRfc4122()] = $contributor;
        }

        $result = [];
        foreach ($rows as $row) {
            $contributor = $contributorMap[$row['contributorId']] ?? null;
            if ($contributor === null) {
                continue;
            }
            $result[] = [
                'contributor' => $contributor,
                'role' => $row['role'],
                'count' => (int) $row['tomeCount'],
            ];
        }

        return $result;
    }
}
