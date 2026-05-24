<?php

declare(strict_types=1);

namespace App\Tests\Integration\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class BannedUserTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\User u')->execute();

        parent::tearDown();
    }

    private function createUser(string $email, string $pseudo, array $roles = ['ROLE_MODERATOR'], string $status = 'active', ?\DateTimeImmutable $deletedAt = null): User
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPseudo($pseudo);
        $user->setRoles($roles);
        $user->setStatus($status);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        if ($deletedAt !== null) {
            $user->setDeletedAt($deletedAt);
        }
        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function testBannedUserOnAuthenticatedRouteReceives403(): void
    {
        $client = static::createClient();
        $active = $this->createUser('soon.banned@example.com', 'soontobanned', ['ROLE_MODERATOR'], 'active');

        $client->loginUser($active);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $active->setStatus('banned');
        $em->flush();

        $client->request('GET', '/moderation');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSoftDeletedUserCannotAuthenticate(): void
    {
        $client = static::createClient();
        $this->createUser('deleted@example.com', 'deletedmod', ['ROLE_MODERATOR'], 'active', new \DateTimeImmutable());

        $client->request('GET', '/connexion');
        $csrfToken = $client->getContainer()->get('security.csrf.token_manager')->getToken('authenticate')->getValue();
        $client->request('POST', '/connexion', [
            '_username' => 'deleted@example.com',
            '_password' => 'password123',
            '_csrf_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects('/connexion');
    }

    public function testActiveModeratorCanAccessModeration(): void
    {
        $client = static::createClient();
        $mod = $this->createUser('active.mod@example.com', 'activemod', ['ROLE_MODERATOR'], 'active');

        $client->loginUser($mod);
        $client->request('GET', '/moderation');
        $this->assertResponseIsSuccessful();
    }
}
