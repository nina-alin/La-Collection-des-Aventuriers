<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Suggestion;
use App\Entity\SuggestionRefusal;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class SuggestionRefusalTest extends TestCase
{
    public function testDefaultActionsIsEmptyArray(): void
    {
        $refusal = new SuggestionRefusal();
        $this->assertSame([], $refusal->getActions());
    }

    public function testRefusedAtIsSetOnConstruct(): void
    {
        $before = new \DateTimeImmutable();
        $refusal = new SuggestionRefusal();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $refusal->getRefusedAt());
        $this->assertLessThanOrEqual($after, $refusal->getRefusedAt());
    }

    public function testReasonCanBeSet(): void
    {
        $refusal = new SuggestionRefusal();
        $refusal->setReason('Doublon existant');
        $this->assertSame('Doublon existant', $refusal->getReason());
    }

    public function testSuggestionAssociationTyped(): void
    {
        $suggestion = $this->createMock(Suggestion::class);
        $refusal = new SuggestionRefusal();
        $refusal->setSuggestion($suggestion);
        $this->assertSame($suggestion, $refusal->getSuggestion());
    }

    public function testModeratorIsNullableAndCanBeSet(): void
    {
        $refusal = new SuggestionRefusal();
        $this->assertNull($refusal->getModerator());

        $user = $this->createMock(User::class);
        $refusal->setModerator($user);
        $this->assertSame($user, $refusal->getModerator());

        $refusal->setModerator(null);
        $this->assertNull($refusal->getModerator());
    }

    public function testActionsCanBeSet(): void
    {
        $refusal = new SuggestionRefusal();
        $refusal->setActions(['VOIR_FICHE', 'MASQUER']);
        $this->assertSame(['VOIR_FICHE', 'MASQUER'], $refusal->getActions());
    }
}
