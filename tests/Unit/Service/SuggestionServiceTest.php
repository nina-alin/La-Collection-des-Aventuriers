<?php

namespace App\Tests\Unit\Service;

use App\Entity\Enum\SuggestionEntityType;
use App\Entity\Enum\SuggestionMode;
use App\Entity\Suggestion;
use App\Entity\User;
use App\Repository\SuggestionRepository;
use App\Service\SuggestionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SuggestionServiceTest extends TestCase
{
    private SuggestionRepository $repository;
    private EntityManagerInterface $em;
    private SuggestionService $service;
    private User $user;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SuggestionRepository::class);
        $this->em         = $this->createMock(EntityManagerInterface::class);
        $dispatcher       = $this->createMock(EventDispatcherInterface::class);
        $this->service    = new SuggestionService($this->repository, $this->em, $dispatcher);
        $this->user       = $this->createMock(User::class);
    }

    public function testSubmitPersistsEntityAndReturnsIt(): void
    {
        $this->repository->method('findPendingCountByUser')->willReturn(0);
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Suggestion::class));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->submit(
            $this->user,
            ['title' => 'Mon Livre'],
            SuggestionEntityType::BOOK,
            SuggestionMode::NEW_ENTRY,
            null,
            null,
        );

        $this->assertInstanceOf(Suggestion::class, $result);
    }

    public function testSubmitThrowsWhenPendingCountReachesQuota(): void
    {
        $this->repository->method('findPendingCountByUser')->willReturn(20);
        $this->em->expects($this->never())->method('persist');

        $this->expectException(\RuntimeException::class);

        $this->service->submit(
            $this->user,
            ['title' => 'Mon Livre'],
            SuggestionEntityType::BOOK,
            SuggestionMode::NEW_ENTRY,
            null,
            null,
        );
    }

    public function testGetPendingCountDelegatesToRepository(): void
    {
        $this->repository->method('findPendingCountByUser')->willReturn(7);

        $count = $this->service->getPendingCount($this->user);

        $this->assertSame(7, $count);
    }
}
