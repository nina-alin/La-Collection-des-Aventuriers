<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function countAll(): int
    {
        return $this->count([]);
    }

    public function findByTitleLike(string $q, int $limit = 10): array
    {
        return $this->createQueryBuilder('b')
            ->where('LOWER(b.title) LIKE LOWER(:q)')
            ->setParameter('q', '%' . $q . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOneByTitleCaseInsensitive(string $value, bool $useOriginalTitle = false): ?Book
    {
        $field = $useOriginalTitle ? 'b.originalTitle' : 'b.title';
        return $this->createQueryBuilder('b')
            ->where("LOWER($field) = LOWER(:value)")
            ->setParameter('value', $value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findBySlugWithRelations(string $slug): ?Book
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.contributions', 'contrib')->addSelect('contrib')
            ->leftJoin('contrib.contributor', 'contributor')->addSelect('contributor')
            ->leftJoin('b.editor', 'e')->addSelect('e')
            ->leftJoin('b.galleryImages', 'g')->addSelect('g')
            ->leftJoin('b.collection', 'c')->addSelect('c')
            ->where('b.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
