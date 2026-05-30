<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\ResetPasswordToken;
use App\Entity\User;
use App\Repository\ResetPasswordTokenRepository;
use App\Repository\UserRepository;
use App\Service\AuthMailerService;
use App\Service\PasswordResetService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

class PasswordResetServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ResetPasswordTokenRepository&MockObject $tokenRepo;
    private UserRepository&MockObject $userRepo;
    private AuthMailerService&MockObject $mailer;
    private UserPasswordHasherInterface&MockObject $hasher;
    private RateLimiterFactory $limiterFactory;
    private PasswordResetService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->tokenRepo = $this->createMock(ResetPasswordTokenRepository::class);
        $this->userRepo = $this->createMock(UserRepository::class);
        $this->mailer = $this->createMock(AuthMailerService::class);
        $this->hasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->limiterFactory = new RateLimiterFactory(
            ['id' => 'resend', 'policy' => 'no_limit', 'limit' => 999999, 'interval' => '3600 seconds'],
            new InMemoryStorage()
        );

        $this->service = new PasswordResetService(
            $this->em,
            $this->tokenRepo,
            $this->userRepo,
            $this->mailer,
            $this->hasher,
            $this->limiterFactory,
        );
    }

    private function makeUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPseudo('testuser');
        $user->setPassword('oldhash');

        return $user;
    }

    private function mockTransaction(): void
    {
        $this->em->method('wrapInTransaction')
            ->willReturnCallback(function (callable $fn) {
                $fn();
            });
    }

    public function testRequestResetInvalidatesExistingTokensBeforeCreatingNew(): void
    {
        $user = $this->makeUser();
        $this->userRepo->method('findOneBy')->willReturn($user);
        $this->mockTransaction();

        $invalidated = false;
        $this->tokenRepo->expects($this->once())
            ->method('invalidateAllForUser')
            ->with($user)
            ->willReturnCallback(function () use (&$invalidated) { $invalidated = true; });

        $persisted = false;
        $this->em->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function () use (&$persisted) { $persisted = true; });

        $this->em->method('flush');
        $this->mailer->method('sendPasswordResetEmail');

        $this->service->requestReset('test@example.com');

        $this->assertTrue($invalidated);
        $this->assertTrue($persisted);
    }

    public function testRequestResetCreatesTokenAndSendsEmail(): void
    {
        $user = $this->makeUser();
        $this->userRepo->method('findOneBy')->willReturn($user);
        $this->mockTransaction();
        $this->tokenRepo->method('invalidateAllForUser');
        $this->em->method('persist');
        $this->em->method('flush');

        $this->mailer->expects($this->once())
            ->method('sendPasswordResetEmail')
            ->with($user, $this->isType('string'));

        $this->service->requestReset('test@example.com');
    }

    public function testRequestResetForUnknownEmailDoesNothing(): void
    {
        $this->userRepo->method('findOneBy')->willReturn(null);

        $this->tokenRepo->expects($this->never())->method('invalidateAllForUser');
        $this->em->expects($this->never())->method('persist');
        $this->mailer->expects($this->never())->method('sendPasswordResetEmail');

        $this->service->requestReset('unknown@example.com');
    }

    public function testMailerExceptionPropagates(): void
    {
        $user = $this->makeUser();
        $this->userRepo->method('findOneBy')->willReturn($user);
        $this->mockTransaction();
        $this->tokenRepo->method('invalidateAllForUser');
        $this->em->method('persist');
        $this->em->method('flush');
        $this->mailer->method('sendPasswordResetEmail')
            ->willThrowException(new \RuntimeException('SMTP error'));

        $this->expectException(\RuntimeException::class);

        $this->service->requestReset('test@example.com');
    }

    public function testResetPasswordMarksTokenUsed(): void
    {
        $user = $this->makeUser();
        $token = new ResetPasswordToken($user);

        $this->tokenRepo->method('findValidTokenByString')->willReturn($token);
        $this->mockTransaction();
        $this->hasher->method('hashPassword')->willReturn('newhash');

        $conn = $this->createMock(Connection::class);
        $conn->method('executeStatement');
        $this->em->method('getConnection')->willReturn($conn);
        $this->em->method('flush');

        $this->service->resetPassword($token->getToken(), 'NewPass1!', 'NewPass1!');

        $this->assertTrue($token->isUsed());
    }

    public function testResetPasswordUpdatesPasswordHash(): void
    {
        $user = $this->makeUser();
        $token = new ResetPasswordToken($user);

        $this->tokenRepo->method('findValidTokenByString')->willReturn($token);
        $this->mockTransaction();
        $this->hasher->method('hashPassword')->willReturn('newhash');

        $conn = $this->createMock(Connection::class);
        $conn->method('executeStatement');
        $this->em->method('getConnection')->willReturn($conn);
        $this->em->method('flush');

        $this->service->resetPassword($token->getToken(), 'NewPass1!', 'NewPass1!');

        $this->assertSame('newhash', $user->getPassword());
    }

    public function testResetPasswordDeletesRememberMeTokensViaDbal(): void
    {
        $user = $this->makeUser();
        $token = new ResetPasswordToken($user);

        $this->tokenRepo->method('findValidTokenByString')->willReturn($token);
        $this->mockTransaction();
        $this->hasher->method('hashPassword')->willReturn('newhash');

        $conn = $this->createMock(Connection::class);
        $conn->expects($this->once())
            ->method('executeStatement')
            ->with(
                'DELETE FROM rememberme_token WHERE username = :email',
                ['email' => 'test@example.com']
            );
        $this->em->method('getConnection')->willReturn($conn);
        $this->em->method('flush');

        $this->service->resetPassword($token->getToken(), 'NewPass1!', 'NewPass1!');
    }

    public function testResetPasswordThrowsForInvalidToken(): void
    {
        $this->tokenRepo->method('findValidTokenByString')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid_token');

        $this->service->resetPassword('badtoken', 'NewPass1!', 'NewPass1!');
    }

    public function testResetPasswordThrowsForPasswordsMismatch(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->resetPassword('anytoken', 'NewPass1!', 'DifferentPass1!');
    }
}
