<?php

namespace App\Tests\Notification\Entity;

use App\Entity\Enum\NotificationType;
use App\Entity\NotificationPreference;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class NotificationPreferenceTest extends TestCase
{
    private function makePreference(): NotificationPreference
    {
        return new NotificationPreference($this->createMock(User::class));
    }

    public function testAllFourTypesDefaultEnabled(): void
    {
        $pref = $this->makePreference();

        $this->assertTrue($pref->isEnabled(NotificationType::CONTRIBUTION_VALIDATED));
        $this->assertTrue($pref->isEnabled(NotificationType::BOOK_ACTIVITY));
        $this->assertTrue($pref->isEnabled(NotificationType::MODERATION_PENDING));
        $this->assertTrue($pref->isEnabled(NotificationType::RANK_UP));
    }

    public function testIsEnabledMatchReturnsCorrectBooleanPerType(): void
    {
        $pref = $this->makePreference();
        $pref->setRankUp(false);

        $this->assertTrue($pref->isEnabled(NotificationType::CONTRIBUTION_VALIDATED));
        $this->assertTrue($pref->isEnabled(NotificationType::BOOK_ACTIVITY));
        $this->assertTrue($pref->isEnabled(NotificationType::MODERATION_PENDING));
        $this->assertFalse($pref->isEnabled(NotificationType::RANK_UP));
    }

    public function testDisablingOneTypeDoesNotAffectOthers(): void
    {
        $pref = $this->makePreference();
        $pref->setBookActivity(false);

        $this->assertTrue($pref->isEnabled(NotificationType::CONTRIBUTION_VALIDATED));
        $this->assertFalse($pref->isEnabled(NotificationType::BOOK_ACTIVITY));
        $this->assertTrue($pref->isEnabled(NotificationType::MODERATION_PENDING));
        $this->assertTrue($pref->isEnabled(NotificationType::RANK_UP));
    }
}
