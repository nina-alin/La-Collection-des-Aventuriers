<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContactControllerTest extends WebTestCase
{
    public function testGetContactReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/contact');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorExists('form#contact-form');
    }

    public function testPostContactSendWithValidDataReturns200(): void
    {
        $client = static::createClient();

        $client->request('GET', '/contact');
        $csrfToken = $client->getCrawler()->filter('input[name="_token"]')->attr('value');

        $client->request(
            'POST',
            '/contact/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                '_token'  => $csrfToken,
                'pseudo'  => 'TestUser',
                'email'   => 'test@example.com',
                'raison'  => 'autre',
                'message' => 'Bonjour, ceci est un test.',
            ])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testPostContactSendWithInvalidCsrfTokenReturns403(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/contact/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                '_token'  => 'invalid-token',
                'pseudo'  => 'TestUser',
                'email'   => 'test@example.com',
                'raison'  => 'autre',
                'message' => 'Hello',
            ])
        );

        $this->assertResponseStatusCodeSame(403);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function testPostContactSendWithMissingIdentityReturns422(): void
    {
        $client = static::createClient();

        $client->request('GET', '/contact');
        $csrfToken = $client->getCrawler()->filter('input[name="_token"]')->attr('value');

        $client->request(
            'POST',
            '/contact/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                '_token'  => $csrfToken,
                'email'   => 'test@example.com',
                'raison'  => 'autre',
                'message' => 'Hello',
            ])
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertNotEmpty($data['errors']);
    }

    public function testPostContactSendWithInvalidEmailReturns422(): void
    {
        $client = static::createClient();

        $client->request('GET', '/contact');
        $csrfToken = $client->getCrawler()->filter('input[name="_token"]')->attr('value');

        $client->request(
            'POST',
            '/contact/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                '_token'  => $csrfToken,
                'pseudo'  => 'TestUser',
                'email'   => 'not-an-email',
                'raison'  => 'autre',
                'message' => 'Hello',
            ])
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function testPostContactSendWithInvalidRaisonReturns422(): void
    {
        $client = static::createClient();

        $client->request('GET', '/contact');
        $csrfToken = $client->getCrawler()->filter('input[name="_token"]')->attr('value');

        $client->request(
            'POST',
            '/contact/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                '_token'  => $csrfToken,
                'pseudo'  => 'TestUser',
                'email'   => 'test@example.com',
                'raison'  => 'invalid-raison',
                'message' => 'Hello',
            ])
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function testPostContactSendWithEmptyMessageReturns422(): void
    {
        $client = static::createClient();

        $client->request('GET', '/contact');
        $csrfToken = $client->getCrawler()->filter('input[name="_token"]')->attr('value');

        $client->request(
            'POST',
            '/contact/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                '_token'  => $csrfToken,
                'pseudo'  => 'TestUser',
                'email'   => 'test@example.com',
                'raison'  => 'autre',
                'message' => '   ',
            ])
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function testPostContactSendWithMalformedJsonReturns400(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/contact/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'not valid json {'
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function testPostContactSendWithWrongContentTypeReturns400(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/contact/send',
            ['pseudo' => 'TestUser', 'email' => 'test@example.com', 'raison' => 'autre', 'message' => 'Hello']
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
    }
}
