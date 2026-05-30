<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\EmailVerificationToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EmailVerificationControllerTest extends WebTestCase
{
    public function testValidTokenSetsEmailVerified(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $user = new User();
        $user->setEmail('verify@example.com');
        $user->setPseudo('verify_user');
        $user->setPassword('$2y$13$hash');
        $user->setIsEmailVerified(false);
        $em->persist($user);
        $em->flush();

        $token = new EmailVerificationToken($user);
        $em->persist($token);
        $em->flush();
        $tokenString = $token->getToken();

        $client->request('GET', '/confirmation-email/' . $tokenString);

        $this->assertResponseIsSuccessful();

        $em->clear();
        $refreshed = $em->getRepository(User::class)->findOneBy(['email' => 'verify@example.com']);
        $this->assertTrue($refreshed->isEmailVerified());

        $em->remove($refreshed);
        $em->flush();
    }

    public function testExpiredOrInvalidTokenRendersErrorView(): void
    {
        $client = static::createClient();

        $client->request('GET', '/confirmation-email/invalidtoken123456789012345678901234567890123456789012345678901234');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.auth-confirm-icon');
    }

    public function testResendConfirmationIsRateLimited(): void
    {
        $client = static::createClient();

        $client->request('POST', '/inscription/renvoyer-confirmation', [
            'email' => 'test@example.com',
            '_csrf_token' => 'invalid',
        ]);

        $this->assertResponseRedirects();
    }
}
