<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Review;
use PHPUnit\Framework\TestCase;

class ReviewTest extends TestCase
{
    public function testScoreOneIsValid(): void
    {
        $review = new Review();
        $review->setScore(1);
        $this->assertSame(1, $review->getScore());
    }

    public function testScoreTenIsValid(): void
    {
        $review = new Review();
        $review->setScore(10);
        $this->assertSame(10, $review->getScore());
    }

    public function testSetCommentEmptyStringNormalizesToNull(): void
    {
        $review = new Review();
        $review->setComment('');
        $this->assertNull($review->getComment());
    }

    public function testSetCommentNullStaysNull(): void
    {
        $review = new Review();
        $review->setComment(null);
        $this->assertNull($review->getComment());
    }

    public function testSetCommentNonEmptyStringKept(): void
    {
        $review = new Review();
        $review->setComment('Great book!');
        $this->assertSame('Great book!', $review->getComment());
    }

    public function testCreatedAtSetOnConstruction(): void
    {
        $review = new Review();
        $this->assertInstanceOf(\DateTimeImmutable::class, $review->getCreatedAt());
    }

    public function testUpdatedAtSetOnConstruction(): void
    {
        $review = new Review();
        $this->assertInstanceOf(\DateTimeImmutable::class, $review->getUpdatedAt());
    }

    public function testCreatedAtIsUtc(): void
    {
        $review = new Review();
        $this->assertSame('UTC', $review->getCreatedAt()->getTimezone()->getName());
    }

    public function testUpdatedAtIsUtc(): void
    {
        $review = new Review();
        $this->assertSame('UTC', $review->getUpdatedAt()->getTimezone()->getName());
    }

    public function testOnPrePersistSetsTimestamps(): void
    {
        $review = new Review();
        $before = new \DateTimeImmutable('-1 second');
        $review->onPrePersist();
        $this->assertGreaterThanOrEqual($before, $review->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $review->getUpdatedAt());
    }

    public function testOnPreUpdateSetsUpdatedAt(): void
    {
        $review = new Review();
        $original = $review->getUpdatedAt();
        usleep(1000);
        $review->onPreUpdate();
        $this->assertGreaterThanOrEqual($original, $review->getUpdatedAt());
    }
}
