<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Book;
use App\Entity\Collection;
use App\Entity\Editor;
use App\Entity\Enum\BookStatus;
use App\Entity\Enum\GenreCollection;
use App\Entity\Enum\StatutCollection;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\CollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CollectionRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CollectionRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->repo = $this->em->getRepository(Collection::class);
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Review r WHERE r.book IN (SELECT b FROM App\Entity\Book b WHERE b.collection IS NOT NULL)')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Book b WHERE b.collection IS NOT NULL')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Collection c WHERE c.nom LIKE :prefix')
            ->setParameter('prefix', 'RepoTest%')
            ->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Editor e WHERE e.name LIKE :prefix')
            ->setParameter('prefix', 'RepoTest%')
            ->execute();
        parent::tearDown();
    }

    private function makeCollection(string $suffix = ''): Collection
    {
        $c = new Collection();
        $c->setNom('RepoTest Collection ' . $suffix . uniqid());
        $c->setDescription('Test');
        $c->setGenre(GenreCollection::AVENTURE);
        $c->setStatut(StatutCollection::EN_COURS);
        $this->em->persist($c);
        return $c;
    }

    private function makeEditor(): Editor
    {
        $e = new Editor();
        $e->setName('RepoTest Editor ' . uniqid());
        $this->em->persist($e);
        return $e;
    }

    private function makeBook(Collection $collection, Editor $editor, ?int $year): Book
    {
        $b = new Book();
        $b->setTitle('Book ' . uniqid());
        $b->setStatus(BookStatus::PUBLISHED);
        $b->setCollection($collection);
        $b->setEditor($editor);
        $b->setFrenchPublicationYear($year);
        $this->em->persist($b);
        return $b;
    }

    public function testGetPublicationYearRangeAllNull(): void
    {
        $collection = $this->makeCollection('allnull');
        $editor = $this->makeEditor();
        $this->makeBook($collection, $editor, null);
        $this->makeBook($collection, $editor, null);
        $this->em->flush();

        $range = $this->repo->getPublicationYearRange($collection);

        $this->assertNull($range['min']);
        $this->assertNull($range['max']);
    }

    public function testGetPublicationYearRangeSomeNull(): void
    {
        $collection = $this->makeCollection('somenull');
        $editor = $this->makeEditor();
        $this->makeBook($collection, $editor, 1984);
        $this->makeBook($collection, $editor, null);
        $this->makeBook($collection, $editor, 1992);
        $this->em->flush();

        $range = $this->repo->getPublicationYearRange($collection);

        $this->assertSame(1984, $range['min']);
        $this->assertSame(1992, $range['max']);
    }

    public function testGetPublicationYearRangeNoneNull(): void
    {
        $collection = $this->makeCollection('nonenull');
        $editor = $this->makeEditor();
        $this->makeBook($collection, $editor, 1990);
        $this->makeBook($collection, $editor, 1995);
        $this->makeBook($collection, $editor, 1987);
        $this->em->flush();

        $range = $this->repo->getPublicationYearRange($collection);

        $this->assertSame(1987, $range['min']);
        $this->assertSame(1995, $range['max']);
    }

    public function testComputeAverageRatingNullWhenNoReviews(): void
    {
        $collection = $this->makeCollection('noreviews');
        $editor = $this->makeEditor();
        $this->makeBook($collection, $editor, 1990);
        $this->em->flush();

        $avg = $this->repo->computeAverageRating($collection);

        $this->assertNull($avg);
    }

    public function testComputeAverageRatingWithReviews(): void
    {
        $collection = $this->makeCollection('reviews');
        $editor = $this->makeEditor();
        $book = $this->makeBook($collection, $editor, 1990);
        $this->em->flush();

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);
        if ($user === null) {
            $this->markTestSkipped('Need admin user fixture for review test');
        }

        $review = new Review();
        $review->setBook($book);
        $review->setUser($user);
        $review->setScore(8);
        $this->em->persist($review);
        $this->em->flush();

        $avg = $this->repo->computeAverageRating($collection);

        $this->assertNotNull($avg);
        $this->assertEqualsWithDelta(8.0, $avg, 0.1);
    }
}
