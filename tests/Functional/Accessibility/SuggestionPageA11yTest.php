<?php

declare(strict_types=1);

namespace App\Tests\Functional\Accessibility;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * WCAG 2.1 AA structural audit for the contributor suggestion page.
 * Full axe-core audit requires symfony/panther + chromium.
 */
class SuggestionPageA11yTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\User u')->execute();
        parent::tearDown();
    }

    private function createUser(): User
    {
        $container = static::getContainer();
        $em        = $container->get(EntityManagerInterface::class);
        $hasher    = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('a11y@example.com');
        $user->setPseudo('a11yuser');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function testSuggestionPageReturns200ForAuthenticatedUser(): void
    {
        $client = static::createClient();
        $user   = $this->createUser();

        $client->loginUser($user);
        $crawler = $client->request('GET', '/suggestions');

        $this->assertResponseIsSuccessful();
    }

    public function testDashboardBannerHasCorrectAriaLabel(): void
    {
        $client = static::createClient();
        $user   = $this->createUser();

        $client->loginUser($user);
        $crawler = $client->request('GET', '/suggestions');

        $this->assertResponseIsSuccessful();
        $banner = $crawler->filter('[role="region"][aria-label="Tableau de bord contributeur"]');
        $this->assertGreaterThan(0, $banner->count(), 'Dashboard banner must have role=region and correct aria-label');
    }

    public function testPageHasTablistForMobileNavigation(): void
    {
        $client = static::createClient();
        $user   = $this->createUser();

        $client->loginUser($user);
        $crawler = $client->request('GET', '/suggestions');

        $tablist = $crawler->filter('[role="tablist"]');
        $this->assertGreaterThan(0, $tablist->count(), 'Page must have a tablist for mobile navigation');

        $tabs = $crawler->filter('[role="tab"]');
        $this->assertGreaterThanOrEqual(2, $tabs->count(), 'Tablist must have at least 2 tabs');
    }

    public function testAllFormInputsHaveLabels(): void
    {
        $client = static::createClient();
        $user   = $this->createUser();

        $client->loginUser($user);
        $crawler = $client->request('GET', '/suggestions');

        $this->assertResponseIsSuccessful();
        $inputs = $crawler->filter('input:not([type="hidden"]):not([type="submit"]):not([type="radio"]):not([type="checkbox"])');

        foreach ($inputs as $input) {
            $inputId = $input->getAttribute('id');
            if (!$inputId) continue;
            $label = $crawler->filter("label[for=\"{$inputId}\"]");
            $this->assertGreaterThan(
                0,
                $label->count(),
                "Input #{$inputId} must have an associated <label>"
            );
        }
    }

    public function testSuggestionPageRendersBasicHtmlWithoutJavaScript(): void
    {
        $client = static::createClient();
        $user   = $this->createUser();

        $client->loginUser($user);
        $client->request('GET', '/suggestions');

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        $this->assertNotEmpty($content, 'Page must not render an empty body');
        $this->assertStringContainsString('<main', $content, 'Page must render a <main> element');
    }

    public function testSidePanelHasAriaLiveForPollingUpdates(): void
    {
        $client = static::createClient();
        $user   = $this->createUser();

        $client->loginUser($user);
        $crawler = $client->request('GET', '/suggestions');

        $liveRegion = $crawler->filter('[aria-live]');
        $this->assertGreaterThan(0, $liveRegion->count(), 'Page must have at least one aria-live region');
    }
}
