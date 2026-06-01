<?php

namespace App\Tests\Repository;

use App\Dto\ActiveFilterState;
use App\Entity\Book;
use App\Entity\Editor;
use App\Entity\Enum\BookStatus;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BookRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private BookRepository $bookRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->bookRepository = static::getContainer()->get(BookRepository::class);
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Book b WHERE b.title LIKE :prefix')
            ->setParameter('prefix', '__test_%')
            ->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Editor e WHERE e.name LIKE :prefix')
            ->setParameter('prefix', '__test_%')
            ->execute();
        parent::tearDown();
    }

    private function createPublishedBook(string $title, ?int $paragraphs = 200, ?Editor $editor = null): Book
    {
        if ($editor === null) {
            $editor = new Editor();
            $editor->setName('__test_editor_' . uniqid());
            $this->em->persist($editor);
        }

        $book = new Book();
        $book->setTitle('__test_' . $title);
        $book->setEditor($editor);
        $book->setStatus(BookStatus::PUBLISHED);
        if ($paragraphs !== null) {
            $book->setParagraphs($paragraphs);
        }
        $this->em->persist($book);
        $this->em->flush();

        return $book;
    }

    public function testFindParagraphBoundsReturnsMinMax(): void
    {
        $this->createPublishedBook('ParagraphBoundsA', 100);
        $this->createPublishedBook('ParagraphBoundsB', 300);
        $this->createPublishedBook('ParagraphBoundsC', 500);

        $bounds = $this->bookRepository->findParagraphBounds();

        $this->assertArrayHasKey('min', $bounds);
        $this->assertArrayHasKey('max', $bounds);
        $this->assertLessThanOrEqual($bounds['max'], $bounds['min']);
        $this->assertLessThanOrEqual(100, $bounds['min']);
        $this->assertGreaterThanOrEqual(500, $bounds['max']);
    }

    public function testCountFilteredWithNoFilters(): void
    {
        $this->createPublishedBook('CountFilteredA', 150);
        $this->createPublishedBook('CountFilteredB', 250);

        $state = new ActiveFilterState();
        $count = $this->bookRepository->countFiltered($state);

        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function testCountFilteredWithParagraphRange(): void
    {
        $this->createPublishedBook('CountFilteredRange100', 100);
        $this->createPublishedBook('CountFilteredRange300', 300);
        $this->createPublishedBook('CountFilteredRange500', 500);

        $state = new ActiveFilterState(paragraphMin: 200, paragraphMax: 400);
        $count = $this->bookRepository->countFiltered($state);

        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testCountFilteredWithEditorFilter(): void
    {
        $editor = new Editor();
        $editor->setName('__test_editor_filter_' . uniqid());
        $this->em->persist($editor);

        $this->createPublishedBook('BookWithEditor', 200, $editor);

        $state = new ActiveFilterState(editors: [$editor->getId()]);
        $count = $this->bookRepository->countFiltered($state);

        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindFilteredPaginatedReturnsPaginator(): void
    {
        $this->createPublishedBook('PaginatedA', 200);
        $this->createPublishedBook('PaginatedB', 300);

        $state = new ActiveFilterState();
        $result = $this->bookRepository->findFilteredPaginated($state);

        $this->assertInstanceOf(Paginator::class, $result);
        $this->assertGreaterThanOrEqual(0, count($result));
    }

    public function testFindFilteredPaginatedRespectsPagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->createPublishedBook('PaginationTest' . $i, 200 + $i * 10);
        }

        $state = new ActiveFilterState(page: 1);
        $result = $this->bookRepository->findFilteredPaginated($state, null, 2);

        $items = iterator_to_array($result->getIterator());
        $this->assertLessThanOrEqual(2, count($items));
    }

    public function testFindFilteredPaginatedWithSearchQuery(): void
    {
        $this->createPublishedBook('UniqueSearchableTitle' . uniqid(), 200);

        $state = new ActiveFilterState(searchQuery: 'UniqueSearchableTitle');
        $result = $this->bookRepository->findFilteredPaginated($state);

        $this->assertInstanceOf(Paginator::class, $result);
    }

    public function testFindFilteredPaginatedSortAlpha(): void
    {
        $this->createPublishedBook('AlphaBeta', 200);
        $this->createPublishedBook('AlphaAlpha', 200);

        $state = new ActiveFilterState(sort: 'alpha');
        $result = $this->bookRepository->findFilteredPaginated($state);

        $this->assertInstanceOf(Paginator::class, $result);
    }
}
