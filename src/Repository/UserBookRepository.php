<?php

namespace App\Repository;

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
