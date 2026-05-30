<?php

namespace App\Repository;

use App\Entity\ContributorLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContributorLevel>
 */
class ContributorLevelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContributorLevel::class);
    }

    public function findRankForCount(int $validatedCount): ?ContributorLevel
    {
        return $this->createQueryBuilder('cl')
            ->where('cl.threshold <= :count')
            ->setParameter('count', $validatedCount)
            ->orderBy('cl.threshold', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findNextLevel(?ContributorLevel $current): ?ContributorLevel
    {
        if ($current === null) {
            return $this->findRankForCount(0);
        }

        return $this->createQueryBuilder('cl')
            ->where('cl.threshold > :threshold')
            ->setParameter('threshold', $current->getThreshold())
            ->orderBy('cl.threshold', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
