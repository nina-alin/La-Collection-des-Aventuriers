<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class EmailChangeService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Environment $twig,
    ) {
    }

    public function requestChange(User $user, string $newEmail): void
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+24 hours', new \DateTimeZone('UTC'));

        $user->setPendingEmail($newEmail);
        $user->setEmailChangeToken($token);
        $user->setEmailTokenExpiresAt($expiresAt);

        $this->em->flush();

        $confirmUrl = $this->urlGenerator->generate(
            'profile_confirm_email',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $htmlBody = $this->twig->render('emails/email_change_confirmation.html.twig', [
            'user' => $user,
            'confirmUrl' => $confirmUrl,
        ]);
        $textBody = $this->twig->render('emails/email_change_confirmation.txt.twig', [
            'user' => $user,
            'confirmUrl' => $confirmUrl,
        ]);

        $email = (new Email())
            ->to($newEmail)
            ->subject('Confirmation de changement d\'adresse e-mail — La Collection des Aventuriers')
            ->html($htmlBody)
            ->text($textBody);

        $this->mailer->send($email);
    }

    public function confirmChange(string $token): User
    {
        $user = $this->userRepository->findOneBy(['emailChangeToken' => $token]);

        if ($user === null) {
            throw new \InvalidArgumentException('Token invalide.');
        }

        $expiresAt = $user->getEmailTokenExpiresAt();
        if ($expiresAt === null || $expiresAt < new \DateTimeImmutable('now', new \DateTimeZone('UTC'))) {
            throw new \InvalidArgumentException('Token expiré.');
        }

        $pendingEmail = $user->getPendingEmail();
        if ($pendingEmail === null) {
            throw new \InvalidArgumentException('Aucune modification d\'e-mail en attente.');
        }

        $user->setEmail($pendingEmail);
        $user->setPendingEmail(null);
        $user->setEmailChangeToken(null);
        $user->setEmailTokenExpiresAt(null);

        $this->em->flush();

        return $user;
    }
}
