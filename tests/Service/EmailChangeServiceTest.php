<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\EmailChangeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class EmailChangeServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private UserRepository&MockObject $userRepository;
    private MailerInterface&MockObject $mailer;
    private UrlGeneratorInterface&MockObject $urlGenerator;
    private Environment&MockObject $twig;
    private EmailChangeService $service;

    protected function setUp(): void
    {
        $this->em           = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->mailer       = $this->createMock(MailerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->twig         = $this->createMock(Environment::class);

        $this->service = new EmailChangeService(
            $this->em,
            $this->userRepository,
            $this->mailer,
            $this->urlGenerator,
            $this->twig,
        );
    }

    public function testRequestChangeSetsThreeFields(): void
    {
        $user = new User();
        $user->setEmail('old@example.com');
        $user->setPseudo('testuser');

        $this->urlGenerator->method('generate')->willReturn('https://example.com/confirm/TOKEN');
        $this->twig->method('render')->willReturn('<p>email</p>');
        $this->mailer->expects($this->once())->method('send');
        $this->em->expects($this->once())->method('flush');

        $this->service->requestChange($user, 'new@example.com');

        $this->assertSame('new@example.com', $user->getPendingEmail());
        $this->assertNotNull($user->getEmailChangeToken());
        $this->assertSame(64, strlen($user->getEmailChangeToken()));
        $this->assertNotNull($user->getEmailTokenExpiresAt());
        $this->assertGreaterThan(new \DateTimeImmutable(), $user->getEmailTokenExpiresAt());
    }

    public function testConfirmChangeSwapsEmailAndClearsFields(): void
    {
        $user = new User();
        $user->setEmail('old@example.com');
        $user->setPseudo('testuser');
        $user->setPendingEmail('new@example.com');
        $user->setEmailChangeToken('abc123');
        $user->setEmailTokenExpiresAt(new \DateTimeImmutable('+1 hour'));

        $this->userRepository
            ->method('findOneBy')
            ->willReturn($user);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->confirmChange('abc123');

        $this->assertSame('new@example.com', $result->getEmail());
        $this->assertNull($result->getPendingEmail());
        $this->assertNull($result->getEmailChangeToken());
        $this->assertNull($result->getEmailTokenExpiresAt());
    }

    public function testExpiredTokenIsRejected(): void
    {
        $user = new User();
        $user->setEmail('old@example.com');
        $user->setPseudo('testuser');
        $user->setPendingEmail('new@example.com');
        $user->setEmailChangeToken('expired123');
        $user->setEmailTokenExpiresAt(new \DateTimeImmutable('-1 hour'));

        $this->userRepository
            ->method('findOneBy')
            ->willReturn($user);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->confirmChange('expired123');
    }

    public function testInvalidTokenIsRejected(): void
    {
        $this->userRepository
            ->method('findOneBy')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->confirmChange('nonexistent-token');
    }

    public function testSuccessfulConfirmReturnsUpdatedUser(): void
    {
        $user = new User();
        $user->setEmail('old@example.com');
        $user->setPseudo('testuser');
        $user->setPendingEmail('confirmed@example.com');
        $user->setEmailChangeToken('valid-token');
        $user->setEmailTokenExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->userRepository->method('findOneBy')->willReturn($user);
        $this->em->method('flush');

        $result = $this->service->confirmChange('valid-token');

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame('confirmed@example.com', $result->getEmail());
    }
}
