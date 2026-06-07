<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CorrectionProposal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CorrectionProposal>
 */
class CorrectionProposalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CorrectionProposal::class);
    }

    /** @return CorrectionProposal[] */
    public function findPending(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.status = :status')
            ->setParameter('status', 'PENDING')
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countPending(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.status = :status')
            ->setParameter('status', 'PENDING')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPublishedByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.author = :user')
            ->andWhere('c.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'PUBLISHED')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countBatchPublished(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $rows = $this->createQueryBuilder('c')
            ->select('IDENTITY(c.author) as authorId, COUNT(c.id) as cnt')
            ->where('c.author IN (:users)')
            ->andWhere('c.status = :status')
            ->setParameter('users', $users)
            ->setParameter('status', 'PUBLISHED')
            ->groupBy('c.author')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['authorId']] = (int) $row['cnt'];
        }

        return $result;
    }
}
