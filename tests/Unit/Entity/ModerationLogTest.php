<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\ModerationLog;
use PHPUnit\Framework\TestCase;

class ModerationLogTest extends TestCase
{
    public function testCreatedAtSetInConstructor(): void
    {
        $log = new ModerationLog('mod-id', 'APPROVED', 'WorkEntry', 'entry-id');
        $this->assertInstanceOf(\DateTimeImmutable::class, $log->getCreatedAt());
    }

    public function testPreUpdateThrowsLogicException(): void
    {
        $log = new ModerationLog('mod-id', 'APPROVED', 'WorkEntry', 'entry-id');
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('ModerationLog is append-only');
        $log->onPreUpdate();
    }

    public function testPreRemoveThrowsLogicException(): void
    {
        $log = new ModerationLog('mod-id', 'APPROVED', 'WorkEntry', 'entry-id');
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('ModerationLog is append-only');
        $log->onPreRemove();
    }

    public function testReasonIsNullable(): void
    {
        $log = new ModerationLog('mod-id', 'REJECTED', 'WorkEntry', 'entry-id', null);
        $this->assertNull($log->getReason());
    }

    public function testReasonIsStored(): void
    {
        $log = new ModerationLog('mod-id', 'REJECTED', 'WorkEntry', 'entry-id', 'spam content');
        $this->assertSame('spam content', $log->getReason());
    }
}
