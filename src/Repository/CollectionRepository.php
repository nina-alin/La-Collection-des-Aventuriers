<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Collection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Review;

/**
 * @extends ServiceEntityRepository<Collection>
 */
class CollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Collection::class);
    }

    public function findForGlobalSearch(string $q, int $limit = 3): array
    {
        return $this->createQueryBuilder('gc')
            ->where('LOWER(gc.nom) LIKE :q')
            ->setParameter('q', '%' . mb_strtolower($q) . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findMostPopular(int $limit = 2): array
    {
        return $this->createQueryBuilder('gc')
            ->orderBy('SIZE(gc.books)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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

    /** @return array{min: ?int, max: ?int} */
    public function getPublicationYearRange(Collection $collection): array
    {
        $row = $this->getEntityManager()->createQueryBuilder()
            ->select('MIN(b.frenchPublicationYear) AS yearMin, MAX(b.frenchPublicationYear) AS yearMax')
            ->from(Book::class, 'b')
            ->where('b.collection = :collection')
            ->andWhere('b.frenchPublicationYear IS NOT NULL')
            ->setParameter('collection', $collection)
            ->getQuery()
            ->getSingleResult();

        return [
            'min' => $row['yearMin'] !== null ? (int) $row['yearMin'] : null,
            'max' => $row['yearMax'] !== null ? (int) $row['yearMax'] : null,
        ];
    }

    public function computeAverageRating(Collection $collection): ?float
    {
        $row = $this->getEntityManager()->createQueryBuilder()
            ->select('AVG(r.score) AS avgScore')
            ->from(Review::class, 'r')
            ->join('r.book', 'b')
            ->where('b.collection = :collection')
            ->andWhere('b.deletedAt IS NULL')
            ->setParameter('collection', $collection)
            ->getQuery()
            ->getSingleResult();

        return $row['avgScore'] !== null ? round((float) $row['avgScore'], 1) : null;
    }
}
