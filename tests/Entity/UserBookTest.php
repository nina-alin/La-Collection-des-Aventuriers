<?php

namespace App\Tests\Entity;

use App\Dto\ActiveFilterState;
use App\Entity\Book;
use App\Entity\User;
use App\Entity\UserBook;
use PHPUnit\Framework\TestCase;

class UserBookTest extends TestCase
{
    public function testUserBookConstruction(): void
    {
        [$user, $book] = $this->makeUserAndBook();

        $userBook = new UserBook($user, $book);

        $this->assertSame($user, $userBook->getUser());
        $this->assertSame($book, $userBook->getBook());
        $this->assertFalse($userBook->isFavorite());
        $this->assertFalse($userBook->isOwned());
        $this->assertNull($userBook->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $userBook->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $userBook->getUpdatedAt());
    }

    public function testUserBookSetIsFavorite(): void
    {
        [$user, $book] = $this->makeUserAndBook();
        $userBook = new UserBook($user, $book);

        $userBook->setIsFavorite(true);

        $this->assertTrue($userBook->isFavorite());
    }

    public function testUserBookSetIsOwned(): void
    {
        [$user, $book] = $this->makeUserAndBook();
        $userBook = new UserBook($user, $book);

        $userBook->setIsOwned(true);

        $this->assertTrue($userBook->isOwned());
    }

    public function testUserBookSetIsToRead(): void
    {
        [$user, $book] = $this->makeUserAndBook();
        $userBook = new UserBook($user, $book);

        $userBook->setIsToRead(true);

        $this->assertTrue($userBook->isToRead());
    }

    public function testUserBookSetIsToBuy(): void
    {
        [$user, $book] = $this->makeUserAndBook();
        $userBook = new UserBook($user, $book);

        $userBook->setIsToBuy(true);

        $this->assertTrue($userBook->isToBuy());
    }

    public function testUserBookIsAllInactiveWhenAllFlagsAreFalse(): void
    {
        [$user, $book] = $this->makeUserAndBook();
        $userBook = new UserBook($user, $book);

        $this->assertTrue($userBook->isAllInactive());
    }

    public function testUserBookIsNotAllInactiveWhenOneIsSet(): void
    {
        [$user, $book] = $this->makeUserAndBook();
        $userBook = new UserBook($user, $book);
        $userBook->setIsOwned(true);

        $this->assertFalse($userBook->isAllInactive());
    }

    public function testActiveFilterStateCountZeroFilters(): void
    {
        $state = new ActiveFilterState();

        $this->assertSame(0, $state->countActiveFilters());
    }

    public function testActiveFilterStateCountEditors(): void
    {
        $state = new ActiveFilterState(editors: [1, 2, 3]);

        $this->assertSame(3, $state->countActiveFilters());
    }

    public function testActiveFilterStateCountParagraphRange(): void
    {
        $state = new ActiveFilterState(paragraphMin: 100, paragraphMax: 400);

        $this->assertSame(1, $state->countActiveFilters());
    }

    public function testActiveFilterStateCountParagraphMinOnly(): void
    {
        $state = new ActiveFilterState(paragraphMin: 100);

        $this->assertSame(1, $state->countActiveFilters());
    }

    public function testActiveFilterStateCountCollectionStatus(): void
    {
        $state = new ActiveFilterState(collectionStatus: 'dans-ma-collection');

        $this->assertSame(1, $state->countActiveFilters());
    }

    public function testActiveFilterStateCountFavorites(): void
    {
        $state = new ActiveFilterState(onlyFavorites: true);

        $this->assertSame(1, $state->countActiveFilters());
    }

    public function testActiveFilterStateCountSearchQuery(): void
    {
        $state = new ActiveFilterState(searchQuery: 'Loup Noir');

        $this->assertSame(1, $state->countActiveFilters());
    }

    public function testActiveFilterStateCountSortExcluded(): void
    {
        $state = new ActiveFilterState(sort: 'alpha');

        $this->assertSame(0, $state->countActiveFilters());
    }

    public function testActiveFilterStateCountPageExcluded(): void
    {
        $state = new ActiveFilterState(page: 5);

        $this->assertSame(0, $state->countActiveFilters());
    }

    public function testActiveFilterStateCountAllFilters(): void
    {
        $state = new ActiveFilterState(
            editors: [1, 2],
            paragraphMin: 100,
            paragraphMax: 400,
            collectionStatus: 'lu',
            onlyFavorites: true,
            hideModeration: true,
            searchQuery: 'test',
        );

        // 2 editors + 1 paragraph range + 1 collection status + 1 favorites + 1 hideModeration + 1 search = 7
        $this->assertSame(7, $state->countActiveFilters());
    }

    public function testActiveFilterStateToUrlParamsDefaults(): void
    {
        $state = new ActiveFilterState();

        $this->assertSame([], $state->toUrlParams());
    }

    public function testActiveFilterStateToUrlParamsWithValues(): void
    {
        $state = new ActiveFilterState(
            sort: 'alpha',
            editors: [3, 7],
            paragraphMin: 200,
            searchQuery: 'Loup',
            page: 2,
        );

        $params = $state->toUrlParams();

        $this->assertSame('alpha', $params['sort']);
        $this->assertSame([3, 7], $params['editors']);
        $this->assertSame(200, $params['paragraphMin']);
        $this->assertSame('Loup', $params['q']);
        $this->assertSame(2, $params['page']);
    }

    /** @return array{0: User, 1: Book} */
    private function makeUserAndBook(): array
    {
        $user = $this->createMock(User::class);
        $book = $this->createMock(Book::class);
        return [$user, $book];
    }
}
