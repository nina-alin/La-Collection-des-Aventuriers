<?php

namespace App\Tests\Integration\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\User u')->execute();

        parent::tearDown();
    }

    private function createTestUser(string $email = 'test@example.com', string $password = 'password123'): User
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPseudo('testuser');
        $user->setPassword($hasher->hashPassword($user, $password));
        $user->setIsEmailVerified(true);

        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function testGetLoginReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/connexion');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
        $this->assertSelectorExists('input[name="_remember_me"]');
        $this->assertSelectorExists('input[name="_csrf_token"]');
    }

    public function testPostValidCredentialsRedirectsToHome(): void
    {
        $client = static::createClient();
        $this->createTestUser();

        $client->request('GET', '/connexion');
        $csrfToken = $client->getContainer()->get('security.csrf.token_manager')
            ->getToken('authenticate')->getValue();

        $client->request('POST', '/connexion', [
            '_username' => 'test@example.com',
            '_password' => 'password123',
            '_csrf_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects('/');
    }

    public function testPostInvalidCredentialsShowsErrorMessage(): void
    {
        $client = static::createClient();
        $this->createTestUser();

        $client->request('GET', '/connexion');
        $csrfToken = $client->getContainer()->get('security.csrf.token_manager')
            ->getToken('authenticate')->getValue();

        $client->request('POST', '/connexion', [
            '_username' => 'test@example.com',
            '_password' => 'wrongpassword',
            '_csrf_token' => $csrfToken,
        ]);

        $client->followRedirect();
        $this->assertSelectorTextContains('body', 'Identifiant ou mot de passe incorrect.');
    }

    public function testRememberMeCookieHasSecurityAttributesWhenChecked(): void
    {
        $client = static::createClient();
        $this->createTestUser();

        $client->request('GET', '/connexion');
        $csrfToken = $client->getContainer()->get('security.csrf.token_manager')
            ->getToken('authenticate')->getValue();

        $client->request('POST', '/connexion', [
            '_username' => 'test@example.com',
            '_password' => 'password123',
            '_remember_me' => '1',
            '_csrf_token' => $csrfToken,
        ]);

        $cookies = $client->getCookieJar()->all();
        $rememberMe = null;
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'REMEMBERME') {
                $rememberMe = $cookie;
                break;
            }
        }

        $this->assertNotNull($rememberMe, 'REMEMBERME cookie should be set');
        $this->assertTrue($rememberMe->isHttpOnly());
    }

    public function testTargetPathRedirectRespected(): void
    {
        $client = static::createClient();
        $this->createTestUser();

        $client->request('GET', '/suggestions');
        $client->followRedirect();

        $csrfToken = $client->getContainer()->get('security.csrf.token_manager')
            ->getToken('authenticate')->getValue();

        $client->request('POST', '/connexion', [
            '_username' => 'test@example.com',
            '_password' => 'password123',
            '_csrf_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects('/suggestions');
    }

    public function testPostLogoutDestroySessionAndRedirects(): void
    {
        $client = static::createClient();
        $this->createTestUser();

        $client->request('GET', '/connexion');
        $csrfToken = $client->getContainer()->get('security.csrf.token_manager')
            ->getToken('authenticate')->getValue();

        $client->request('POST', '/connexion', [
            '_username' => 'test@example.com',
            '_password' => 'password123',
            '_csrf_token' => $csrfToken,
        ]);
        $client->followRedirect();

        $logoutCsrf = $client->getContainer()->get('security.csrf.token_manager')
            ->getToken('logout')->getValue();

        $client->request('POST', '/deconnexion', ['_csrf_token' => $logoutCsrf]);

        $this->assertResponseRedirects('/connexion');

        $client->followRedirect();
        $client->request('GET', '/suggestions');
        $this->assertResponseRedirects('/connexion');
    }

    public function testGetDeconnexionReturns405(): void
    {
        $client = static::createClient();
        $client->request('GET', '/deconnexion');
        $this->assertResponseStatusCodeSame(405);
    }

    public function testRememberMeCookieClearedAfterLogout(): void
    {
        $client = static::createClient();
        $this->createTestUser();

        $client->request('GET', '/connexion');
        $csrfToken = $client->getContainer()->get('security.csrf.token_manager')
            ->getToken('authenticate')->getValue();

        $client->request('POST', '/connexion', [
            '_username' => 'test@example.com',
            '_password' => 'password123',
            '_remember_me' => '1',
            '_csrf_token' => $csrfToken,
        ]);
        $client->followRedirect();

        $logoutCsrf = $client->getContainer()->get('security.csrf.token_manager')
            ->getToken('logout')->getValue();

        $client->request('POST', '/deconnexion', ['_csrf_token' => $logoutCsrf]);

        $cookies = $client->getCookieJar()->all();
        $rememberMeAfterLogout = null;
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'REMEMBERME') {
                $rememberMeAfterLogout = $cookie;
                break;
            }
        }

        $this->assertTrue(
            null === $rememberMeAfterLogout || ($rememberMeAfterLogout->getExpiresTime() !== null && $rememberMeAfterLogout->getExpiresTime() <= 0),
            'REMEMBERME cookie should be absent or cleared after logout'
        );
    }
}
