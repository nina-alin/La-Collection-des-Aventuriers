<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testHomeRedirectsToLoginWhenUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseRedirects();
    }
}
