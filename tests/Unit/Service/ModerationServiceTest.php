<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\CorrectionProposal;
use App\Entity\ModerationLog;
use App\Entity\WorkEntry;
use App\Service\ModerationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ModerationServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ModerationService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->service = new ModerationService($this->em);
    }

    public function testApproveWorkEntryTransitionsToPublishedAndPersistsLog(): void
    {
        $entry = new WorkEntry('Test');
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->service->approve($entry, 'mod-uuid');

        $this->assertSame('PUBLISHED', $entry->getStatus());
    }

    public function testRejectWorkEntryTransitionsToRejectedAndStoresReason(): void
    {
        $entry = new WorkEntry('Test');
        $this->em->expects($this->once())->method('flush');

        $this->service->reject($entry, 'mod-uuid', 'spam');

        $this->assertSame('REJECTED', $entry->getStatus());
    }

    public function testEditPendingWorkEntryUpdatesTitle(): void
    {
        $entry = new WorkEntry('Old title');
        $this->em->expects($this->once())->method('flush');

        $this->service->editPendingWorkEntry($entry, 'New title', 'mod-uuid');

        $this->assertSame('New title', $entry->getTitle());
        $this->assertSame('PENDING', $entry->getStatus());
    }

    public function testEditPendingCorrectionUpdatesContent(): void
    {
        $entry = new WorkEntry('Entry');
        $proposal = new CorrectionProposal($entry, ['content' => 'old']);
        $this->em->expects($this->once())->method('flush');

        $this->service->editPendingCorrection($proposal, 'new content', 'mod-uuid');

        $this->assertSame('PENDING', $proposal->getStatus());
    }

    public function testApproveNonPendingThrows(): void
    {
        $entry = new WorkEntry('Test');
        $entry->setStatus('PUBLISHED');

        $this->expectException(\InvalidArgumentException::class);
        $this->service->approve($entry, 'mod-uuid');
    }

    public function testRejectNonPendingThrows(): void
    {
        $entry = new WorkEntry('Test');
        $entry->setStatus('REJECTED');

        $this->expectException(\InvalidArgumentException::class);
        $this->service->reject($entry, 'mod-uuid', null);
    }

    public function testEditPendingWorkEntryNonPendingThrows(): void
    {
        $entry = new WorkEntry('Test');
        $entry->setStatus('PUBLISHED');

        $this->expectException(\InvalidArgumentException::class);
        $this->service->editPendingWorkEntry($entry, 'New', 'mod-uuid');
    }

    public function testEditPendingCorrectionNonPendingThrows(): void
    {
        $entry = new WorkEntry('Entry');
        $proposal = new CorrectionProposal($entry, ['content' => 'x']);
        $proposal->setStatus('REJECTED');

        $this->expectException(\InvalidArgumentException::class);
        $this->service->editPendingCorrection($proposal, 'new', 'mod-uuid');
    }

    public function testApproveCorrectionProposal(): void
    {
        $entry = new WorkEntry('Entry');
        $proposal = new CorrectionProposal($entry, ['content' => 'fix']);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->service->approve($proposal, 'mod-uuid');

        $this->assertSame('PUBLISHED', $proposal->getStatus());
    }
}
