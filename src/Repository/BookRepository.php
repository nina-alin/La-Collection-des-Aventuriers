<?php

namespace App\Repository;

use App\Dto\ActiveFilterState;
use App\Entity\Book;
use App\Entity\Enum\BookStatus;
use Symfony\Component\Uid\Uuid;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

    /** @param int[] $bookIds @return array<int,float> */
    public function findAverageRatingsByIds(array $bookIds): array
    {
        if (empty($bookIds)) {
            return [];
        }

        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(r.book) AS bookId, AVG(r.score) AS avg')
            ->from(Review::class, 'r')
            ->where('r.book IN (:ids)')
            ->setParameter('ids', $bookIds)
            ->groupBy('r.book')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['bookId']] = round((float) $row['avg'], 1);
        }

        return $map;
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

    public function findForGlobalSearch(string $q, int $limit = 5): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.contributions', 'contrib')->addSelect('contrib')
            ->leftJoin('contrib.contributor', 'contributor')->addSelect('contributor')
            ->leftJoin('b.editor', 'e')->addSelect('e')
            ->where('LOWER(b.title) LIKE :q')
            ->andWhere('b.status = :published')
            ->setParameter('q', '%' . mb_strtolower($q) . '%')
            ->setParameter('published', BookStatus::PUBLISHED)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findMostPopular(int $limit = 4): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.contributions', 'contrib')->addSelect('contrib')
            ->leftJoin('contrib.contributor', 'contributor')->addSelect('contributor')
            ->leftJoin('b.editor', 'e')->addSelect('e')
            ->where('b.status = :published')
            ->setParameter('published', BookStatus::PUBLISHED)
            ->orderBy('SIZE(b.reviews)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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

    /** @return array{min: int, max: int} */
    public function findParagraphBounds(): array
    {
        $result = $this->createQueryBuilder('b')
            ->select('MIN(b.paragraphs) AS minP, MAX(b.paragraphs) AS maxP')
            ->where('b.status = :published')
            ->andWhere('b.paragraphs IS NOT NULL')
            ->setParameter('published', BookStatus::PUBLISHED)
            ->getQuery()
            ->getSingleResult();

        return [
            'min' => (int) ($result['minP'] ?? 0),
            'max' => (int) ($result['maxP'] ?? 999),
        ];
    }

    public function countFiltered(ActiveFilterState $state, ?User $user = null): int
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(DISTINCT b.id)')
            ->leftJoin('b.contributions', 'contrib')
            ->leftJoin('contrib.contributor', 'contributor')
            ->leftJoin('b.editor', 'e')
            ->where('b.status = :published')
            ->setParameter('published', BookStatus::PUBLISHED);

        $this->applyFilterWhere($qb, $state, $user);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findFilteredPaginated(ActiveFilterState $state, ?User $user = null, int $perPage = 24): Paginator
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.contributions', 'contrib')->addSelect('contrib')
            ->leftJoin('contrib.contributor', 'contributor')->addSelect('contributor')
            ->leftJoin('b.editor', 'e')->addSelect('e')
            ->leftJoin('b.collection', 'coll')->addSelect('coll')
            ->where('b.status = :published')
            ->setParameter('published', BookStatus::PUBLISHED)
            ->setFirstResult(($state->page - 1) * $perPage)
            ->setMaxResults($perPage);

        if ($user !== null) {
            $qb->leftJoin('b.userBooks', 'ub', 'WITH', 'ub.user = :currentUser')
               ->setParameter('currentUser', $user);
        }

        $this->applyFilterWhere($qb, $state, $user);
        $this->applyFilterOrder($qb, $state);

        return new Paginator($qb, fetchJoinCollection: false);
    }

    private function applyFilterWhere(\Doctrine\ORM\QueryBuilder $qb, ActiveFilterState $state, ?User $user): void
    {
        if (!empty($state->editors)) {
            $qb->andWhere('e.id IN (:editors)')
               ->setParameter('editors', $state->editors);
        }

        if (!empty($state->contributors)) {
            $uuids = [];
            foreach ($state->contributors as $id) {
                try { $uuids[] = Uuid::fromString($id); } catch (\Throwable) {}
            }
            if (!empty($uuids)) {
                $qb->andWhere('contributor.id IN (:contributors)')
                   ->setParameter('contributors', $uuids);
            }
        }

        if ($state->paragraphMin !== null) {
            $qb->andWhere('b.paragraphs >= :paragraphMin')
               ->setParameter('paragraphMin', $state->paragraphMin);
        }

        if ($state->paragraphMax !== null) {
            $qb->andWhere('b.paragraphs <= :paragraphMax')
               ->setParameter('paragraphMax', $state->paragraphMax);
        }

        if ($state->searchQuery !== null) {
            $qb->andWhere('LOWER(b.title) LIKE :q OR LOWER(contributor.firstName) LIKE :q OR LOWER(contributor.lastName) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($state->searchQuery) . '%');
        }

        if ($user !== null) {
            if ($state->collectionStatus !== null) {
                $qb->andWhere('ub.status = :collectionStatus')
                   ->setParameter('collectionStatus', $state->collectionStatus);
            }

            if ($state->onlyFavorites) {
                $qb->andWhere('ub.isFavorite = true');
            }
        }
    }

    private function applyFilterOrder(\Doctrine\ORM\QueryBuilder $qb, ActiveFilterState $state): void
    {
        match ($state->sort) {
            'alpha'       => $qb->orderBy('b.title', 'ASC'),
            'parution-fr' => $qb->orderBy('b.frenchPublicationYear', 'DESC'),
            'parution-orig' => $qb->orderBy('b.originalPublicationYear', 'DESC'),
            'recent'      => $qb->orderBy('b.id', 'DESC'),
            default       => $qb->orderBy('SIZE(b.reviews)', 'DESC')->addOrderBy('b.title', 'ASC'),
        };
    }
}
