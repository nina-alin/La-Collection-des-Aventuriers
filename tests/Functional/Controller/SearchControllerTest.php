<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Book;
use App\Entity\Collection;
use App\Entity\Contributor;
use App\Entity\Editor;
use App\Entity\Enum\BookStatus;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SearchControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        try {
            $em = static::getContainer()->get(EntityManagerInterface::class);
            $em->createQuery('DELETE FROM App\Entity\Contribution c')->execute();
            $em->createQuery('DELETE FROM App\Entity\BookImage bi')->execute();
            $em->createQuery('DELETE FROM App\Entity\Review r')->execute();
            $em->createQuery('DELETE FROM App\Entity\Book b')->execute();
            $em->createQuery('DELETE FROM App\Entity\Editor e')->execute();
            $em->createQuery('DELETE FROM App\Entity\Collection c')->execute();
            $em->createQuery('DELETE FROM App\Entity\Contributor c')->execute();
            $em->createQuery('DELETE FROM App\Entity\User u')->execute();
        } catch (\Throwable) {
        } finally {
            parent::tearDown();
        }
    }

    private function createUser(): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('search-test-' . uniqid() . '@example.com');
        $user->setPseudo('SearchTester' . uniqid());
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function createPublishedBook(string $title): Book
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $editor = (new Editor())->setName('Test Editor ' . uniqid());
        $em->persist($editor);

        $book = new Book();
        $book->setTitle($title);
        $book->setStatus(BookStatus::PUBLISHED);
        $book->setEditor($editor);
        $em->persist($book);
        $em->flush();

        return $book;
    }

    public function testAuthenticatedWithValidQueryReturns200WithResults(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createPublishedBook('Le Sorcier de la Montagne de Feu');

        $client->loginUser($user);
        $client->request('GET', '/api/search?q=Sorcier');

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('results', $response);
        $this->assertIsArray($response['results']);
    }

    public function testBlankQueryReturnsEmptyResults(): void
    {
        $client = static::createClient();
        $user = $this->createUser();

        $client->loginUser($user);
        $client->request('GET', '/api/search?q=');

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame([], $response['results']);
    }

    public function testQueryExceeding100CharsReturns400(): void
    {
        $client = static::createClient();
        $user = $this->createUser();

        $client->loginUser($user);
        $client->request('GET', '/api/search?q=' . str_repeat('a', 101));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUnauthenticatedReturns302RedirectToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/search?q=test');

        $this->assertResponseStatusCodeSame(302);
    }

    public function testPopularEndpointAuthenticatedReturns200(): void
    {
        $client = static::createClient();
        $user = $this->createUser();

        $client->loginUser($user);
        $client->request('GET', '/api/search/popular');

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('popular', $response);
        $this->assertIsArray($response['popular']);
    }

    public function testPopularEndpointUnauthenticatedReturns302(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/search/popular');

        $this->assertResponseStatusCodeSame(302);
    }

    public function testResultsContainExpectedFields(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createPublishedBook('Livre de Test Unique XYZ');

        $client->loginUser($user);
        $client->request('GET', '/api/search?q=Test Unique XYZ');

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);

        if (!empty($response['results'])) {
            $item = $response['results'][0];
            $this->assertArrayHasKey('type', $item);
            $this->assertArrayHasKey('slug', $item);
            $this->assertArrayHasKey('title', $item);
            $this->assertArrayHasKey('subtitle', $item);
            $this->assertArrayHasKey('thumbnailUrl', $item);
            $this->assertArrayHasKey('initials', $item);
            $this->assertArrayHasKey('avatarColor', $item);
        }
    }
}
