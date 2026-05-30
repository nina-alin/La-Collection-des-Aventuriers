<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\EmailVerificationToken;
use App\Entity\User;
use App\Repository\EmailVerificationTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class EmailVerificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EmailVerificationTokenRepository $tokenRepository,
        private readonly UserRepository $userRepository,
        private readonly AuthMailerService $mailer,
        #[Autowire(service: 'limiter.resend_limiter')]
        private readonly RateLimiterFactory $resendLimiter,
    ) {
    }

    public function sendConfirmationEmail(User $user): void
    {
        $this->tokenRepository->deleteForUser($user);

        $token = new EmailVerificationToken($user);
        $this->em->persist($token);
        $this->em->flush();

        $this->mailer->sendEmailConfirmationEmail($user, $token->getToken());
    }

    public function verifyToken(string $tokenString): bool
    {
        $token = $this->tokenRepository->findByToken($tokenString);

        if ($token === null || !$token->isValid()) {
            return false;
        }

        $user = $token->getUser();
        $user->setIsEmailVerified(true);

        $this->em->remove($token);
        $this->em->flush();

        return true;
    }

    public function resend(string $email, string $ip): void
    {
        $limiter = $this->resendLimiter->create($ip);
        $limit = $limiter->consume();
        if (!$limit->isAccepted()) {
            throw new \RuntimeException('rate_limited');
        }

        $user = $this->userRepository->findOneBy(['email' => strtolower($email)]);
        if ($user === null || $user->isEmailVerified()) {
            return;
        }

        $this->sendConfirmationEmail($user);
    }
}
