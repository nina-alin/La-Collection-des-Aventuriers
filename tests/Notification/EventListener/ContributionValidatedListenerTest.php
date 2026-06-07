<?php

namespace App\Tests\Notification\EventListener;

use App\Entity\ContributorLevel;
use App\Entity\Enum\NotificationType;
use App\Entity\NotificationPreference;
use App\Entity\User;
use App\Event\ContributionValidatedEvent;
use App\Event\RankUpEvent;
use App\EventListener\ContributionValidatedListener;
use App\Messenger\Message\NotificationMessage;
use App\Repository\NotificationPreferenceRepository;
use App\Service\ContributorLevelService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class ContributionValidatedListenerTest extends TestCase
{
    private function makeUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(Uuid::v4());
        return $user;
    }

    public function testDispatchesNotificationMessageWithCorrectFields(): void
    {
        $user = $this->makeUser();

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->isInstanceOf(NotificationMessage::class))
            ->willReturn(new Envelope(new \stdClass()));

        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn(null);

        $levelService = $this->createMock(ContributorLevelService::class);
        $levelService->method('computeRank')->willReturn(null);

        $listener = new ContributionValidatedListener($bus, $dispatcher, $prefRepo, $levelService);
        $listener(new ContributionValidatedEvent('Mon livre', $user));
    }

    public function testSkipsWhenPreferenceDisabled(): void
    {
        $user = $this->makeUser();

        $pref = $this->createMock(NotificationPreference::class);
        $pref->method('isEnabled')->willReturn(false);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn($pref);

        $levelService = $this->createMock(ContributorLevelService::class);

        $listener = new ContributionValidatedListener($bus, $dispatcher, $prefRepo, $levelService);
        $listener(new ContributionValidatedEvent('Mon livre', $user));
    }

    public function testDetectsRankUpAndDispatchesRankUpEvent(): void
    {
        $user = $this->makeUser();

        $oldLevel = $this->createMock(ContributorLevel::class);
        $oldLevel->method('getId')->willReturn(1);

        $newLevel = $this->createMock(ContributorLevel::class);
        $newLevel->method('getId')->willReturn(2);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(RankUpEvent::class));

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn(null);

        $levelService = $this->createMock(ContributorLevelService::class);
        $levelService->method('computeRank')
            ->willReturnOnConsecutiveCalls($oldLevel, $newLevel);

        $listener = new ContributionValidatedListener($bus, $dispatcher, $prefRepo, $levelService);
        $listener(new ContributionValidatedEvent('Mon livre', $user));
    }

    public function testRankDetectionRunsEvenWhenContributionPrefDisabled(): void
    {
        $user = $this->makeUser();

        $pref = $this->createMock(NotificationPreference::class);
        $pref->method('isEnabled')->willReturn(false);

        $oldLevel = $this->createMock(ContributorLevel::class);
        $oldLevel->method('getId')->willReturn(1);

        $newLevel = $this->createMock(ContributorLevel::class);
        $newLevel->method('getId')->willReturn(2);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(RankUpEvent::class));

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn($pref);

        $levelService = $this->createMock(ContributorLevelService::class);
        $levelService->method('computeRank')
            ->willReturnOnConsecutiveCalls($oldLevel, $newLevel);

        $listener = new ContributionValidatedListener($bus, $dispatcher, $prefRepo, $levelService);
        $listener(new ContributionValidatedEvent('Mon livre', $user));
    }

    public function testDoesNotDispatchRankUpWhenRankUnchanged(): void
    {
        $user = $this->makeUser();

        $level = $this->createMock(ContributorLevel::class);
        $level->method('getId')->willReturn(1);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn(null);

        $levelService = $this->createMock(ContributorLevelService::class);
        $levelService->method('computeRank')->willReturn($level);

        $listener = new ContributionValidatedListener($bus, $dispatcher, $prefRepo, $levelService);
        $listener(new ContributionValidatedEvent('Mon livre', $user));
    }
}
