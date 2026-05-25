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

    public function findBySlugWithRelations(string $slug): ?Book
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.authors', 'a')->addSelect('a')
            ->leftJoin('b.illustrators', 'i')->addSelect('i')
            ->leftJoin('b.translator', 't')->addSelect('t')
            ->leftJoin('b.editor', 'e')->addSelect('e')
            ->leftJoin('b.galleryImages', 'g')->addSelect('g')
            ->leftJoin('b.collection', 'c')->addSelect('c')
            ->where('b.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
