<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SuggestionControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\SuggestionRefusal sr')->execute();
        $em->createQuery('DELETE FROM App\Entity\Suggestion s')->execute();
        $em->createQuery('DELETE FROM App\Entity\User u')->execute();

        parent::tearDown();
    }

    private function createUser(array $roles = ['ROLE_USER'], string $email = 'user@example.com', string $pseudo = 'testuser'): User
    {
        $container = static::getContainer();
        $em        = $container->get(EntityManagerInterface::class);
        $hasher    = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPseudo($pseudo);
        $user->setRoles($roles);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function testUnauthenticatedAccessRedirectsToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/suggestions');
        $this->assertResponseRedirects('/connexion');
    }

    public function testRoleUserCanAccessSuggestionsPage(): void
    {
        $client = static::createClient();
        $user   = $this->createUser();

        $client->loginUser($user);
        $client->request('GET', '/suggestions');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateEntityWithoutCsrfHeaderReturns403(): void
    {
        $client = static::createClient();
        $user   = $this->createUser();

        $client->loginUser($user);
        $client->request('POST', '/api/suggestions/entities/author', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Test Author']));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateEntityUnauthenticatedRedirectsToLogin(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/suggestions/entities/author', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Test Author']));

        $this->assertResponseRedirects('/connexion');
    }

    public function testFeedEndpointUnauthenticatedRedirectsToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/suggestions/feed');
        $this->assertResponseRedirects('/connexion');
    }

    public function testFeedEndpointReturnsCorrectJsonShape(): void
    {
        $client = static::createClient();
        $user   = $this->createUser();

        $client->loginUser($user);
        $client->request('GET', '/api/suggestions/feed', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('suggestions', $data);
        $this->assertArrayHasKey('counts', $data);
        $this->assertArrayHasKey('pendingCount', $data);
        $this->assertArrayHasKey('total', $data['counts']);
        $this->assertArrayHasKey('pending', $data['counts']);
        $this->assertArrayHasKey('validated', $data['counts']);
        $this->assertArrayHasKey('refused', $data['counts']);
    }

    public function testAutocompleteUnauthenticatedRedirectsToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/suggestions/autocomplete/book?q=test');
        $this->assertResponseRedirects('/connexion');
    }

    public function testCheckUniqueUnauthenticatedRedirectsToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/suggestions/check-unique?field=title&value=test&entityType=book');
        $this->assertResponseRedirects('/connexion');
    }

    public function testCreateEntityWithBlankNameReturns400(): void
    {
        $client = static::createClient();
        $user   = $this->createUser();

        $client->loginUser($user);
        $client->request('GET', '/test-tokens/csrf/suggestion_entity_create');
        $csrfToken = json_decode($client->getResponse()->getContent(), true)['token'];

        $client->request('POST', '/api/suggestions/entities/author', [], [], [
            'CONTENT_TYPE'   => 'application/json',
            'HTTP_X-CSRF-Token' => $csrfToken,
        ], json_encode(['name' => '']));

        $this->assertResponseStatusCodeSame(400);
    }
}
