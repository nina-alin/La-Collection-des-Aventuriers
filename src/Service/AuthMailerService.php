<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class AuthMailerService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Environment $twig,
    ) {
    }

    public function sendPasswordResetEmail(User $user, string $token): void
    {
        $resetUrl = $this->urlGenerator->generate(
            'app_password_reset_show',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $htmlBody = $this->twig->render('emails/password_reset.html.twig', [
            'user' => $user,
            'resetUrl' => $resetUrl,
        ]);
        $textBody = $this->twig->render('emails/password_reset.txt.twig', [
            'user' => $user,
            'resetUrl' => $resetUrl,
        ]);

        $email = (new Email())
            ->to($user->getEmail())
            ->subject('Réinitialisation de ton mot de passe — La Collection des Aventuriers')
            ->html($htmlBody)
            ->text($textBody);

        $this->mailer->send($email);
    }

    public function sendEmailConfirmationEmail(User $user, string $token): void
    {
        $confirmUrl = $this->urlGenerator->generate(
            'app_email_verify',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $htmlBody = $this->twig->render('emails/email_confirmation.html.twig', [
            'user' => $user,
            'confirmUrl' => $confirmUrl,
        ]);
        $textBody = $this->twig->render('emails/email_confirmation.txt.twig', [
            'user' => $user,
            'confirmUrl' => $confirmUrl,
        ]);

        $email = (new Email())
            ->to($user->getEmail())
            ->subject('Confirme ton adresse e-mail — La Collection des Aventuriers')
            ->html($htmlBody)
            ->text($textBody);

        $this->mailer->send($email);
    }
}
