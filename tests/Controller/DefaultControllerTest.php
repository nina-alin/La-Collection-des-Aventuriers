<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testHomeReturns200(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('nav[aria-label="Navigation principale"]');
        $this->assertSelectorExists('footer[aria-label="Pied de page"]');
        $this->assertSelectorExists('[data-controller="toast-container"]');
    }
}
