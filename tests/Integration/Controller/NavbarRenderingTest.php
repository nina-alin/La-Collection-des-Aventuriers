<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class NavbarRenderingTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\User u')->execute();

        parent::tearDown();
    }

    private function createUser(array $roles, string $email, string $pseudo): User
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPseudo($pseudo);
        $user->setRoles($roles);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function testRoleUserHasNoModerationLink(): void
    {
        $client = static::createClient();
        $user = $this->createUser(['ROLE_USER'], 'user@example.com', 'useronly');

        $client->loginUser($user);
        $crawler = $client->request('GET', '/');

        $this->assertCount(0, $crawler->filter('a[href*="moderation"]'));
    }

    public function testRoleModeratorHasModerationLink(): void
    {
        $client = static::createClient();
        $mod = $this->createUser(['ROLE_MODERATOR'], 'mod@example.com', 'moduser');

        $client->loginUser($mod);
        $crawler = $client->request('GET', '/');

        $this->assertGreaterThan(0, $crawler->filter('a[href*="moderation"]')->count());
    }

    public function testRoleAdminHasBothModerationAndAdminLinks(): void
    {
        $client = static::createClient();
        $admin = $this->createUser(['ROLE_ADMIN'], 'admin@example.com', 'adminuser');

        $client->loginUser($admin);
        $crawler = $client->request('GET', '/');

        $this->assertGreaterThan(0, $crawler->filter('a[href*="moderation"]')->count());
        $this->assertGreaterThan(0, $crawler->filter('a[href*="admin"]')->count());
    }
}
