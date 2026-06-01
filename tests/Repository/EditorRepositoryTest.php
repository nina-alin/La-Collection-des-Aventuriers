<?php

namespace App\Tests\Repository;

use App\Entity\Book;
use App\Entity\Editor;
use App\Entity\Enum\BookStatus;
use App\Repository\EditorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EditorRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private EditorRepository $editorRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->editorRepository = static::getContainer()->get(EditorRepository::class);
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Book b WHERE b.title LIKE :prefix')
            ->setParameter('prefix', '__testeditor_%')
            ->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Editor e WHERE e.name LIKE :prefix')
            ->setParameter('prefix', '__testeditor_%')
            ->execute();
        parent::tearDown();
    }

    private function createEditor(string $name): Editor
    {
        $editor = new Editor();
        $editor->setName('__testeditor_' . $name);
        $this->em->persist($editor);
        $this->em->flush();
        return $editor;
    }

    private function createBookForEditor(Editor $editor): Book
    {
        $book = new Book();
        $book->setTitle('__testeditor_book_' . uniqid());
        $book->setEditor($editor);
        $book->setStatus(BookStatus::PUBLISHED);
        $this->em->persist($book);
        $this->em->flush();
        return $book;
    }

    public function testFindByNameSearchWithMatchingQuery(): void
    {
        $this->createEditor('GallimardJeunesse');
        $this->createEditor('GallimardBD');

        $results = $this->editorRepository->findByNameSearch('gallimard');

        $names = array_map(fn(Editor $e) => $e->getName(), $results);
        $matched = array_filter($names, fn(string $n) => str_contains(strtolower($n), 'gallimard'));
        $this->assertGreaterThanOrEqual(2, count($matched));
    }

    public function testFindByNameSearchWithEmptyStringReturnsResults(): void
    {
        $this->createEditor('EmptySearchTest' . uniqid());

        $results = $this->editorRepository->findByNameSearch('');

        $this->assertIsArray($results);
    }

    public function testFindByNameSearchWithNoMatch(): void
    {
        $results = $this->editorRepository->findByNameSearch('zzz_no_match_xyz_' . uniqid());

        $this->assertIsArray($results);
        $this->assertCount(0, $results);
    }

    public function testFindByNameSearchCaseInsensitive(): void
    {
        $editor = $this->createEditor('CaseTestEditor');

        $resultsLower = $this->editorRepository->findByNameSearch('casetesteditor');
        $resultsUpper = $this->editorRepository->findByNameSearch('CASETESTEDITOR');

        $lowerIds = array_map(fn(Editor $e) => $e->getId(), $resultsLower);
        $upperIds = array_map(fn(Editor $e) => $e->getId(), $resultsUpper);

        $this->assertContains($editor->getId(), $lowerIds);
        $this->assertContains($editor->getId(), $upperIds);
    }

    public function testFindWithBookCountReturnsArray(): void
    {
        $editor = $this->createEditor('BookCountTest' . uniqid());
        $this->createBookForEditor($editor);
        $this->createBookForEditor($editor);

        $results = $this->editorRepository->findWithBookCount();

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);

        foreach ($results as $row) {
            $this->assertInstanceOf(Editor::class, $row[0]);
            $this->assertArrayHasKey('bookCount', $row);
        }
    }

    public function testFindWithBookCountOrderedByBookCountDesc(): void
    {
        $editorA = $this->createEditor('OrderTestA_' . uniqid());
        $editorB = $this->createEditor('OrderTestB_' . uniqid());

        $this->createBookForEditor($editorA);
        $this->createBookForEditor($editorA);
        $this->createBookForEditor($editorB);

        $results = $this->editorRepository->findWithBookCount();

        $counts = array_map(fn(array $row) => (int) $row['bookCount'], $results);

        for ($i = 1; $i < count($counts); $i++) {
            $this->assertGreaterThanOrEqual($counts[$i], $counts[$i - 1]);
        }
    }
}
