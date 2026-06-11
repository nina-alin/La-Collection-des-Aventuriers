<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\GhostUser;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AccountDeletionService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AccountDeletionServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private UserRepository&MockObject $userRepository;
    private AccountDeletionService $service;

    protected function setUp(): void
    {
        $this->em             = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->service        = new AccountDeletionService($this->em, $this->userRepository);
    }

    public function testGhostUserGuardThrowsException(): void
    {
        $ghostUser = new User();
        $ghostUser->setEmail(GhostUser::GHOST_EMAIL);

        $this->expectException(\LogicException::class);
        $this->service->delete($ghostUser);
    }

    public function testUserFieldsAnonymisedWithDeletedPattern(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPseudo('testuser');
        $user->setDisplayName('Test User');
        $user->setAvatarUrl('/uploads/avatars/test.jpg');
        $user->setGoogleId('google-123');
        $user->setPassword('hashed-password');
        $user->setPendingEmail('new@example.com');
        $user->setEmailChangeToken('token123');

        $ghostUserEntity = new User();
        $ghostUserEntity->setEmail(GhostUser::GHOST_EMAIL);

        $this->userRepository
            ->method('findOneByEmail')
            ->with(GhostUser::GHOST_EMAIL)
            ->willReturn($ghostUserEntity);

        $query = $this->createMock(Query::class);
        $query->method('setParameter')->willReturnSelf();
        $query->method('execute');

        $this->em->method('createQuery')->willReturn($query);
        $this->em->expects($this->atLeastOnce())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->service->delete($user);

        $this->assertStringStartsWith('[deleted]-', $user->getEmail());
        $this->assertStringStartsWith('[deleted]-', $user->getPseudo());
        $this->assertSame('[deleted]', $user->getDisplayName());
        $this->assertNull($user->getAvatarUrl());
        $this->assertNull($user->getGoogleId());
        $this->assertNull($user->getPassword());
        $this->assertNull($user->getPendingEmail());
        $this->assertNull($user->getEmailChangeToken());
        $this->assertNotNull($user->getDeletedAt());
    }

    public function testModerationLogCreatedWithNullModeratorId(): void
    {
        $user = new User();
        $user->setEmail('todelete@example.com');
        $user->setPseudo('todelete');

        $ghostUserEntity = new User();
        $ghostUserEntity->setEmail(GhostUser::GHOST_EMAIL);

        $this->userRepository
            ->method('findOneByEmail')
            ->with(GhostUser::GHOST_EMAIL)
            ->willReturn($ghostUserEntity);

        $query = $this->createMock(Query::class);
        $query->method('setParameter')->willReturnSelf();
        $query->method('execute');

        $this->em->method('createQuery')->willReturn($query);

        $persistedEntities = [];
        $this->em
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistedEntities) {
                $persistedEntities[] = $entity;
            });
        $this->em->expects($this->once())->method('flush');

        $this->service->delete($user);

        $moderationLogs = array_filter($persistedEntities, fn ($e) => $e instanceof \App\Entity\ModerationLog);
        $this->assertCount(1, $moderationLogs);
        $log = reset($moderationLogs);
        $this->assertNull($log->getModeratorId());
        $this->assertSame('ACCOUNT_DELETED', $log->getActionType());
    }

    public function testThrowsIfGhostUserNotFound(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPseudo('test');

        $this->userRepository
            ->method('findOneByEmail')
            ->with(GhostUser::GHOST_EMAIL)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->delete($user);
    }
}
