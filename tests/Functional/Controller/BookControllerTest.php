<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Book;
use App\Entity\Editor;
use App\Entity\Enum\BookStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\BookImage bi')->execute();
        $em->createQuery('DELETE FROM App\Entity\Book b')->execute();
        $em->createQuery('DELETE FROM App\Entity\Editor e')->execute();
        parent::tearDown();
    }

    private function createBook(string $title, BookStatus $status): array
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $editor = (new Editor())->setName('Test Editor ' . uniqid());
        $em->persist($editor);

        $book = new Book();
        $book->setTitle($title);
        $book->setStatus($status);
        $book->setEditor($editor);
        $em->persist($book);
        $em->flush();

        return [$book, $editor];
    }

    public function testSC1PublishedBookReturns200WithTitle(): void
    {
        $client = static::createClient();
        [$book] = $this->createBook('Le Sorcier de la Montagne de Feu', BookStatus::PUBLISHED);

        $client->request('GET', '/livre/' . $book->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1.book-title', 'Le Sorcier de la Montagne de Feu');
    }

    public function testSC2FicheTechniqueGridPresent(): void
    {
        $client = static::createClient();
        [$book] = $this->createBook('Test Fiche Technique', BookStatus::PUBLISHED);

        $client->request('GET', '/livre/' . $book->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.meta-grid');
    }

    public function testSC3PendingBookWithNoAuthReturns404(): void
    {
        $client = static::createClient();
        [$book] = $this->createBook('Livre en attente', BookStatus::PENDING);

        $client->request('GET', '/livre/' . $book->getSlug());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testNonExistentSlugReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/livre/slug-qui-nexiste-pas');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testTaverneLinkPresent(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        [$book] = $this->createBook('Livre avec Taverne', BookStatus::PUBLISHED);
        $book->setTaverneUrl('https://example.com/taverne/livre-avec-taverne');
        $em->flush();

        $client->request('GET', '/livre/' . $book->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a.taverne-btn[target="_blank"]');
    }

    public function testTaverneLinkAbsentWhenNull(): void
    {
        $client = static::createClient();
        [$book] = $this->createBook('Livre sans Taverne', BookStatus::PUBLISHED);

        $client->request('GET', '/livre/' . $book->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('a.taverne-btn');
    }

    public function testActionBarHiddenForAnonymous(): void
    {
        $client = static::createClient();
        [$book] = $this->createBook('Livre sans action bar', BookStatus::PUBLISHED);

        $client->request('GET', '/livre/' . $book->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.actions-grid');
    }
}
