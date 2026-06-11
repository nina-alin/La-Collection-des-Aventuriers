<?php

namespace App\Repository;

use App\Dto\ReviewStats;
use App\Entity\Book;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /** @return array{count: int, average: float|null} */
    public function getStatsByUser(User $user): array
    {
        $result = $this->createQueryBuilder('r')
            ->select('COUNT(r.id) as cnt, AVG(r.score) as avg_score')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleResult();

        $count = (int) $result['cnt'];
        return [
            'count' => $count,
            'average' => $count > 0 ? round((float) $result['avg_score'], 1) : null,
        ];
    }

    public function findByUserAndBook(User $user, Book $book): ?Review
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.book = :book')
            ->setParameter('user', $user)
            ->setParameter('book', $book)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getStatsForBook(Book $book): ReviewStats
    {
        $distributionResult = $this->createQueryBuilder('r')
            ->select('r.score, COUNT(r) as cnt')
            ->where('r.book = :book')
            ->setParameter('book', $book)
            ->groupBy('r.score')
            ->getQuery()
            ->getResult();

        $totalCount = 0;
        $distribution = array_fill(0, 10, 0);
        foreach ($distributionResult as $row) {
            $idx = (int) $row['score'] - 1;
            $distribution[$idx] = (int) $row['cnt'];
            $totalCount += (int) $row['cnt'];
        }

        if ($totalCount === 0) {
            return new ReviewStats(0.0, 0, $distribution, array_fill(0, 10, 0.0), []);
        }

        $averageResult = $this->createQueryBuilder('r')
            ->select('AVG(r.score) as avg_score')
            ->where('r.book = :book')
            ->setParameter('book', $book)
            ->getQuery()
            ->getSingleScalarResult();

        $averageScore = round((float) $averageResult, 1, PHP_ROUND_HALF_UP);

        $maxCount = max($distribution);
        $histogramHeights = array_map(
            fn (int $count) => $maxCount > 0 ? round($count / $maxCount * 100.0, 2) : 0.0,
            $distribution
        );

        $lastEvaluatorsResult = $this->createQueryBuilder('r')
            ->select('r')
            ->where('r.book = :book')
            ->setParameter('book', $book)
            ->orderBy('r.updatedAt', 'DESC')
            ->setMaxResults(4)
            ->getQuery()
            ->getResult();

        $lastEvaluators = array_map(fn (Review $r) => $r->getUser(), $lastEvaluatorsResult);

        return new ReviewStats($averageScore, $totalCount, $distribution, $histogramHeights, $lastEvaluators);
    }

    public function findPaginatedByBook(Book $book, string $filter, int $page, int $perPage = 10): Paginator
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.book = :book')
            ->setParameter('book', $book)
            ->orderBy('r.updatedAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        if ($filter === 'avec_commentaire') {
            $qb->andWhere('r.comment IS NOT NULL');
        }

        return new Paginator($qb, fetchJoinCollection: false);
    }
}
