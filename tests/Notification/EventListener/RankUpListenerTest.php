<?php

namespace App\Tests\Notification\EventListener;

use App\Entity\ContributorLevel;
use App\Entity\NotificationPreference;
use App\Entity\User;
use App\Event\RankUpEvent;
use App\EventListener\RankUpListener;
use App\Messenger\Message\NotificationMessage;
use App\Repository\NotificationPreferenceRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

class RankUpListenerTest extends TestCase
{
    private function makeUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(Uuid::v4());
        return $user;
    }

    private function makeLevel(int $rankNumber, string $name): ContributorLevel
    {
        $level = $this->createMock(ContributorLevel::class);
        $level->method('getRankNumber')->willReturn($rankNumber);
        $level->method('getName')->willReturn($name);
        return $level;
    }

    private function makeRouter(): UrlGeneratorInterface
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/suggestions');
        return $router;
    }

    public function testAlwaysDispatchesRegardlessOfPreference(): void
    {
        $user = $this->makeUser();
        $level = $this->makeLevel(3, 'Chroniqueur confirmé');

        $pref = $this->createMock(NotificationPreference::class);
        $pref->method('isEnabled')->willReturn(false);

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn($pref);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(NotificationMessage::class))
            ->willReturn(new Envelope(new \stdClass()));

        $listener = new RankUpListener($bus, $prefRepo, $this->makeRouter());
        $listener(new RankUpEvent($user, $level));
    }

    public function testAlwaysDispatchesWhenNoPreferenceExists(): void
    {
        $user = $this->makeUser();
        $level = $this->makeLevel(2, 'Apprenti');

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn(null);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(NotificationMessage::class))
            ->willReturn(new Envelope(new \stdClass()));

        $listener = new RankUpListener($bus, $prefRepo, $this->makeRouter());
        $listener(new RankUpEvent($user, $level));
    }
}
