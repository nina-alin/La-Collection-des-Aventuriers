<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WorkEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkEntry>
 */
class WorkEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkEntry::class);
    }

    /** @return WorkEntry[] */
    public function findPending(): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.status = :status')
            ->setParameter('status', 'PENDING')
            ->orderBy('w.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
