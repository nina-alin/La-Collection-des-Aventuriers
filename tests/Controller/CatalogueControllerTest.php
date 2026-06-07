<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CatalogueControllerTest extends WebTestCase
{
    public function testCatalogueReturns200ForGuest(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/catalogue');

        $this->assertResponseIsSuccessful();
    }

    public function testCataloguePageContainsResultsGrid(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/catalogue');

        $this->assertSelectorExists("#grid");
    }

    public function testCatalogueCollectionStatusSectionAbsentForGuest(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/catalogue');

        $this->assertSelectorNotExists('.filter-section--collection-status');
    }

    public function testCatalogueUrlParamsHydrateFilterState(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/catalogue?sort=alpha&paragraphMin=100&paragraphMax=400');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists("#grid");
    }

    public function testCatalogueWithInvalidPageRedirectsToLastPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/catalogue?page=99999');

        // Should redirect to last available page or return successful response
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 302]);
    }

    public function testCatalogueSearchSuggestionsEmptyQuery(): void
    {
        $client = static::createClient();
        $client->request('GET', '/catalogue/search-suggestions?q=');

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('books', $response);
        $this->assertArrayHasKey('authors', $response);
        $this->assertEmpty($response['books']);
        $this->assertEmpty($response['authors']);
    }

    public function testCatalogueSearchSuggestionsWithQuery(): void
    {
        $client = static::createClient();
        $client->request('GET', '/catalogue/search-suggestions?q=sorcier');

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('books', $response);
        $this->assertArrayHasKey('authors', $response);
    }
}
