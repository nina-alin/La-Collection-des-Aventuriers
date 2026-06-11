<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Collection as CollectionEntity;
use App\Entity\User;
use App\Entity\UserBook;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserBook>
 */
class UserBookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBook::class);
    }

    public function findByUserAndBook(User $user, Book $book): ?UserBook
    {
        return $this->findOneBy(['user' => $user, 'book' => $book]);
    }

    public function countOwnedByUserForCollection(User $user, CollectionEntity $collection): int
    {
        return (int) $this->createQueryBuilder('ub')
            ->select('COUNT(ub.id)')
            ->join('ub.book', 'b')
            ->where('ub.user = :user')
            ->andWhere('b.collection = :collection')
            ->andWhere('ub.isOwned = true')
            ->setParameter('user', $user)
            ->setParameter('collection', $collection)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param int[] $bookIds
     * @return UserBook[]
     */
    public function findByUserAndBookIds(User $user, array $bookIds): array
    {
        if (empty($bookIds)) {
            return [];
        }

        return $this->createQueryBuilder('ub')
            ->where('ub.user = :user')
            ->andWhere('ub.book IN (:ids)')
            ->setParameter('user', $user)
            ->setParameter('ids', $bookIds)
            ->getQuery()
            ->getResult();
    }

    public function countOwnedByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('ub')
            ->select('COUNT(ub.id)')
            ->where('ub.user = :user')
            ->andWhere('ub.isOwned = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOwnedAddedSince(User $user, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('ub')
            ->select('COUNT(ub.id)')
            ->where('ub.user = :user')
            ->andWhere('ub.isOwned = true')
            ->andWhere('ub.createdAt >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countToReadByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('ub')
            ->select('COUNT(ub.id)')
            ->where('ub.user = :user')
            ->andWhere('ub.isToRead = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countToBuyByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('ub')
            ->select('COUNT(ub.id)')
            ->where('ub.user = :user')
            ->andWhere('ub.isToBuy = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countFavoritesByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('ub')
            ->select('COUNT(ub.id)')
            ->where('ub.user = :user')
            ->andWhere('ub.isFavorite = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return UserBook[]
     */
    public function findPaginatedByUserAndList(
        User $user,
        string $listFlag,
        int $page,
        int $perPage = 20,
        string $sort = 'recently_added',
    ): array {
        $qb = $this->createQueryBuilder('ub')
            ->join('ub.book', 'b')
            ->addSelect('b')
            ->where('ub.user = :user')
            ->andWhere(sprintf('ub.%s = true', $listFlag))
            ->setParameter('user', $user)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        match ($sort) {
            'title_asc' => $qb->orderBy('b.title', 'ASC'),
            'title_desc' => $qb->orderBy('b.title', 'DESC'),
            default => $qb->orderBy('ub.createdAt', 'DESC'),
        };

        return $qb->getQuery()->getResult();
    }

    public function countByUserAndList(User $user, string $listFlag): int
    {
        return (int) $this->createQueryBuilder('ub')
            ->select('COUNT(ub.id)')
            ->where('ub.user = :user')
            ->andWhere(sprintf('ub.%s = true', $listFlag))
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
