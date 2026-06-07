<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileControllerTest extends WebTestCase
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

    public function testPublicProfileShowsRankBadgeForRoleUser(): void
    {
        $client = static::createClient();
        $this->createUser(['ROLE_USER'], 'contrib@test.com', 'contrib_user');

        $client->request('GET', '/profil/contrib_user');
        $this->assertResponseIsSuccessful();
    }

    public function testPublicProfileHidesRankForModerator(): void
    {
        $client = static::createClient();
        $this->createUser(['ROLE_MODERATOR'], 'mod@test.com', 'mod_user');

        $client->request('GET', '/profil/mod_user');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.badge-rank');
    }

    public function testPublicProfileReturns404ForUnknownPseudo(): void
    {
        $client = static::createClient();

        $client->request('GET', '/profil/unknown_pseudo_xyz');
        $this->assertResponseStatusCodeSame(404);
    }
}
