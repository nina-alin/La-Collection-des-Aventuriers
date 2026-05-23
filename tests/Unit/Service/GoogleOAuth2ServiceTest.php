<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\GoogleOAuth2Service;
use App\Service\UserRegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class GoogleOAuth2ServiceTest extends TestCase
{
    private UserRepository $repository;
    private UserRegistrationService $registrationService;
    private EntityManagerInterface $em;
    private GoogleOAuth2Service $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepository::class);
        $this->registrationService = $this->createMock(UserRegistrationService::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->service = new GoogleOAuth2Service(
            $this->repository,
            $this->registrationService,
            $this->em,
        );
    }

    private function googleUserInfo(array $overrides = []): array
    {
        return array_merge([
            'email' => 'google@example.com',
            'email_verified' => true,
            'sub' => 'google-sub-123',
            'name' => 'Jean Dupont',
            'picture' => 'https://example.com/photo.jpg',
        ], $overrides);
    }

    public function testFr011ExistingEmailConnectsExistingUser(): void
    {
        $existingUser = new User();
        $existingUser->setEmail('google@example.com');
        $existingUser->setPassword('$2y$13$existing');

        $this->repository->method('findOneByEmail')->willReturn($existingUser);

        $result = $this->service->findOrCreateUser($this->googleUserInfo());

        $this->assertSame($existingUser, $result);
        $this->em->expects($this->never())->method('persist');
    }

    public function testFr012NewEmailCreatesUserWithGoogleFields(): void
    {
        $this->repository->method('findOneByEmail')->willReturn(null);
        $this->repository->method('isPseudoTaken')->willReturn(false);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $user = $this->service->findOrCreateUser($this->googleUserInfo());

        $this->assertSame('google-sub-123', $user->getGoogleId());
        $this->assertSame('Jean Dupont', $user->getDisplayName());
        $this->assertSame('https://example.com/photo.jpg', $user->getAvatarUrl());
        $this->assertNull($user->getPassword());
    }

    public function testFr016EmailVerifiedFalseThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Adresse Google non vérifiée. Utilisez la connexion classique.');

        $this->service->findOrCreateUser($this->googleUserInfo(['email_verified' => false]));
    }

    public function testFr016AbsentEmailVerifiedTreatedAsFalse(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Adresse Google non vérifiée. Utilisez la connexion classique.');

        $info = $this->googleUserInfo();
        unset($info['email_verified']);
        $this->service->findOrCreateUser($info);
    }

    public function testFr018PseudoAlreadyTakenAppendsSuffix(): void
    {
        $this->repository->method('findOneByEmail')->willReturn(null);
        $this->repository->method('isPseudoTaken')
            ->willReturnCallback(fn($p) => in_array($p, ['JeanDupont', 'JeanDupont_2']));
        $this->em->method('persist');
        $this->em->method('flush');

        $user = $this->service->findOrCreateUser($this->googleUserInfo(['name' => 'Jean Dupont']));

        $this->assertStringStartsWith('JeanDupont', $user->getPseudo());
        $this->assertNotSame('JeanDupont', $user->getPseudo());
        $this->assertNotSame('JeanDupont_2', $user->getPseudo());
    }

    public function testFr018EmptyGoogleNameFallsBackToEmailLocalPart(): void
    {
        $this->repository->method('findOneByEmail')->willReturn(null);
        $this->repository->method('isPseudoTaken')->willReturn(false);
        $this->em->method('persist');
        $this->em->method('flush');

        $user = $this->service->findOrCreateUser($this->googleUserInfo(['name' => '']));

        $this->assertSame('google', $user->getPseudo());
    }

    public function testFr015EmailBelongsToGoogleOnlyAccountReturnsExistingUser(): void
    {
        $googleOnlyUser = new User();
        $googleOnlyUser->setEmail('google@example.com');
        $googleOnlyUser->setGoogleId('g123');

        $this->repository->method('findOneByEmail')->willReturn($googleOnlyUser);
        $this->registrationService->expects($this->never())->method('fuseGoogleAccount');

        $result = $this->service->findOrCreateUser($this->googleUserInfo());

        $this->assertSame($googleOnlyUser, $result);
    }
}
