<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileControllerTest extends WebTestCase
{
    public function testDashboardRedirectsUnauthenticatedUserToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/profil');

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/connexion', $client->getResponse()->headers->get('Location'));
    }

    public function testDashboardReturns200ForRoleUser(): void
    {
        $client = static::createClient();
        $em     = static::getContainer()->get(EntityManagerInterface::class);
        $user   = $em->getRepository(User::class)->findOneBy([]);

        if ($user === null) {
            $this->markTestSkipped('No user in test database.');
        }

        $client->loginUser($user);
        $client->request('GET', '/profil');

        $this->assertResponseIsSuccessful();
    }

    public function testListVisibilityRejectsBadCsrfToken(): void
    {
        $client = static::createClient();
        $em     = static::getContainer()->get(EntityManagerInterface::class);
        $user   = $em->getRepository(User::class)->findOneBy([]);

        if ($user === null) {
            $this->markTestSkipped('No user in test database.');
        }

        $client->loginUser($user);
        $client->request('POST', '/profil/list/collection/visibility', ['_token' => 'invalid-token']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteAccountRejectsBadCsrfToken(): void
    {
        $client = static::createClient();
        $em     = static::getContainer()->get(EntityManagerInterface::class);
        $user   = $em->getRepository(User::class)->findOneBy([]);

        if ($user === null) {
            $this->markTestSkipped('No user in test database.');
        }

        $client->loginUser($user);
        $client->request('POST', '/profil/delete-account', [
            '_token' => 'invalid-token',
            'confirmation' => 'SUPPRIMER',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteAccountRejectedWhenConfirmationWrong(): void
    {
        $client = static::createClient();
        $em     = static::getContainer()->get(EntityManagerInterface::class);
        $user   = $em->getRepository(User::class)->findOneBy([]);

        if ($user === null) {
            $this->markTestSkipped('No user in test database.');
        }

        $client->loginUser($user);
        $crawler   = $client->request('GET', '/profil');
        $csrfToken = $crawler->filter('input[name="_token"][form="delete-account-form"], #delete-account-form input[name="_token"], form[action*="delete-account"] input[name="_token"]')->attr('value');

        if ($csrfToken === null) {
            $this->markTestSkipped('Delete account CSRF token not found in dashboard template.');
        }

        $client->request('POST', '/profil/delete-account', [
            '_token' => $csrfToken,
            'confirmation' => 'wrong',
        ]);

        $this->assertResponseRedirects('/profil');
    }
}
