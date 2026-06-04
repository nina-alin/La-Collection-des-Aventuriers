<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Book;
use App\Entity\CorrectionProposal;
use App\Entity\Enum\BookStatus;
use App\Entity\Enum\SuggestionStatus;
use App\Entity\ModerationLog;
use App\Entity\Suggestion;
use App\Entity\User;
use App\Entity\WorkEntry;
use App\Event\BookPublishedEvent;
use App\Event\ContributionValidatedEvent;
use App\Event\SuggestionModeratedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ModerationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function approve(WorkEntry|CorrectionProposal $entity, string $moderatorId): void
    {
        if ($entity->getStatus() !== 'PENDING') {
            throw new \InvalidArgumentException('Entity must be in PENDING status to approve.');
        }

        $entity->setStatus('PUBLISHED');

        $log = new ModerationLog(
            $moderatorId,
            'APPROVED',
            $entity instanceof WorkEntry ? 'WorkEntry' : 'CorrectionProposal',
            (string) $entity->getId(),
        );
        $this->entityManager->persist($log);
        $this->entityManager->flush();

        if ($entity instanceof WorkEntry && $entity->getAuthor() !== null) {
            $this->dispatcher->dispatch(new ContributionValidatedEvent($entity, $entity->getAuthor()));
        }
    }

    public function reject(WorkEntry|CorrectionProposal $entity, string $moderatorId, ?string $reason): void
    {
        if ($entity->getStatus() !== 'PENDING') {
            throw new \InvalidArgumentException('Entity must be in PENDING status to reject.');
        }

        $entity->setStatus('REJECTED');

        $log = new ModerationLog(
            $moderatorId,
            'REJECTED',
            $entity instanceof WorkEntry ? 'WorkEntry' : 'CorrectionProposal',
            (string) $entity->getId(),
            $reason ?: null,
        );
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function editPendingWorkEntry(WorkEntry $entity, string $title, string $moderatorId): void
    {
        if ($entity->getStatus() !== 'PENDING') {
            throw new \InvalidArgumentException('WorkEntry must be in PENDING status to edit.');
        }

        $entity->setTitle($title);

        $log = new ModerationLog($moderatorId, 'MODIFIED', 'WorkEntry', (string) $entity->getId());
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function editPendingCorrection(CorrectionProposal $entity, string $proposedContent, string $moderatorId): void
    {
        if ($entity->getStatus() !== 'PENDING') {
            throw new \InvalidArgumentException('CorrectionProposal must be in PENDING status to edit.');
        }

        $entity->setProposedContent(['content' => $proposedContent]);

        $log = new ModerationLog($moderatorId, 'MODIFIED', 'CorrectionProposal', (string) $entity->getId());
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function publishBook(User $moderator, Book $book): void
    {
        $book->setStatus(BookStatus::PUBLISHED);
        $this->entityManager->flush();
        $this->dispatcher->dispatch(new BookPublishedEvent($moderator, $book));
    }

    public function moderateSuggestion(User $moderator, Suggestion $suggestion, SuggestionStatus $newStatus): void
    {
        $suggestion->setStatus($newStatus);
        $this->entityManager->flush();
        $this->dispatcher->dispatch(new SuggestionModeratedEvent($moderator, $suggestion, $newStatus));
    }
}
