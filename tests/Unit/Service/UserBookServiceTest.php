<?php

namespace App\Tests\Unit\Service;

use App\Entity\Book;
use App\Entity\User;
use App\Entity\UserBook;
use App\Repository\UserBookRepository;
use App\Service\UserBookService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UserBookServiceTest extends TestCase
{
    private UserBookRepository&MockObject $repo;
    private EntityManagerInterface&MockObject $em;
    private EventDispatcherInterface&MockObject $dispatcher;
    private UserBookService $service;
    private User $user;
    private Book $book;

    protected function setUp(): void
    {
        $this->repo       = $this->createMock(UserBookRepository::class);
        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->service    = new UserBookService($this->repo, $this->em, $this->dispatcher);
        $this->user       = new User();
        $this->book       = $this->createMock(Book::class);
    }

    // ─── toggleOwned ─────────────────────────────────────────────────────────

    public function testToggleOwnedCreatesRecordWhenNoneExists(): void
    {
        $this->repo->method('findByUserAndBook')->willReturn(null);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->toggleOwned($this->user, $this->book);

        $this->assertTrue($result['newValue']);
        $this->assertSame([], $result['affected']);
    }

    public function testToggleOwnedSetsOwnedTrueAndClearsToBuy(): void
    {
        $ub = new UserBook($this->user, $this->book);
        $ub->setIsToBuy(true);
        $this->repo->method('findByUserAndBook')->willReturn($ub);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->toggleOwned($this->user, $this->book);

        $this->assertTrue($result['newValue']);
        $this->assertTrue($ub->isOwned());
        $this->assertFalse($ub->isToBuy());
        $this->assertContains('isToBuy', $result['affected']);
    }

    public function testToggleOwnedSetsOwnedFalseWhenAlreadyOwned(): void
    {
        $ub = new UserBook($this->user, $this->book);
        $ub->setIsOwned(true);
        $ub->setIsFavorite(true);
        $this->repo->method('findByUserAndBook')->willReturn($ub);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->toggleOwned($this->user, $this->book);

        $this->assertFalse($result['newValue']);
        $this->assertFalse($ub->isOwned());
        $this->assertSame([], $result['affected']);
    }

    public function testToggleOwnedDeletesRecordWhenAllInactive(): void
    {
        $ub = new UserBook($this->user, $this->book);
        $ub->setIsOwned(true);
        $this->repo->method('findByUserAndBook')->willReturn($ub);
        $this->em->expects($this->once())->method('remove')->with($ub);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->toggleOwned($this->user, $this->book);

        $this->assertFalse($result['newValue']);
    }

    // ─── toggleToRead ─────────────────────────────────────────────────────────

    public function testToggleToReadCreatesRecordWhenNoneExists(): void
    {
        $this->repo->method('findByUserAndBook')->willReturn(null);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->toggleToRead($this->user, $this->book);

        $this->assertTrue($result['newValue']);
        $this->assertSame([], $result['affected']);
    }

    public function testToggleToReadTogglesBackToFalse(): void
    {
        $ub = new UserBook($this->user, $this->book);
        $ub->setIsToRead(true);
        $ub->setIsOwned(true);
        $this->repo->method('findByUserAndBook')->willReturn($ub);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->toggleToRead($this->user, $this->book);

        $this->assertFalse($result['newValue']);
        $this->assertFalse($ub->isToRead());
        $this->assertTrue($ub->isOwned(), 'isOwned must not be affected by toggleToRead');
    }

    public function testToggleToReadNoAutoCoherenceSideEffects(): void
    {
        $ub = new UserBook($this->user, $this->book);
        $ub->setIsOwned(true);
        $ub->setIsToBuy(false);
        $this->repo->method('findByUserAndBook')->willReturn($ub);
        $this->em->method('flush');

        $result = $this->service->toggleToRead($this->user, $this->book);

        $this->assertSame([], $result['affected']);
        $this->assertTrue($ub->isOwned());
        $this->assertFalse($ub->isToBuy());
    }

    public function testToggleToReadDeletesRecordWhenAllInactive(): void
    {
        $ub = new UserBook($this->user, $this->book);
        $ub->setIsToRead(true);
        $this->repo->method('findByUserAndBook')->willReturn($ub);
        $this->em->expects($this->once())->method('remove')->with($ub);
        $this->em->expects($this->once())->method('flush');

        $this->service->toggleToRead($this->user, $this->book);
    }

    // ─── toggleToBuy ──────────────────────────────────────────────────────────

    public function testToggleToBuyCreatesRecordWhenNoneExists(): void
    {
        $this->repo->method('findByUserAndBook')->willReturn(null);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->toggleToBuy($this->user, $this->book);

        $this->assertTrue($result['newValue']);
        $this->assertSame([], $result['affected']);
    }

    public function testToggleToBuySetsOwnedFalseWhenActivated(): void
    {
        $ub = new UserBook($this->user, $this->book);
        $ub->setIsOwned(true);
        $this->repo->method('findByUserAndBook')->willReturn($ub);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->toggleToBuy($this->user, $this->book);

        $this->assertTrue($result['newValue']);
        $this->assertTrue($ub->isToBuy());
        $this->assertFalse($ub->isOwned());
        $this->assertContains('isOwned', $result['affected']);
    }

    public function testToggleToBuySetsToFalseWhenAlreadySet(): void
    {
        $ub = new UserBook($this->user, $this->book);
        $ub->setIsToBuy(true);
        $ub->setIsToRead(true);
        $this->repo->method('findByUserAndBook')->willReturn($ub);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->toggleToBuy($this->user, $this->book);

        $this->assertFalse($result['newValue']);
        $this->assertFalse($ub->isToBuy());
        $this->assertTrue($ub->isToRead(), 'isToRead must not be affected');
        $this->assertSame([], $result['affected']);
    }

    public function testToggleToBuyDeletesRecordWhenAllInactive(): void
    {
        $ub = new UserBook($this->user, $this->book);
        $ub->setIsToBuy(true);
        $this->repo->method('findByUserAndBook')->willReturn($ub);
        $this->em->expects($this->once())->method('remove')->with($ub);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->toggleToBuy($this->user, $this->book);

        $this->assertFalse($result['newValue']);
    }

    // ─── toggleFavorite ───────────────────────────────────────────────────────

    public function testToggleFavoriteCreatesRecordWhenNoneExists(): void
    {
        $this->repo->method('findByUserAndBook')->willReturn(null);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->toggleFavorite($this->user, $this->book);

        $this->assertTrue($result['newValue']);
        $this->assertSame([], $result['affected']);
    }

    public function testToggleFavoriteTogglesBackToFalse(): void
    {
        $ub = new UserBook($this->user, $this->book);
        $ub->setIsFavorite(true);
        $ub->setIsOwned(true);
        $this->repo->method('findByUserAndBook')->willReturn($ub);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->toggleFavorite($this->user, $this->book);

        $this->assertFalse($result['newValue']);
        $this->assertFalse($ub->isFavorite());
        $this->assertTrue($ub->isOwned(), 'isOwned must not be affected by toggleFavorite');
    }

    public function testToggleFavoriteNoAutoCoherence(): void
    {
        $ub = new UserBook($this->user, $this->book);
        $ub->setIsOwned(true);
        $ub->setIsToBuy(false);
        $ub->setIsToRead(true);
        $this->repo->method('findByUserAndBook')->willReturn($ub);
        $this->em->method('flush');

        $result = $this->service->toggleFavorite($this->user, $this->book);

        $this->assertSame([], $result['affected']);
        $this->assertTrue($ub->isOwned());
        $this->assertFalse($ub->isToBuy());
        $this->assertTrue($ub->isToRead());
    }

    public function testToggleFavoriteDeletesRecordWhenAllInactive(): void
    {
        $ub = new UserBook($this->user, $this->book);
        $ub->setIsFavorite(true);
        $this->repo->method('findByUserAndBook')->willReturn($ub);
        $this->em->expects($this->once())->method('remove')->with($ub);
        $this->em->expects($this->once())->method('flush');

        $this->service->toggleFavorite($this->user, $this->book);
    }
}
