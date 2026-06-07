<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\CorrectionProposal;
use App\Entity\Enum\SuggestionStatus;
use App\Entity\ModerationLog;
use App\Entity\Suggestion;
use App\Entity\User;
use App\Entity\WorkEntry;
use App\Event\ContributionValidatedEvent;
use App\Event\SuggestionModeratedEvent;
use App\Service\ModerationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

class ModerationServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private EventDispatcherInterface&MockObject $dispatcher;
    private ModerationService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->service = new ModerationService($this->em, $this->dispatcher);
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

    public function testApproveWorkEntryDispatchesContributionValidatedEvent(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(Uuid::v4());

        $entry = new WorkEntry('Mon livre', $user);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ContributionValidatedEvent::class));

        $this->service->approve($entry, 'mod-uuid');
    }

    public function testApproveCorrectionProposalDispatchesContributionValidatedEvent(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(Uuid::v4());

        $workEntry = new WorkEntry('Le grand livre');
        $proposal = new CorrectionProposal($workEntry, ['content' => 'fix'], $user);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ContributionValidatedEvent::class));

        $this->service->approve($proposal, 'mod-uuid');
    }

    public function testApproveWithNullAuthorDoesNotDispatch(): void
    {
        $entry = new WorkEntry('Mon livre', null);

        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->service->approve($entry, 'mod-uuid');
    }

    public function testModerateSuggestionDispatchesContributionValidatedEventOnValidated(): void
    {
        $moderator = $this->createMock(User::class);
        $moderator->method('getId')->willReturn(Uuid::v4());

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(Uuid::v4());

        $suggestion = new Suggestion();
        $suggestion->setUser($user);

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $this->service->moderateSuggestion($moderator, $suggestion, SuggestionStatus::VALIDATED);
    }

    public function testModerateSuggestionDoesNotDispatchOnRefused(): void
    {
        $moderator = $this->createMock(User::class);
        $moderator->method('getId')->willReturn(Uuid::v4());

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(Uuid::v4());

        $suggestion = new Suggestion();
        $suggestion->setUser($user);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SuggestionModeratedEvent::class));

        $this->service->moderateSuggestion($moderator, $suggestion, SuggestionStatus::REFUSED);
    }
}
