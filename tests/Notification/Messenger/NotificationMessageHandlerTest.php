<?php

namespace App\Tests\Notification\Messenger;

use App\Entity\Enum\NotificationType;
use App\Entity\NotificationPreference;
use App\Entity\User;
use App\Messenger\Handler\NotificationMessageHandler;
use App\Messenger\Message\NotificationMessage;
use App\Repository\NotificationPreferenceRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class NotificationMessageHandlerTest extends TestCase
{
    private function makeMessage(string $type = 'rank_up'): NotificationMessage
    {
        return new NotificationMessage(
            userId: 'user-uuid-123',
            type: $type,
            message: 'Test notification',
            sourceId: $type . ':entity-123',
            targetUrl: '/profile',
        );
    }

    private function makeUser(): User
    {
        return $this->createMock(User::class);
    }

    public function testCreatesNotificationRowOnValidMessage(): void
    {
        $user = $this->makeUser();
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('find')->willReturn($user);

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn(null);

        $notifRepo = $this->createMock(NotificationRepository::class);
        $notifRepo->method('countForUser')->willReturn(1);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        $handler = new NotificationMessageHandler($em, $userRepo, $notifRepo, $prefRepo);
        $handler($this->makeMessage());
    }

    public function testSilentlySkipsOnDuplicateSourceId(): void
    {
        $user = $this->makeUser();
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('find')->willReturn($user);

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn(null);

        $notifRepo = $this->createMock(NotificationRepository::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist');
        $em->method('flush')->willThrowException(
            $this->createMock(UniqueConstraintViolationException::class)
        );

        $handler = new NotificationMessageHandler($em, $userRepo, $notifRepo, $prefRepo);

        // No exception expected
        $handler($this->makeMessage());
        $this->assertTrue(true);
    }

    public function testSkipsInsertWhenPreferenceDisabled(): void
    {
        $user = $this->makeUser();
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('find')->willReturn($user);

        $preference = $this->createMock(NotificationPreference::class);
        $preference->method('isEnabled')->willReturn(false);

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn($preference);

        $notifRepo = $this->createMock(NotificationRepository::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $handler = new NotificationMessageHandler($em, $userRepo, $notifRepo, $prefRepo);
        $handler($this->makeMessage());
    }

    public function testPrunesTo500AfterInsert(): void
    {
        $user = $this->makeUser();
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('find')->willReturn($user);

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn(null);

        $notifRepo = $this->createMock(NotificationRepository::class);
        $notifRepo->method('countForUser')->willReturn(503);
        $notifRepo->expects($this->once())
            ->method('deleteOldestForUser')
            ->with($user, 3);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist');
        $em->method('flush');

        $handler = new NotificationMessageHandler($em, $userRepo, $notifRepo, $prefRepo);
        $handler($this->makeMessage());
    }
}
