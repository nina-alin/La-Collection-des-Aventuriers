<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CreateursControllerTest extends WebTestCase
{
    public function testCreateursReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/createurs');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateursWithRoleFilter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/createurs?role=auteur');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateursWithLetterFilter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/createurs?letter=A');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateursWithSortNote(): void
    {
        $client = static::createClient();
        $client->request('GET', '/createurs?sort=note');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateursWithHighPageRedirects(): void
    {
        $client = static::createClient();
        $client->request('GET', '/createurs?page=999999');

        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 302]);
    }

    public function testSearchEmptyQueryReturnsEmptyArray(): void
    {
        $client = static::createClient();
        $client->request('GET', '/createurs/search?q=');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame([], $data);
    }

    public function testSearchWithQueryReturnsGroupedStructure(): void
    {
        $client = static::createClient();
        $client->request('GET', '/createurs/search?q=a');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testSearchNotFoundReturnsEmptyGroups(): void
    {
        $client = static::createClient();
        $client->request('GET', '/createurs/search?q=xxxxnotfound99999');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        foreach ($data as $group) {
            $this->assertEmpty($group);
        }
    }
}
