<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ContactMailerService
{
    private const RAISON_LABELS = [
        'question-site'        => "J'ai une question sur le site",
        'signaler-probleme'    => 'Je souhaite remonter un problème',
        'erreur-fiche'         => 'Je souhaite signaler une erreur dans une fiche',
        'suggerer-oeuvre'      => 'Je souhaite suggérer un livre ou une œuvre',
        'devenir-moderateur'   => 'Je souhaite devenir modérateur',
        'contester-moderation' => 'Je souhaite contester une décision de modération',
        'donnees-personnelles' => 'Question sur mes données personnelles',
        'partenariat'          => 'Partenariat, presse ou association',
        'autre'                => 'Autre',
    ];

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $contactEmailFrom,
        private readonly string $contactEmailTo,
    ) {
    }

    public function send(
        ?string $prenom,
        ?string $nom,
        ?string $pseudo,
        string $email,
        string $raison,
        string $message,
    ): void {
        $identifiant = (trim((string) $pseudo) !== '') ? $pseudo : "$prenom $nom";
        $label = self::RAISON_LABELS[$raison] ?? $raison;
        $sujet = "[Contact] {$label} — {$identifiant}";

        $mail = (new Email())
            ->from($this->contactEmailFrom)
            ->to($this->contactEmailTo)
            ->replyTo($email)
            ->subject($sujet)
            ->text(
                "De : {$identifiant} <{$email}>\n"
                . "Raison : {$label}\n\n"
                . $message
            );

        $this->mailer->send($mail);
    }
}
