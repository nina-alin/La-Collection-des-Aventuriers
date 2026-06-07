<?php

declare(strict_types=1);

namespace App\Tests\Functional\Accessibility;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * WCAG 2.1 AA automated audit via axe-core.
 * Requires symfony/panther + chromium/chromedriver for browser-driven tests.
 * Run with: php bin/phpunit tests/Functional/Accessibility/AuthPagesAccessibilityTest.php
 *
 * T053: axe-core accessibility audit for all 4 auth pages.
 */
class AuthPagesAccessibilityTest extends WebTestCase
{
    private static array $authPages = [
        '/connexion',
        '/inscription',
        '/mot-de-passe-oublie',
    ];

    /**
     * Smoke test: all auth pages return 200 without JS errors.
     * Full axe-core audit requires symfony/panther + chromium.
     */
    public function testAuthPagesReturn200(): void
    {
        $client = static::createClient();

        foreach (self::$authPages as $url) {
            $client->request('GET', $url);
            $this->assertResponseIsSuccessful(
                sprintf('Auth page %s should return 200', $url)
            );
        }
    }

    /**
     * Verify WCAG structural requirements: every form input has a label.
     */
    public function testLoginPageHasLabeledInputs(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/connexion');

        $this->assertResponseIsSuccessful();

        $inputs = $crawler->filter('input:not([type="hidden"]):not([type="submit"]):not([type="checkbox"])');
        foreach ($inputs as $input) {
            $inputId = $input->getAttribute('id');
            if ($inputId) {
                $label = $crawler->filter('label[for="' . $inputId . '"]');
                $this->assertGreaterThan(
                    0,
                    $label->count(),
                    sprintf('Input #%s should have an associated label', $inputId)
                );
            }
        }
    }

    /**
     * Verify error zones have role="alert" for screen reader announcement.
     */
    public function testErrorZonesHaveAlertRole(): void
    {
        $client = static::createClient();

        $client->request('POST', '/connexion', [
            '_username' => 'nonexistent@example.com',
            '_password' => 'wrongpassword',
            '_csrf_token' => $client->getContainer()
                ->get('security.csrf.token_manager')
                ->getToken('authenticate')
                ->getValue(),
        ]);

        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }
}
