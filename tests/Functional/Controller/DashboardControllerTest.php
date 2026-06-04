<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DashboardControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\ActivityEvent ae')->execute();
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email LIKE :prefix')
            ->setParameter('prefix', '__dashboard_test_%')
            ->execute();
        parent::tearDown();
    }

    private function createUser(array $roles = [], string $suffix = 'user'): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail("__dashboard_test_{$suffix}@example.com");
        $user->setPseudo("testdash_{$suffix}");
        $user->setRoles($roles);
        $user->setPassword($hasher->hashPassword($user, 'password'));
        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function testUnauthenticatedRedirectsToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertResponseRedirects();
        $this->assertStringContainsString('connexion', $client->getResponse()->headers->get('Location') ?? '');
    }

    public function testAuthenticatedUserSees200WithDashboardHeader(): void
    {
        $client = static::createClient();
        $user = $this->createUser([], 'user1');
        $client->loginUser($user);

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#dashboard-header');
        $this->assertSelectorExists('#kpi-blocks');
    }

    public function testKpiBlocksContainThreeStats(): void
    {
        $client = static::createClient();
        $user = $this->createUser([], 'user2');
        $client->loginUser($user);

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertCount(3, $client->getCrawler()->filter('#kpi-blocks .stat-w'));
    }

    public function testQuickAccessGridContainsFourCardsForStandardUser(): void
    {
        $client = static::createClient();
        $user = $this->createUser([], 'user3');
        $client->loginUser($user);

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#quick-access-grid');
        $this->assertCount(4, $client->getCrawler()->filter('#quick-access-grid .action'));
    }

    public function testModerationCardAbsentForStandardUser(): void
    {
        $client = static::createClient();
        $user = $this->createUser([], 'user4');
        $client->loginUser($user);

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('#quick-access-grid .quick-card--moderation');
    }

    public function testModerationCardPresentForModerator(): void
    {
        $client = static::createClient();
        $mod = $this->createUser(['ROLE_MODERATOR'], 'mod1');
        $client->loginUser($mod);

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#quick-access-grid .quick-card--moderation');
        $this->assertCount(5, $client->getCrawler()->filter('#quick-access-grid .action'));
    }

    public function testForumBannerPresentForAuthenticatedUser(): void
    {
        $client = static::createClient();
        $user = $this->createUser([], 'user5');
        $client->loginUser($user);

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#forum-banner');
        $this->assertSelectorExists('#forum-banner a');
    }
}
