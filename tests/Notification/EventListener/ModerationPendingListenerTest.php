<?php

namespace App\Tests\Notification\EventListener;

use App\Entity\Enum\NotificationType;
use App\Entity\NotificationPreference;
use App\Entity\Suggestion;
use App\Entity\User;
use App\Event\ModerationPendingEvent;
use App\EventListener\ModerationPendingListener;
use App\Repository\NotificationPreferenceRepository;
use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class ModerationPendingListenerTest extends TestCase
{
    private function makeUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(Uuid::v4());
        return $user;
    }

    private function makeSuggestion(): Suggestion
    {
        $s = $this->createMock(Suggestion::class);
        $s->method('getId')->willReturn(Uuid::v4());
        return $s;
    }

    public function testDispatchesOneMessagePerModerator(): void
    {
        $mod1 = $this->makeUser();
        $mod2 = $this->makeUser();

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findByRole')->with('ROLE_MODERATOR')->willReturn([$mod1, $mod2]);

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn(null);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $listener = new ModerationPendingListener($bus, $userRepo, $prefRepo);
        $listener(new ModerationPendingEvent($this->makeSuggestion()));
    }

    public function testSkipsModeratorsWithDisabledPreference(): void
    {
        $mod = $this->makeUser();

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findByRole')->willReturn([$mod]);

        $pref = $this->createMock(NotificationPreference::class);
        $pref->method('isEnabled')->with(NotificationType::MODERATION_PENDING)->willReturn(false);

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn($pref);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $listener = new ModerationPendingListener($bus, $userRepo, $prefRepo);
        $listener(new ModerationPendingEvent($this->makeSuggestion()));
    }

    public function testDispatchesZeroMessagesIfNoModerators(): void
    {
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findByRole')->willReturn([]);

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $listener = new ModerationPendingListener($bus, $userRepo, $prefRepo);
        $listener(new ModerationPendingEvent($this->makeSuggestion()));
    }
}
