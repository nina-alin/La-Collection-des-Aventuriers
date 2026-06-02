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
}
