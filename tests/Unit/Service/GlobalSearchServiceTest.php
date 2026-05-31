<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\Search\SearchResultItem;
use App\Entity\Book;
use App\Entity\Collection;
use App\Entity\Contributor;
use App\Entity\Editor;
use App\Entity\Enum\BookStatus;
use App\Entity\Enum\ContributionRole;
use App\Repository\BookRepository;
use App\Repository\CollectionRepository;
use App\Repository\ContributorRepository;
use App\Service\GlobalSearchService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class GlobalSearchServiceTest extends TestCase
{
    private BookRepository&MockObject $bookRepo;
    private CollectionRepository&MockObject $collectionRepo;
    private ContributorRepository&MockObject $contributorRepo;
    private GlobalSearchService $service;

    protected function setUp(): void
    {
        $this->bookRepo = $this->createMock(BookRepository::class);
        $this->collectionRepo = $this->createMock(CollectionRepository::class);
        $this->contributorRepo = $this->createMock(ContributorRepository::class);

        $this->service = new GlobalSearchService(
            $this->bookRepo,
            $this->collectionRepo,
            $this->contributorRepo,
        );
    }

    public function testQueryHappyPathReturnsSearchResultItems(): void
    {
        $book = $this->makeBook('Le Sorcier', '001', 1984, 'Steve', 'Jackson');
        $this->bookRepo->method('findForGlobalSearch')->willReturn([$book]);
        $this->collectionRepo->method('findForGlobalSearch')->willReturn([]);
        $this->contributorRepo->method('findForGlobalSearch')->willReturn([]);

        $results = $this->service->query('sorcier');

        $this->assertCount(1, $results);
        $this->assertInstanceOf(SearchResultItem::class, $results[0]);
        $this->assertSame('livre', $results[0]->type);
        $this->assertSame('Le Sorcier', $results[0]->title);
    }

    public function testEmptyQueryReturnsEmptyArray(): void
    {
        $this->bookRepo->expects($this->never())->method('findForGlobalSearch');
        $this->collectionRepo->expects($this->never())->method('findForGlobalSearch');
        $this->contributorRepo->expects($this->never())->method('findForGlobalSearch');

        $this->assertSame([], $this->service->query(''));
        $this->assertSame([], $this->service->query('   '));
    }

    public function testResultsAreCappedAtEight(): void
    {
        $books = array_fill(0, 5, $this->makeBook('Titre', null, null, 'A', 'B'));
        $collections = array_fill(0, 3, $this->makeCollection('Coll', []));
        $contributors = array_fill(0, 3, $this->makeContributor('X', 'Y', 'x-y'));

        $this->bookRepo->method('findForGlobalSearch')->willReturn($books);
        $this->collectionRepo->method('findForGlobalSearch')->willReturn($collections);
        $this->contributorRepo->method('findForGlobalSearch')->willReturn($contributors);

        $results = $this->service->query('test');

        $this->assertCount(8, $results);
    }

    public function testBookSubtitleFormattedCorrectly(): void
    {
        $book = $this->makeBook('Titre', '978-2-01-012345-6', 1985, 'Steve', 'Jackson');
        $this->bookRepo->method('findForGlobalSearch')->willReturn([$book]);
        $this->collectionRepo->method('findForGlobalSearch')->willReturn([]);
        $this->contributorRepo->method('findForGlobalSearch')->willReturn([]);

        $results = $this->service->query('titre');

        $this->assertStringContainsString('978-2-01-012345-6', $results[0]->subtitle);
        $this->assertStringContainsString('1985', $results[0]->subtitle);
        $this->assertStringContainsString('Steve Jackson', $results[0]->subtitle);
    }

    public function testCollectionSubtitleFormattedCorrectly(): void
    {
        $collection = $this->makeCollection('Défis Fantastiques', ['Steve Jackson']);
        $this->addBooksToCollection($collection, 59);

        $this->bookRepo->method('findForGlobalSearch')->willReturn([]);
        $this->collectionRepo->method('findForGlobalSearch')->willReturn([$collection]);
        $this->contributorRepo->method('findForGlobalSearch')->willReturn([]);

        $results = $this->service->query('defis');

        $this->assertSame('collection', $results[0]->type);
        $this->assertStringContainsString('collection', $results[0]->subtitle);
        $this->assertStringContainsString('tomes', $results[0]->subtitle);
        $this->assertStringContainsString('Steve Jackson', $results[0]->subtitle);
    }

    public function testAuteurSubtitleFormattedCorrectly(): void
    {
        $contributor = $this->makeContributor('Steve', 'Jackson', 'steve-jackson');
        $this->addContributionsToContributor($contributor, 14);

        $this->bookRepo->method('findForGlobalSearch')->willReturn([]);
        $this->collectionRepo->method('findForGlobalSearch')->willReturn([]);
        $this->contributorRepo->method('findForGlobalSearch')->willReturn([$contributor]);

        $results = $this->service->query('steve');

        $this->assertSame('auteur', $results[0]->type);
        $this->assertStringContainsString('auteur', $results[0]->subtitle);
        $this->assertStringContainsString('fiches', $results[0]->subtitle);
    }

    public function testAuteurHasNonNullInitialsAndAvatarColor(): void
    {
        $contributor = $this->makeContributor('Steve', 'Jackson', 'steve-jackson');

        $this->bookRepo->method('findForGlobalSearch')->willReturn([]);
        $this->collectionRepo->method('findForGlobalSearch')->willReturn([]);
        $this->contributorRepo->method('findForGlobalSearch')->willReturn([$contributor]);

        $results = $this->service->query('steve');

        $this->assertNotNull($results[0]->initials);
        $this->assertNotNull($results[0]->avatarColor);
        $this->assertSame('SJ', $results[0]->initials);
        $this->assertContains($results[0]->avatarColor, ['cuir', 'mousse', 'encre', 'sang', 'or']);
    }

    private function makeBook(string $title, ?string $isbn, ?int $year, string $firstName, string $lastName): Book
    {
        $book = new Book();
        $book->setTitle($title);
        $book->setStatus(BookStatus::PUBLISHED);
        if ($isbn !== null) {
            $book->setIsbn($isbn);
        }
        if ($year !== null) {
            $book->setFrenchPublicationYear($year);
        }

        $editor = (new Editor())->setName('Test Editor');
        $book->setEditor($editor);

        $contributor = $this->makeContributor($firstName, $lastName, mb_strtolower($firstName . '-' . $lastName));
        $contribution = new \App\Entity\Contribution();
        $contribution->setContributor($contributor);
        $contribution->setBook($book);
        $contribution->setRole(ContributionRole::Author);
        $book->getContributions()->add($contribution);

        return $book;
    }

    private function makeCollection(string $nom, array $createurs): Collection
    {
        $collection = new Collection();
        $collection->setNom($nom);
        $collection->setSlug(mb_strtolower(str_replace(' ', '-', $nom)));
        $collection->setDescription('desc');
        $collection->setCreateurs($createurs);
        return $collection;
    }

    private function makeContributor(string $firstName, string $lastName, string $slug): Contributor
    {
        $contributor = new Contributor();
        $contributor->setFirstName($firstName);
        $contributor->setLastName($lastName);
        $contributor->setSlug($slug);
        return $contributor;
    }

    private function addBooksToCollection(Collection $collection, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $editor = (new Editor())->setName('Ed');
            $book = new Book();
            $book->setTitle('Book ' . $i);
            $book->setStatus(BookStatus::PUBLISHED);
            $book->setEditor($editor);
            $collection->getBooks()->add($book);
        }
    }

    private function addContributionsToContributor(Contributor $contributor, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $contribution = new \App\Entity\Contribution();
            $contribution->setContributor($contributor);
            $contributor->getContributions()->add($contribution);
        }
    }
}
