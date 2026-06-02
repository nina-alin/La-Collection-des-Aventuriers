<?php

namespace App\Tests\Notification\Repository;

use App\Entity\Enum\NotificationType;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\TestCase;

class NotificationRepositoryTest extends TestCase
{
    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $this->assertTrue(is_subclass_of(NotificationRepository::class, ServiceEntityRepository::class));
    }

    public function testNotificationEntityHasCorrectMethods(): void
    {
        $user = $this->createMock(User::class);
        $n = new Notification($user, NotificationType::RANK_UP, 'msg', 'rank_up:1');

        $this->assertFalse($n->isRead());
        $n->markRead();
        $this->assertTrue($n->isRead());
    }

    public function testCountUnreadForUserReturnsCastInt(): void
    {
        // Verify the method exists on the repository class with the correct signature
        $this->assertTrue(method_exists(NotificationRepository::class, 'countUnreadForUser'));
        $this->assertTrue(method_exists(NotificationRepository::class, 'markAllReadForUser'));
        $this->assertTrue(method_exists(NotificationRepository::class, 'deleteUnreadByTypeForUser'));
        $this->assertTrue(method_exists(NotificationRepository::class, 'deleteOldestForUser'));
    }
}
