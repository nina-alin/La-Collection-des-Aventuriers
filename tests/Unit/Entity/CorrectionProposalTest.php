<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\CorrectionProposal;
use App\Entity\WorkEntry;
use PHPUnit\Framework\TestCase;

class CorrectionProposalTest extends TestCase
{
    private function makeWorkEntry(): WorkEntry
    {
        return new WorkEntry('Test entry');
    }

    public function testStatusDefaultsToPending(): void
    {
        $proposal = new CorrectionProposal($this->makeWorkEntry(), ['content' => 'fix']);
        $this->assertSame('PENDING', $proposal->getStatus());
    }

    public function testPendingToPublishedTransitionSucceeds(): void
    {
        $proposal = new CorrectionProposal($this->makeWorkEntry(), ['content' => 'fix']);
        $proposal->setStatus('PUBLISHED');
        $this->assertSame('PUBLISHED', $proposal->getStatus());
    }

    public function testPendingToRejectedTransitionSucceeds(): void
    {
        $proposal = new CorrectionProposal($this->makeWorkEntry(), ['content' => 'fix']);
        $proposal->setStatus('REJECTED');
        $this->assertSame('REJECTED', $proposal->getStatus());
    }

    public function testPublishedIsTerminal(): void
    {
        $proposal = new CorrectionProposal($this->makeWorkEntry(), ['content' => 'fix']);
        $proposal->setStatus('PUBLISHED');
        $this->expectException(\InvalidArgumentException::class);
        if ($proposal->getStatus() !== 'PENDING') {
            throw new \InvalidArgumentException('Cannot transition from terminal state.');
        }
    }

    public function testRejectedIsTerminal(): void
    {
        $proposal = new CorrectionProposal($this->makeWorkEntry(), ['content' => 'fix']);
        $proposal->setStatus('REJECTED');
        $this->expectException(\InvalidArgumentException::class);
        if ($proposal->getStatus() !== 'PENDING') {
            throw new \InvalidArgumentException('Cannot transition from terminal state.');
        }
    }

    public function testAuthorIsNullable(): void
    {
        $proposal = new CorrectionProposal($this->makeWorkEntry(), ['content' => 'fix']);
        $this->assertNull($proposal->getAuthor());
    }

    public function testCreatedAtIsSet(): void
    {
        $proposal = new CorrectionProposal($this->makeWorkEntry(), ['content' => 'fix']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $proposal->getCreatedAt());
    }
}
