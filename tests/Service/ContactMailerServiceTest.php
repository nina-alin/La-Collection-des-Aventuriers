<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ContactMailerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ContactMailerServiceTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private ContactMailerService $service;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->service = new ContactMailerService(
            $this->mailer,
            'from@example.com',
            'to@example.com',
        );
    }

    public function testSendDispatchesEmailWithCorrectFromAndTo(): void
    {
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                return $email->getFrom()[0]->getAddress() === 'from@example.com'
                    && $email->getTo()[0]->getAddress() === 'to@example.com';
            }));

        $this->service->send(null, null, 'TestUser', 'user@example.com', 'autre', 'Hello');
    }

    public function testPseudoTakesPriorityOverPrenomNomInSubject(): void
    {
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                return str_contains($email->getSubject(), '— MonPseudo');
            }));

        $this->service->send('Jean', 'Dupont', 'MonPseudo', 'user@example.com', 'autre', 'Hello');
    }

    public function testPrenomNomUsedWhenNoPseudo(): void
    {
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                return str_contains($email->getSubject(), '— Jean Dupont');
            }));

        $this->service->send('Jean', 'Dupont', null, 'user@example.com', 'autre', 'Hello');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('raisonLabelProvider')]
    public function testAllRaisonLabelsProduceCorrectSubject(string $raison, string $expectedLabel): void
    {
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($expectedLabel) {
                return str_contains($email->getSubject(), "[Contact] {$expectedLabel}");
            }));

        $this->service->send(null, null, 'User', 'user@example.com', $raison, 'Hello');
    }

    public static function raisonLabelProvider(): array
    {
        return [
            ['question-site', "J'ai une question sur le site"],
            ['signaler-probleme', 'Je souhaite remonter un problème'],
            ['erreur-fiche', 'Je souhaite signaler une erreur dans une fiche'],
            ['suggerer-oeuvre', 'Je souhaite suggérer un livre ou une œuvre'],
            ['devenir-moderateur', 'Je souhaite devenir modérateur'],
            ['contester-moderation', 'Je souhaite contester une décision de modération'],
            ['donnees-personnelles', 'Question sur mes données personnelles'],
            ['partenariat', 'Partenariat, presse ou association'],
            ['autre', 'Autre'],
        ];
    }

    public function testExceptionPropagatesFromMailer(): void
    {
        $this->mailer->expects($this->once())
            ->method('send')
            ->willThrowException(new \RuntimeException('SMTP error'));

        $this->expectException(\RuntimeException::class);
        $this->service->send(null, null, 'User', 'user@example.com', 'autre', 'Hello');
    }
}
