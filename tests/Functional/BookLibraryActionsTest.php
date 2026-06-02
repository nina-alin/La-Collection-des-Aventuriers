<?php

namespace App\Tests\Functional;

use App\Entity\Book;
use App\Entity\Editor;
use App\Entity\Enum\BookStatus;
use App\Entity\User;
use App\Entity\UserBook;
use App\Repository\UserBookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class BookLibraryActionsTest extends WebTestCase
{
    use InteractsWithLiveComponents;

    private EntityManagerInterface $em;
    private User $user;
    private Book $book;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $editor = new Editor();
        $editor->setName('__test_editor_' . uniqid());
        $this->em->persist($editor);

        $this->book = new Book();
        $this->book->setTitle('__test_book_' . uniqid());
        $this->book->setEditor($editor);
        $this->book->setStatus(BookStatus::PUBLISHED);
        $this->em->persist($this->book);

        $this->user = new User();
        $this->user->setEmail('__test_' . uniqid() . '@example.com');
        $this->user->setPassword('hashed');
        $this->em->persist($this->user);

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\UserBook ub WHERE ub.user = :u')
            ->setParameter('u', $this->user)
            ->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Book b WHERE b.title LIKE :p')
            ->setParameter('p', '__test_book_%')
            ->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Editor e WHERE e.name LIKE :p')
            ->setParameter('p', '__test_editor_%')
            ->execute();
        $this->em->createQuery('DELETE FROM App\Entity\User u WHERE u.email LIKE :p')
            ->setParameter('p', '__test_%@example.com')
            ->execute();
        parent::tearDown();
    }

    private function createUserBook(bool $isOwned = false, bool $isToRead = false, bool $isToBuy = false, bool $isFavorite = false): UserBook
    {
        $ub = new UserBook($this->user, $this->book);
        $ub->setIsOwned($isOwned)->setIsToRead($isToRead)->setIsToBuy($isToBuy)->setIsFavorite($isFavorite);
        $this->em->persist($ub);
        $this->em->flush();
        return $ub;
    }

    // ─── US5: Restitution contextuelle au chargement ──────────────────────────

    public function testUS5AuthenticatedUserWithOwnedAndFavoriteSeeActiveButtons(): void
    {
        $this->createUserBook(isOwned: true, isFavorite: true);

        $component = $this->createLiveComponent('Book:LibraryActionsComponent', ['book' => $this->book])
            ->actingAs($this->user);

        $html = $component->render()->toString();

        $this->assertStringContainsString('is-active', $html);
    }

    public function testUS5UserWithNoUserBookSeesAllButtonsInactive(): void
    {
        $component = $this->createLiveComponent('Book:LibraryActionsComponent', ['book' => $this->book])
            ->actingAs($this->user);

        $html = $component->render()->toString();

        $this->assertStringNotContainsString('is-active', $html);
    }

    // ─── US1: "Ma Collection" toggle ──────────────────────────────────────────

    public function testUS1ToggleOwnedOnFromNoRecord(): void
    {
        $component = $this->createLiveComponent('Book:LibraryActionsComponent', ['book' => $this->book])
            ->actingAs($this->user);

        $component->call('toggleOwned');

        $ub = static::getContainer()->get(UserBookRepository::class)
            ->findByUserAndBook($this->user, $this->book);

        $this->assertNotNull($ub);
        $this->assertTrue($ub->isOwned());
    }

    public function testUS1ToggleOwnedOffFromOwned(): void
    {
        $this->createUserBook(isOwned: true, isFavorite: true);

        $component = $this->createLiveComponent('Book:LibraryActionsComponent', ['book' => $this->book])
            ->actingAs($this->user);

        $component->call('toggleOwned');

        $ub = static::getContainer()->get(UserBookRepository::class)
            ->findByUserAndBook($this->user, $this->book);

        $this->assertNotNull($ub, 'UserBook should still exist because isFavorite=true');
        $this->assertFalse($ub->isOwned());
    }

    public function testUS1AutoCoherenceClearsToBuyWhenOwnedActivated(): void
    {
        $this->createUserBook(isToBuy: true);

        $component = $this->createLiveComponent('Book:LibraryActionsComponent', ['book' => $this->book])
            ->actingAs($this->user);

        $component->call('toggleOwned');

        $ub = static::getContainer()->get(UserBookRepository::class)
            ->findByUserAndBook($this->user, $this->book);

        $this->assertTrue($ub->isOwned());
        $this->assertFalse($ub->isToBuy());
    }

    public function testUS1AllFalseAfterToggleDeletesRecord(): void
    {
        $this->createUserBook(isOwned: true);

        $component = $this->createLiveComponent('Book:LibraryActionsComponent', ['book' => $this->book])
            ->actingAs($this->user);

        $component->call('toggleOwned');

        $ub = static::getContainer()->get(UserBookRepository::class)
            ->findByUserAndBook($this->user, $this->book);

        $this->assertNull($ub);
    }

    // ─── US2: "À lire" toggle ─────────────────────────────────────────────────

    public function testUS2ToggleToReadOnFromNoRecord(): void
    {
        $component = $this->createLiveComponent('Book:LibraryActionsComponent', ['book' => $this->book])
            ->actingAs($this->user);

        $component->call('toggleToRead');

        $ub = static::getContainer()->get(UserBookRepository::class)
            ->findByUserAndBook($this->user, $this->book);

        $this->assertNotNull($ub);
        $this->assertTrue($ub->isToRead());
    }

    public function testUS2ToReadDoesNotAffectOwnedOrToBuy(): void
    {
        $this->createUserBook(isOwned: true);

        $component = $this->createLiveComponent('Book:LibraryActionsComponent', ['book' => $this->book])
            ->actingAs($this->user);

        $component->call('toggleToRead');

        $ub = static::getContainer()->get(UserBookRepository::class)
            ->findByUserAndBook($this->user, $this->book);

        $this->assertTrue($ub->isToRead());
        $this->assertTrue($ub->isOwned(), 'isOwned must not be affected by toggleToRead');
        $this->assertFalse($ub->isToBuy());
    }

    public function testUS2ToggleToReadIdempotence(): void
    {
        $this->createUserBook(isToRead: true, isFavorite: true);

        $component = $this->createLiveComponent('Book:LibraryActionsComponent', ['book' => $this->book])
            ->actingAs($this->user);

        $component->call('toggleToRead');

        $ub = static::getContainer()->get(UserBookRepository::class)
            ->findByUserAndBook($this->user, $this->book);

        $this->assertFalse($ub->isToRead());
    }

    // ─── US3: "À acheter" toggle ──────────────────────────────────────────────

    public function testUS3ToggleToBuyOnFromNoRecord(): void
    {
        $component = $this->createLiveComponent('Book:LibraryActionsComponent', ['book' => $this->book])
            ->actingAs($this->user);

        $component->call('toggleToBuy');

        $ub = static::getContainer()->get(UserBookRepository::class)
            ->findByUserAndBook($this->user, $this->book);

        $this->assertNotNull($ub);
        $this->assertTrue($ub->isToBuy());
    }

    public function testUS3AutoCoherenceClearsOwnedWhenToBuyActivated(): void
    {
        $this->createUserBook(isOwned: true);

        $component = $this->createLiveComponent('Book:LibraryActionsComponent', ['book' => $this->book])
            ->actingAs($this->user);

        $component->call('toggleToBuy');

        $ub = static::getContainer()->get(UserBookRepository::class)
            ->findByUserAndBook($this->user, $this->book);

        $this->assertTrue($ub->isToBuy());
        $this->assertFalse($ub->isOwned());
    }

    public function testUS3ToggleToBuyIdempotence(): void
    {
        $this->createUserBook(isToBuy: true, isFavorite: true);

        $component = $this->createLiveComponent('Book:LibraryActionsComponent', ['book' => $this->book])
            ->actingAs($this->user);

        $component->call('toggleToBuy');

        $ub = static::getContainer()->get(UserBookRepository::class)
            ->findByUserAndBook($this->user, $this->book);

        $this->assertFalse($ub->isToBuy());
    }

    // ─── US4: "Favori" toggle ─────────────────────────────────────────────────

    public function testUS4ToggleFavoriteOnFromNoRecord(): void
    {
        $component = $this->createLiveComponent('Book:LibraryActionsComponent', ['book' => $this->book])
            ->actingAs($this->user);

        $component->call('toggleFavorite');

        $ub = static::getContainer()->get(UserBookRepository::class)
            ->findByUserAndBook($this->user, $this->book);

        $this->assertNotNull($ub);
        $this->assertTrue($ub->isFavorite());
    }

    public function testUS4ToggleFavoriteDoesNotAffectOtherFlags(): void
    {
        $this->createUserBook(isOwned: true, isToRead: true);

        $component = $this->createLiveComponent('Book:LibraryActionsComponent', ['book' => $this->book])
            ->actingAs($this->user);

        $component->call('toggleFavorite');

        $ub = static::getContainer()->get(UserBookRepository::class)
            ->findByUserAndBook($this->user, $this->book);

        $this->assertTrue($ub->isFavorite());
        $this->assertTrue($ub->isOwned());
        $this->assertTrue($ub->isToRead());
        $this->assertFalse($ub->isToBuy());
    }

    public function testUS4FavoriteRemainsActiveAfterOwnershipRemoved(): void
    {
        $this->createUserBook(isOwned: true, isFavorite: true);

        $component = $this->createLiveComponent('Book:LibraryActionsComponent', ['book' => $this->book])
            ->actingAs($this->user);

        $component->call('toggleOwned');

        $ub = static::getContainer()->get(UserBookRepository::class)
            ->findByUserAndBook($this->user, $this->book);

        $this->assertNotNull($ub, 'Record must persist because isFavorite=true');
        $this->assertFalse($ub->isOwned());
        $this->assertTrue($ub->isFavorite());
    }

    // ─── Edge cases (T025) ───────────────────────────────────────────────────

    public function testAllFlagsFalseAfterToggleDeletesRow(): void
    {
        $this->createUserBook(isToBuy: true);

        $component = $this->createLiveComponent('Book:LibraryActionsComponent', ['book' => $this->book])
            ->actingAs($this->user);

        $component->call('toggleToBuy');

        $ub = static::getContainer()->get(UserBookRepository::class)
            ->findByUserAndBook($this->user, $this->book);

        $this->assertNull($ub, 'UserBook row must be deleted when all flags are false');
    }
}
