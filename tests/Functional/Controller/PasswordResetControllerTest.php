<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\ResetPasswordToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * TDD: Written before implementation. Will fail until T042/T043/T044 are complete.
 */
class PasswordResetControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\ResetPasswordToken t')->execute();
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :email')
            ->setParameter('email', 'reset@example.com')
            ->execute();

        parent::tearDown();
    }

    private function createTestUser(EntityManagerInterface $em): User
    {
        $user = new User();
        $user->setEmail('reset@example.com');
        $user->setPseudo('reset_user');
        $user->setPassword('$2y$13$oldhashvalue12345678901234567890123456789012345678901234');
        $user->setIsEmailVerified(true);
        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function testGetWithValidTokenShowsForm(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $user = $this->createTestUser($em);
        $token = new ResetPasswordToken($user);
        $em->persist($token);
        $em->flush();
        $tokenString = $token->getToken();

        $client->request('GET', '/reinitialiser-mot-de-passe?token=' . $tokenString);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testPostWithValidTokenAndMatchingPasswordsReturnsSuccess(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $user = $this->createTestUser($em);
        $token = new ResetPasswordToken($user);
        $em->persist($token);
        $em->flush();
        $tokenString = $token->getToken();

        $client->request('GET', '/test-tokens/csrf/reset_password');
        $csrfToken = json_decode($client->getResponse()->getContent(), true)['token'];

        $client->request('POST', '/reinitialiser-mot-de-passe', [
            'token' => $tokenString,
            'plainPassword' => 'NewSecure1!',
            'passwordConfirm' => 'NewSecure1!',
            '_csrf_token' => $csrfToken,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('success', (string) $client->getResponse()->getContent());
    }

    public function testExpiredTokenGetShowsInvalidState(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $user = $this->createTestUser($em);
        $token = new ResetPasswordToken($user);
        $reflection = new \ReflectionClass($token);
        $prop = $reflection->getProperty('expiresAt');
        $prop->setAccessible(true);
        $prop->setValue($token, new \DateTimeImmutable('-1 hour'));
        $em->persist($token);
        $em->flush();
        $tokenString = $token->getToken();

        $client->request('GET', '/reinitialiser-mot-de-passe?token=' . $tokenString);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('invalid', (string) $client->getResponse()->getContent());
    }

    public function testSecondUseOfSameTokenReturnsInvalid(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $user = $this->createTestUser($em);
        $token = new ResetPasswordToken($user);
        $token->setUsed(true);
        $em->persist($token);
        $em->flush();
        $tokenString = $token->getToken();

        $client->request('GET', '/reinitialiser-mot-de-passe?token=' . $tokenString);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('invalid', (string) $client->getResponse()->getContent());
    }
}
