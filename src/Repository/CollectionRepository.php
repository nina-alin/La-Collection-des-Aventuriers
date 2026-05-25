<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Collection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Collection>
 */
class CollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Collection::class);
    }

    public function findBySlug(string $slug): ?Collection
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function paginateBooksForCollection(Collection $collection, int $page, int $perPage = 20): Paginator
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('b', 'CASE WHEN b.volumeNumber IS NULL THEN 1 ELSE 0 END AS HIDDEN volume_sort')
            ->from(Book::class, 'b')
            ->where('b.collection = :collection')
            ->setParameter('collection', $collection)
            ->orderBy('volume_sort', 'ASC')
            ->addOrderBy('b.volumeNumber', 'ASC')
            ->addOrderBy('b.title', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        return new Paginator($qb, fetchJoinCollection: false);
    }
}
