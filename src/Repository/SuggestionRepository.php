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

    public function countAllByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countRecentlyValidatedByUser(User $user, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.user = :user')
            ->andWhere('s.status = :status')
            ->andWhere('s.submittedAt >= :since')
            ->setParameter('user', $user)
            ->setParameter('status', SuggestionStatus::VALIDATED)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return Suggestion[] */
    public function findPending(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->addSelect('u')
            ->where('s.status = :status')
            ->setParameter('status', SuggestionStatus::PENDING)
            ->orderBy('s.submittedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countGlobalPending(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.status = :status')
            ->setParameter('status', SuggestionStatus::PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countBatchValidated(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $rows = $this->createQueryBuilder('s')
            ->select('IDENTITY(s.user) as userId, COUNT(s.id) as cnt')
            ->where('s.user IN (:users)')
            ->andWhere('s.status = :status')
            ->setParameter('users', $users)
            ->setParameter('status', SuggestionStatus::VALIDATED)
            ->groupBy('s.user')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['userId']] = (int) $row['cnt'];
        }

        return $result;
    }
}
