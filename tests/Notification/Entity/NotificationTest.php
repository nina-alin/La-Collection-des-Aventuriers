<?php

namespace App\Tests\Notification\Entity;

use App\Entity\Enum\NotificationType;
use App\Entity\Notification;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    private function makeUser(): User
    {
        return $this->createMock(User::class);
    }

    public function testConstructorSetsAllFields(): void
    {
        $user = $this->makeUser();
        $n = new Notification($user, NotificationType::RANK_UP, 'Félicitations !', 'rank_up:user1:level2', '/profile');

        $this->assertSame($user, $n->getUser());
        $this->assertSame(NotificationType::RANK_UP, $n->getType());
        $this->assertSame('Félicitations !', $n->getMessage());
        $this->assertSame('rank_up:user1:level2', $n->getSourceId());
        $this->assertSame('/profile', $n->getTargetUrl());
    }

    public function testIsReadDefaultsFalse(): void
    {
        $n = new Notification($this->makeUser(), NotificationType::RANK_UP, 'msg', 'src');
        $this->assertFalse($n->isRead());
    }

    public function testMarkReadSetsTrue(): void
    {
        $n = new Notification($this->makeUser(), NotificationType::RANK_UP, 'msg', 'src');
        $n->markRead();
        $this->assertTrue($n->isRead());
    }

    public function testCreatedAtIsUtcDateTimeImmutable(): void
    {
        $n = new Notification($this->makeUser(), NotificationType::CONTRIBUTION_VALIDATED, 'msg', 'src');
        $this->assertInstanceOf(\DateTimeImmutable::class, $n->getCreatedAt());
        $this->assertSame('UTC', $n->getCreatedAt()->getTimezone()->getName());
    }

    public function testTargetUrlNullableDefault(): void
    {
        $n = new Notification($this->makeUser(), NotificationType::BOOK_ACTIVITY, 'msg', 'book_activity:col1:book1');
        $this->assertNull($n->getTargetUrl());
    }

    public function testSourceIdFormat(): void
    {
        $sourceId = 'contribution_validated:018b3c2d-0000-0000-0000-000000000000';
        $n = new Notification($this->makeUser(), NotificationType::CONTRIBUTION_VALIDATED, 'msg', $sourceId);
        $this->assertSame($sourceId, $n->getSourceId());
    }
}
