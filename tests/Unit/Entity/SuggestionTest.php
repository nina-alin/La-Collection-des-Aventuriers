<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Enum\SuggestionEntityType;
use App\Entity\Enum\SuggestionMode;
use App\Entity\Enum\SuggestionStatus;
use App\Entity\Suggestion;
use PHPUnit\Framework\TestCase;

class SuggestionTest extends TestCase
{
    public function testDefaultStatusIsPending(): void
    {
        $suggestion = new Suggestion();
        $this->assertSame(SuggestionStatus::PENDING, $suggestion->getStatus());
    }

    public function testSubmittedAtIsSetOnConstruct(): void
    {
        $before = new \DateTimeImmutable();
        $suggestion = new Suggestion();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $suggestion->getSubmittedAt());
        $this->assertLessThanOrEqual($after, $suggestion->getSubmittedAt());
    }

    public function testIdIsAssignedOnConstruct(): void
    {
        $suggestion = new Suggestion();
        $this->assertNotNull($suggestion->getId());
    }

    public function testEntityTypeEnumField(): void
    {
        $suggestion = new Suggestion();
        $suggestion->setEntityType(SuggestionEntityType::BOOK);
        $this->assertSame(SuggestionEntityType::BOOK, $suggestion->getEntityType());
    }

    public function testModeEnumField(): void
    {
        $suggestion = new Suggestion();
        $suggestion->setMode(SuggestionMode::NEW_ENTRY);
        $this->assertSame(SuggestionMode::NEW_ENTRY, $suggestion->getMode());
    }

    public function testNullableFields(): void
    {
        $suggestion = new Suggestion();
        $this->assertNull($suggestion->getSourceEntityId());
        $this->assertNull($suggestion->getSourceEntityType());
        $this->assertNull($suggestion->getCoverImagePath());
        $this->assertNull($suggestion->getRefusal());
    }

    public function testFormDataDefaultIsEmptyArray(): void
    {
        $suggestion = new Suggestion();
        $this->assertSame([], $suggestion->getFormData());
    }
}
