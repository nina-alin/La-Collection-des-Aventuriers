<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ResetPasswordToken;
use App\Entity\User;
use App\Repository\ResetPasswordTokenRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class PasswordResetService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ResetPasswordTokenRepository $tokenRepository,
        private readonly UserRepository $userRepository,
        private readonly AuthMailerService $mailer,
        private readonly UserPasswordHasherInterface $hasher,
        #[Autowire(service: 'limiter.resend_limiter')]
        private readonly RateLimiterFactory $resendLimiter,
    ) {
    }

    public function requestReset(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => strtolower($email)]);
        if ($user === null) {
            return;
        }

        $this->em->wrapInTransaction(function () use ($user): void {
            $this->tokenRepository->invalidateAllForUser($user);

            $token = new ResetPasswordToken($user);
            $this->em->persist($token);
            $this->em->flush();

            $this->mailer->sendPasswordResetEmail($user, $token->getToken());
        });
    }

    public function resend(string $email, string $ip): void
    {
        $limiter = $this->resendLimiter->create($ip);
        $limit = $limiter->consume();
        if (!$limit->isAccepted()) {
            throw new \RuntimeException('rate_limited');
        }

        $this->requestReset($email);
    }

    public function resetPassword(string $tokenString, string $plainPassword, string $passwordConfirm): void
    {
        if ($plainPassword !== $passwordConfirm) {
            throw new \InvalidArgumentException('Les mots de passe ne correspondent pas.');
        }

        $token = $this->tokenRepository->findValidTokenByString($tokenString);
        if ($token === null) {
            throw new \RuntimeException('invalid_token');
        }

        $user = $token->getUser();

        $this->em->wrapInTransaction(function () use ($user, $token, $plainPassword): void {
            $hashed = $this->hasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashed);

            $this->em->getConnection()->executeStatement(
                'DELETE FROM rememberme_token WHERE username = :email',
                ['email' => $user->getEmail()]
            );

            $token->setUsed(true);
            $this->em->flush();
        });
    }
}
