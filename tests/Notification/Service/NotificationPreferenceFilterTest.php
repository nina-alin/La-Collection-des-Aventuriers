<?php

namespace App\Tests\Notification\Service;

use App\Entity\Enum\NotificationType;
use App\Entity\NotificationPreference;
use App\Entity\User;
use App\Messenger\Handler\NotificationMessageHandler;
use App\Messenger\Message\NotificationMessage;
use App\Repository\NotificationPreferenceRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class NotificationPreferenceFilterTest extends TestCase
{
    private function makeMessage(string $type = 'rank_up'): NotificationMessage
    {
        return new NotificationMessage(
            userId: 'user-123',
            type: $type,
            message: 'Test',
            sourceId: $type . ':entity-1',
        );
    }

    public function testHandlerSkipsInsertWhenPreferenceDisabled(): void
    {
        $user = $this->createMock(User::class);
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('find')->willReturn($user);

        $preference = $this->createMock(NotificationPreference::class);
        $preference->method('isEnabled')->with(NotificationType::RANK_UP)->willReturn(false);

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn($preference);

        $notifRepo = $this->createMock(NotificationRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $handler = new NotificationMessageHandler($em, $userRepo, $notifRepo, $prefRepo);
        $handler($this->makeMessage('rank_up'));
    }

    public function testHandlerCreatesNotificationWhenPreferenceEnabled(): void
    {
        $user = $this->createMock(User::class);
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('find')->willReturn($user);

        $preference = $this->createMock(NotificationPreference::class);
        $preference->method('isEnabled')->with(NotificationType::RANK_UP)->willReturn(true);

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn($preference);

        $notifRepo = $this->createMock(NotificationRepository::class);
        $notifRepo->method('countForUser')->willReturn(1);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        $handler = new NotificationMessageHandler($em, $userRepo, $notifRepo, $prefRepo);
        $handler($this->makeMessage('rank_up'));
    }
}
