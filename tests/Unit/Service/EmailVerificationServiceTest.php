<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\EmailVerificationToken;
use App\Entity\User;
use App\Repository\EmailVerificationTokenRepository;
use App\Repository\UserRepository;
use App\Service\AuthMailerService;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

class EmailVerificationServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private EmailVerificationTokenRepository&MockObject $tokenRepo;
    private UserRepository&MockObject $userRepo;
    private AuthMailerService&MockObject $mailer;
    private RateLimiterFactory $limiterFactory;
    private EmailVerificationService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->tokenRepo = $this->createMock(EmailVerificationTokenRepository::class);
        $this->userRepo = $this->createMock(UserRepository::class);
        $this->mailer = $this->createMock(AuthMailerService::class);
        $this->limiterFactory = new RateLimiterFactory(
            ['id' => 'resend', 'policy' => 'no_limit', 'limit' => 999999, 'interval' => '3600 seconds'],
            new InMemoryStorage()
        );

        $this->service = new EmailVerificationService(
            $this->em,
            $this->tokenRepo,
            $this->userRepo,
            $this->mailer,
            $this->limiterFactory,
        );
    }

    private function makeUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPseudo('testuser');

        return $user;
    }

    public function testSendConfirmationEmailCallsAuthMailer(): void
    {
        $user = $this->makeUser();

        $this->tokenRepo->expects($this->once())->method('deleteForUser')->with($user);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->mailer->expects($this->once())->method('sendEmailConfirmationEmail')
            ->with($user, $this->isType('string'));

        $this->service->sendConfirmationEmail($user);
    }

    public function testVerifyTokenSetsEmailVerifiedAndDeletesToken(): void
    {
        $user = $this->makeUser();
        $token = new EmailVerificationToken($user);

        $this->tokenRepo->expects($this->once())
            ->method('findByToken')
            ->with('sometoken')
            ->willReturn($token);

        $this->em->expects($this->once())->method('remove')->with($token);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->verifyToken('sometoken');

        $this->assertTrue($result);
        $this->assertTrue($user->isEmailVerified());
    }

    public function testVerifyTokenReturnsFalseForExpiredToken(): void
    {
        $user = $this->makeUser();
        $token = new EmailVerificationToken($user);
        $reflection = new \ReflectionClass($token);
        $prop = $reflection->getProperty('expiresAt');
        $prop->setAccessible(true);
        $prop->setValue($token, new \DateTimeImmutable('-1 second'));

        $this->tokenRepo->method('findByToken')->willReturn($token);
        $this->em->expects($this->never())->method('flush');

        $result = $this->service->verifyToken('expiredtoken');

        $this->assertFalse($result);
        $this->assertFalse($user->isEmailVerified());
    }

    public function testResendDeletesOldTokenAndCreatesNew(): void
    {
        $user = $this->makeUser();

        $this->userRepo->method('findOneBy')->willReturn($user);
        $this->tokenRepo->expects($this->once())->method('deleteForUser')->with($user);
        $this->mailer->expects($this->once())->method('sendEmailConfirmationEmail');
        $this->em->method('persist');
        $this->em->method('flush');

        $this->service->resend('test@example.com', '127.0.0.1');
    }

    public function testMailerExceptionPropagatesWithoutSettingVerified(): void
    {
        $user = $this->makeUser();

        $this->tokenRepo->method('deleteForUser');
        $this->em->method('persist');
        $this->em->method('flush');
        $this->mailer->method('sendEmailConfirmationEmail')
            ->willThrowException(new \RuntimeException('Mail error'));

        $this->expectException(\RuntimeException::class);

        $this->service->sendConfirmationEmail($user);

        $this->assertFalse($user->isEmailVerified());
    }
}
