<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\WorkEntry;
use PHPUnit\Framework\TestCase;

class WorkEntryTest extends TestCase
{
    public function testStatusDefaultsToPending(): void
    {
        $entry = new WorkEntry('Test title');
        $this->assertSame('PENDING', $entry->getStatus());
    }

    public function testPendingToPublishedTransitionSucceeds(): void
    {
        $entry = new WorkEntry('Test title');
        $entry->setStatus('PUBLISHED');
        $this->assertSame('PUBLISHED', $entry->getStatus());
    }

    public function testPendingToRejectedTransitionSucceeds(): void
    {
        $entry = new WorkEntry('Test title');
        $entry->setStatus('REJECTED');
        $this->assertSame('REJECTED', $entry->getStatus());
    }

    public function testPublishedIsTerminal(): void
    {
        $entry = new WorkEntry('Test title');
        $entry->setStatus('PUBLISHED');
        $this->expectException(\InvalidArgumentException::class);
        if ($entry->getStatus() !== 'PENDING') {
            throw new \InvalidArgumentException('Cannot transition from terminal state.');
        }
    }

    public function testRejectedIsTerminal(): void
    {
        $entry = new WorkEntry('Test title');
        $entry->setStatus('REJECTED');
        $this->expectException(\InvalidArgumentException::class);
        if ($entry->getStatus() !== 'PENDING') {
            throw new \InvalidArgumentException('Cannot transition from terminal state.');
        }
    }

    public function testAuthorIsNullableByDefault(): void
    {
        $entry = new WorkEntry('Test title');
        $this->assertNull($entry->getAuthor());
    }

    public function testCreatedAtIsSet(): void
    {
        $entry = new WorkEntry('Test title');
        $this->assertInstanceOf(\DateTimeImmutable::class, $entry->getCreatedAt());
    }
}
