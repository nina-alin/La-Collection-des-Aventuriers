<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

class UserTest extends TestCase
{
    public function testIdIsUuidV4OnConstruct(): void
    {
        $user = new User();
        $this->assertInstanceOf(UuidV4::class, $user->getId());
    }

    public function testCreatedAtIsUtcDateTimeImmutable(): void
    {
        $user = new User();
        $createdAt = $user->getCreatedAt();
        $this->assertInstanceOf(\DateTimeImmutable::class, $createdAt);
        $this->assertSame('UTC', $createdAt->getTimezone()->getName());
    }

    public function testGetRolesAlwaysContainsRoleUser(): void
    {
        $user = new User();
        $user->setRoles([]);
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testGetRolesContainsRoleUserEvenWithEmptyRoles(): void
    {
        $user = new User();
        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testEmailSetterLowercases(): void
    {
        $user = new User();
        $user->setEmail('Test@Example.COM');
        $this->assertSame('test@example.com', $user->getEmail());
    }

    public function testGetPasswordIsNullableInitially(): void
    {
        $user = new User();
        $this->assertNull($user->getPassword());
    }

    public function testSetPasswordAcceptsNull(): void
    {
        $user = new User();
        $user->setPassword(null);
        $this->assertNull($user->getPassword());
    }

    public function testEraseCredentialsIsNoop(): void
    {
        $user = new User();
        $user->setPassword('hashed');
        $user->eraseCredentials();
        $this->assertSame('hashed', $user->getPassword());
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $this->assertSame('user@example.com', $user->getUserIdentifier());
    }

    public function testEachUserHasUniqueId(): void
    {
        $user1 = new User();
        $user2 = new User();
        $this->assertNotSame((string) $user1->getId(), (string) $user2->getId());
    }
}
