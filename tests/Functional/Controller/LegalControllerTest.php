<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LegalControllerTest extends WebTestCase
{
    public function testMentionsLegalesReturnsOk(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mentions-legales');
        $this->assertResponseIsSuccessful();
    }
}
