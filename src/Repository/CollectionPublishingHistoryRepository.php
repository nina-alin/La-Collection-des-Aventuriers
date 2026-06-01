<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Collection;
use App\Entity\CollectionPublishingHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CollectionPublishingHistory>
 */
class CollectionPublishingHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CollectionPublishingHistory::class);
    }

    /** @return CollectionPublishingHistory[] */
    public function findByCollection(Collection $collection): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.collection = :collection')
            ->setParameter('collection', $collection)
            ->orderBy('h.startYear', 'ASC')
            ->addOrderBy('h.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
