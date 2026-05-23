<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\EventSubscriber\AuthenticationEventSubscriber;
use App\Repository\UserRepository;
use App\Service\UserRegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistrationServiceTest extends TestCase
{
    private UserRepository $repository;
    private UserPasswordHasherInterface $hasher;
    private EntityManagerInterface $em;
    private AuthenticationEventSubscriber $subscriber;
    private UserRegistrationService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepository::class);
        $this->hasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->subscriber = $this->createMock(AuthenticationEventSubscriber::class);

        $this->service = new UserRegistrationService(
            $this->repository,
            $this->hasher,
            $this->em,
            $this->subscriber,
        );
    }

    public function testValidRegistrationCreatesUserWithRoleUser(): void
    {
        $this->repository->method('findOneByEmail')->willReturn(null);
        $this->repository->method('isEmailTaken')->willReturn(false);
        $this->repository->method('isPseudoTaken')->willReturn(false);
        $this->hasher->method('hashPassword')->willReturn('$2y$13$hashedpassword');
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $user = $this->service->register('pseudo123', 'user@example.com', 'password123');

        $this->assertInstanceOf(User::class, $user);
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertSame('user@example.com', $user->getEmail());
    }

    public function testDuplicateEmailThrows(): void
    {
        $existingUser = new User();
        $existingUser->setEmail('existing@example.com');
        $existingUser->setPassword('$2y$13$existinghash');

        $this->repository->method('findOneByEmail')->willReturn($existingUser);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cette adresse email est déjà associée à un compte.');

        $this->service->register('newpseudo', 'existing@example.com', 'password123');
    }

    public function testDuplicatePseudoThrows(): void
    {
        $this->repository->method('findOneByEmail')->willReturn(null);
        $this->repository->method('isEmailTaken')->willReturn(false);
        $this->repository->method('isPseudoTaken')->willReturn(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Ce pseudo n\'est pas disponible.');

        $this->service->register('takenPseudo', 'new@example.com', 'password123');
    }

    public function testFuseGoogleAccountAddsPasswordPreservesGoogleFields(): void
    {
        $existingUser = new User();
        $existingUser->setGoogleId('google-id-123');
        $existingUser->setDisplayName('Jean Dupont');
        $existingUser->setAvatarUrl('https://example.com/avatar.jpg');

        $this->hasher->method('hashPassword')->willReturn('$2y$13$hashedpassword');
        $this->em->expects($this->once())->method('flush');

        $this->service->fuseGoogleAccount($existingUser, 'newpassword123');

        $this->assertNotNull($existingUser->getPassword());
        $this->assertSame('google-id-123', $existingUser->getGoogleId());
        $this->assertSame('Jean Dupont', $existingUser->getDisplayName());
        $this->assertSame('https://example.com/avatar.jpg', $existingUser->getAvatarUrl());
    }

    public function testFuseGoogleAccountOnGoogleOnlyAccountCallsFuse(): void
    {
        $googleOnlyUser = new User();
        $googleOnlyUser->setEmail('google@example.com');
        $googleOnlyUser->setGoogleId('g123');
        // password is null → Google-only account

        $this->repository->method('findOneByEmail')->willReturn($googleOnlyUser);
        $this->hasher->method('hashPassword')->willReturn('$2y$13$hashedpassword');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->register('somepseudo', 'google@example.com', 'newpassword123');

        $this->assertSame($googleOnlyUser, $result);
        $this->assertNotNull($googleOnlyUser->getPassword());
    }

    public function testAutoLoginDoesNotSetRememberMeFlag(): void
    {
        $this->repository->method('findOneByEmail')->willReturn(null);
        $this->repository->method('isEmailTaken')->willReturn(false);
        $this->repository->method('isPseudoTaken')->willReturn(false);
        $this->hasher->method('hashPassword')->willReturn('$2y$13$hashedpassword');

        $user = $this->service->register('pseudo123', 'user@example.com', 'password123');

        $this->assertInstanceOf(User::class, $user);
    }
}
