<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\ModerationLog m')->execute();
        $em->createQuery('DELETE FROM App\Entity\CorrectionProposal c')->execute();
        $em->createQuery('DELETE FROM App\Entity\WorkEntry w')->execute();
        $em->createQuery('DELETE FROM App\Entity\User u')->execute();

        parent::tearDown();
    }

    private function createUser(array $roles, string $email, string $pseudo, string $status = 'active'): User
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
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function csrfToken(object $client, string $tokenId): string
    {
        $client->request('GET', '/test-tokens/csrf/'.$tokenId);
        return json_decode($client->getResponse()->getContent(), true)['token'];
    }

    public function testGetAdminUsersAsAdminReturns200(): void
    {
        $client = static::createClient();
        $admin = $this->createUser(['ROLE_ADMIN'], 'admin@example.com', 'adminuser');

        $client->loginUser($admin);
        $client->request('GET', '/admin/users');
        $this->assertResponseIsSuccessful();
    }

    public function testModeratorOnAdminReturns403(): void
    {
        $client = static::createClient();
        $mod = $this->createUser(['ROLE_MODERATOR'], 'mod@example.com', 'moduser');

        $client->loginUser($mod);
        $client->request('GET', '/admin/users');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testChangeRoleWithValidCsrfRedirectsWithSuccess(): void
    {
        $client = static::createClient();
        $admin = $this->createUser(['ROLE_ADMIN'], 'admin2@example.com', 'adminuser2');
        $target = $this->createUser(['ROLE_USER'], 'target@example.com', 'targetuser');

        $client->loginUser($admin);
        $client->request('GET', '/admin/users');

        $targetId = (string) $target->getId();
        $client->request('POST', '/admin/users/'.$targetId.'/role', [
            '_csrf_token' => $this->csrfToken($client, 'admin_user_'.$targetId),
            'role' => 'ROLE_MODERATOR',
        ]);

        $this->assertResponseRedirects('/admin/users');
    }

    public function testSelfRoleChangeReturnsFlashError(): void
    {
        $client = static::createClient();
        $admin = $this->createUser(['ROLE_ADMIN'], 'admin3@example.com', 'adminuser3');

        $client->loginUser($admin);
        $client->request('GET', '/admin/users');

        $adminId = (string) $admin->getId();
        $client->request('POST', '/admin/users/'.$adminId.'/role', [
            '_csrf_token' => $this->csrfToken($client, 'admin_user_'.$adminId),
            'role' => 'ROLE_USER',
        ]);
        $client->followRedirect();

        $this->assertSelectorTextContains('body', 'propre rôle');
    }

    public function testBanUserWithValidCsrf(): void
    {
        $client = static::createClient();
        $admin = $this->createUser(['ROLE_ADMIN'], 'admin4@example.com', 'adminuser4');
        $target = $this->createUser(['ROLE_USER'], 'victim@example.com', 'victimuser');

        $client->loginUser($admin);
        $client->request('GET', '/admin/users');

        $targetId = (string) $target->getId();
        $client->request('POST', '/admin/users/'.$targetId.'/ban', [
            '_csrf_token' => $this->csrfToken($client, 'admin_user_'.$targetId),
        ]);

        $this->assertResponseRedirects('/admin/users');

        $target = static::getContainer()->get(EntityManagerInterface::class)->find(User::class, $target->getId());
        $this->assertSame('banned', $target->getStatus());
    }

    public function testSoftDeleteUser(): void
    {
        $client = static::createClient();
        $admin = $this->createUser(['ROLE_ADMIN'], 'admin5@example.com', 'adminuser5');
        $target = $this->createUser(['ROLE_USER'], 'tobedeleted@example.com', 'deleteuser');

        $client->loginUser($admin);
        $client->request('GET', '/admin/users');

        $targetId = (string) $target->getId();
        $client->request('POST', '/admin/users/'.$targetId.'/delete', [
            '_csrf_token' => $this->csrfToken($client, 'admin_user_'.$targetId),
        ]);

        $this->assertResponseRedirects('/admin/users');

        $target = static::getContainer()->get(EntityManagerInterface::class)->find(User::class, $target->getId());
        $this->assertSame('[deleted]', $target->getEmail());
        $this->assertNotNull($target->getDeletedAt());
    }

    public function testGetAdminSettingsReturns200WithJson(): void
    {
        $client = static::createClient();
        $admin = $this->createUser(['ROLE_ADMIN'], 'admin6@example.com', 'adminuser6');

        $client->loginUser($admin);
        $client->request('GET', '/admin/settings');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Settings UI coming soon', $data['message']);
    }
}
