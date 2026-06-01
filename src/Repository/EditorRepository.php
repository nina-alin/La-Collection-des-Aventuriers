<?php

namespace App\Repository;

use App\Entity\Editor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Editor>
 */
class EditorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Editor::class);
    }

    public function findByNameSearch(string $q, int $limit = 20): array
    {
        if ($q === '') {
            return $this->createQueryBuilder('e')
                ->orderBy('e.name', 'ASC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        }

        return $this->createQueryBuilder('e')
            ->where('LOWER(e.name) LIKE LOWER(:q)')
            ->setParameter('q', '%' . $q . '%')
            ->orderBy('e.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findWithBookCount(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e', 'COUNT(b.id) AS bookCount')
            ->leftJoin('e.books', 'b')
            ->groupBy('e.id')
            ->orderBy('bookCount', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
