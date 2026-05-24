<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserManagementService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserManagementServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private UserRepository&MockObject $userRepo;
    private UserManagementService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->userRepo = $this->createMock(UserRepository::class);
        $this->service = new UserManagementService($this->em, $this->userRepo);
    }

    private function makeUser(array $roles = ['ROLE_USER'], string $status = 'active'): User
    {
        $user = new User();
        $user->setEmail(uniqid().'@example.com');
        $user->setPseudo(uniqid());
        $user->setRoles($roles);
        $user->setStatus($status);
        return $user;
    }

    public function testChangeRoleThrowsWhenActorEqualsTarget(): void
    {
        $actor = $this->makeUser(['ROLE_ADMIN']);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->changeRole($actor, $actor, 'ROLE_USER');
    }

    public function testBanUserThrowsWhenActorEqualsTarget(): void
    {
        $actor = $this->makeUser(['ROLE_ADMIN']);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->banUser($actor, $actor);
    }

    public function testSoftDeleteThrowsWhenActorEqualsTarget(): void
    {
        $actor = $this->makeUser(['ROLE_ADMIN']);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->softDeleteUser($actor, $actor);
    }

    public function testChangeRoleDemotingLastAdminThrows(): void
    {
        $actor = $this->makeUser(['ROLE_ADMIN']);
        $target = $this->makeUser(['ROLE_ADMIN']);
        $this->userRepo->method('countActiveAdministrators')->willReturn(1);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->changeRole($actor, $target, 'ROLE_USER');
    }

    public function testBanLastAdminThrows(): void
    {
        $actor = $this->makeUser(['ROLE_ADMIN']);
        $target = $this->makeUser(['ROLE_ADMIN']);
        $this->userRepo->method('countActiveAdministrators')->willReturn(1);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->banUser($actor, $target);
    }

    public function testSoftDeleteLastAdminThrows(): void
    {
        $actor = $this->makeUser(['ROLE_ADMIN']);
        $target = $this->makeUser(['ROLE_ADMIN']);
        $this->userRepo->method('countActiveAdministrators')->willReturn(1);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->softDeleteUser($actor, $target);
    }

    public function testChangeRoleDemotingLastModeratorThrows(): void
    {
        $actor = $this->makeUser(['ROLE_ADMIN']);
        $target = $this->makeUser(['ROLE_MODERATOR']);
        $this->userRepo->method('countActiveAdministrators')->willReturn(0);
        $this->userRepo->method('countAccountsWithModerationCapability')->willReturn(1);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->changeRole($actor, $target, 'ROLE_USER');
    }

    public function testSoftDeleteSetsDeletedAtAndAnonymizesUser(): void
    {
        $actor = $this->makeUser(['ROLE_ADMIN']);
        $target = $this->makeUser(['ROLE_USER']);
        $this->userRepo->method('countActiveAdministrators')->willReturn(2);

        $conn = $this->createMock(Connection::class);
        $conn->expects($this->exactly(2))->method('executeStatement');
        $this->em->method('getConnection')->willReturn($conn);
        $this->em->expects($this->once())->method('flush');

        $this->service->softDeleteUser($actor, $target);

        $this->assertSame('[deleted]', $target->getEmail());
        $this->assertSame('[deleted]', $target->getDisplayName());
        $this->assertNotNull($target->getDeletedAt());
    }

    public function testChangeRoleReplacesRolesWithSingleElement(): void
    {
        $actor = $this->makeUser(['ROLE_ADMIN']);
        $target = $this->makeUser(['ROLE_USER']);
        $this->userRepo->method('countActiveAdministrators')->willReturn(2);
        $this->em->expects($this->once())->method('flush');

        $this->service->changeRole($actor, $target, 'ROLE_MODERATOR');

        $this->assertContains('ROLE_MODERATOR', $target->getRoles());
    }
}
