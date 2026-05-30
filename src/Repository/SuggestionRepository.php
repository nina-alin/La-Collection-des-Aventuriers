<?php

namespace App\Repository;

use App\Entity\Enum\SuggestionStatus;
use App\Entity\Suggestion;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Suggestion>
 */
class SuggestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Suggestion::class);
    }

    public function findPendingCountByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.user = :user')
            ->andWhere('s.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', SuggestionStatus::PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByStatus(User $user, SuggestionStatus $status): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.user = :user')
            ->andWhere('s.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findRecentByUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.refusal', 'r')
            ->leftJoin('r.moderator', 'm')
            ->addSelect('r', 'm')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.submittedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
