<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserManagementService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function changeRole(User $actor, User $target, string $newRole): void
    {
        if ($actor === $target) {
            throw new \InvalidArgumentException('Vous ne pouvez pas modifier votre propre rôle.');
        }

        $currentRole = $target->getRoles();
        $isDemotingAdmin = in_array('ROLE_ADMIN', $currentRole, true) && $newRole !== 'ROLE_ADMIN';
        $isDemotingModerator = in_array('ROLE_MODERATOR', $currentRole, true) && !in_array('ROLE_ADMIN', $currentRole, true) && $newRole === 'ROLE_USER';

        if ($isDemotingAdmin && $this->userRepository->countActiveAdministrators() <= 1) {
            throw new \InvalidArgumentException('Cette action laisserait la plateforme sans administrateur actif.');
        }

        if ($isDemotingModerator && $this->userRepository->countAccountsWithModerationCapability() <= 1) {
            throw new \InvalidArgumentException('Cette action laisserait la plateforme sans modérateur actif.');
        }

        $target->setRoles([$newRole]);
        $this->entityManager->flush();
    }

    public function banUser(User $actor, User $target): void
    {
        if ($actor === $target) {
            throw new \InvalidArgumentException('Vous ne pouvez pas suspendre votre propre compte.');
        }

        $targetRoles = $target->getRoles();
        if (in_array('ROLE_ADMIN', $targetRoles, true) && $this->userRepository->countActiveAdministrators() <= 1) {
            throw new \InvalidArgumentException('Cette action laisserait la plateforme sans administrateur actif.');
        }

        $target->setStatus('banned');
        $this->entityManager->flush();
    }

    public function softDeleteUser(User $actor, User $target): void
    {
        if ($actor === $target) {
            throw new \InvalidArgumentException('Vous ne pouvez pas supprimer votre propre compte.');
        }

        $targetRoles = $target->getRoles();
        if (in_array('ROLE_ADMIN', $targetRoles, true) && $this->userRepository->countActiveAdministrators() <= 1) {
            throw new \InvalidArgumentException('Cette action laisserait la plateforme sans administrateur actif.');
        }

        $conn = $this->entityManager->getConnection();
        $userId = (string) $target->getId();

        $conn->executeStatement(
            'UPDATE work_entry SET author_id = NULL WHERE author_id = :id',
            ['id' => $userId],
        );
        $conn->executeStatement(
            'UPDATE correction_proposal SET author_id = NULL WHERE author_id = :id',
            ['id' => $userId],
        );

        $target->setEmail('[deleted]');
        $target->setDisplayName('[deleted]');
        $target->setDeletedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }
}
