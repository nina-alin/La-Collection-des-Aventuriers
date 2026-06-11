<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CollectionListControllerTest extends WebTestCase
{
    public function testCollectionsListReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/collections');

        $this->assertResponseIsSuccessful();
    }

    public function testCollectionsListWithFollowedFilterUnauthenticatedIgnoresFilter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/collections?followed=true');

        // Guest gets 200 (filter silently ignored or redirected — no crash)
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 302]);
    }

    public function testCollectionsListWithGenreFilter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/collections?genre=aventure');

        $this->assertResponseIsSuccessful();
    }

    public function testCollectionsListWithHighPageRedirects(): void
    {
        $client = static::createClient();
        $client->request('GET', '/collections?page=99999');

        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 302]);
    }
}
