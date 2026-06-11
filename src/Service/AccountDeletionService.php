<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\GhostUser;
use App\Entity\ModerationLog;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class AccountDeletionService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function delete(User $user): void
    {
        if ($user->getEmail() === GhostUser::GHOST_EMAIL) {
            throw new \LogicException('GhostUser cannot be deleted.');
        }

        $ghostUser = $this->userRepository->findOneByEmail(GhostUser::GHOST_EMAIL);
        if ($ghostUser === null) {
            throw new \RuntimeException('GhostUser not found in database. Run migrations first.');
        }

        $userId = (string) $user->getId();

        // Reassign VALIDATED Suggestions to GhostUser
        $this->em->createQuery(
            'UPDATE App\Entity\Suggestion s SET s.user = :ghost WHERE s.user = :user AND s.status = :status'
        )
            ->setParameter('ghost', $ghostUser)
            ->setParameter('user', $user)
            ->setParameter('status', \App\Entity\Enum\SuggestionStatus::VALIDATED)
            ->execute();

        // Reassign PUBLISHED CorrectionProposals to GhostUser
        $this->em->createQuery(
            "UPDATE App\Entity\CorrectionProposal c SET c.author = :ghost WHERE c.author = :user AND c.status = 'PUBLISHED'"
        )
            ->setParameter('ghost', $ghostUser)
            ->setParameter('user', $user)
            ->execute();

        // Delete UserBook rows
        $this->em->createQuery('DELETE FROM App\Entity\UserBook ub WHERE ub.user = :user')
            ->setParameter('user', $user)
            ->execute();

        // Delete Review rows
        $this->em->createQuery('DELETE FROM App\Entity\Review r WHERE r.user = :user')
            ->setParameter('user', $user)
            ->execute();

        // Anonymise User fields
        $user->setEmail('[deleted]-' . $userId);
        $user->setPseudo('[deleted]-' . $userId);
        $user->setDisplayName('[deleted]');
        $user->setAvatarUrl(null);
        $user->setGoogleId(null);
        $user->setPassword(null);
        $user->setPendingEmail(null);
        $user->setEmailChangeToken(null);
        $user->setEmailTokenExpiresAt(null);
        $user->setDeletedAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));

        // Log deletion
        $log = new ModerationLog(
            moderatorId: null,
            actionType: 'ACCOUNT_DELETED',
            targetEntityType: 'User',
            targetEntityId: $userId,
        );
        $this->em->persist($log);

        $this->em->flush();
    }
}
