<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\DataFixtures\Factory\CollectionFactory;
use App\Entity\Book;
use App\Entity\Collection;
use App\Entity\Editor;
use App\Entity\Enum\BookStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookCollectionBreadcrumbTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\BookImage bi')->execute();
        $em->createQuery('DELETE FROM App\Entity\Book b')->execute();
        $em->createQuery('DELETE FROM App\Entity\Collection c')->execute();
        $em->createQuery('DELETE FROM App\Entity\Editor e WHERE e.name LIKE :prefix')
            ->setParameter('prefix', 'Test Editor%')
            ->execute();
        parent::tearDown();
    }

    private function createBook(EntityManagerInterface $em, string $title, ?Collection $collection = null): Book
    {
        $editor = (new Editor())->setName('Test Editor ' . substr(uniqid(), -6));
        $em->persist($editor);

        $book = new Book();
        $book->setTitle($title);
        $book->setStatus(BookStatus::PUBLISHED);
        $book->setEditor($editor);
        if ($collection !== null) {
            $book->setCollection($collection);
            $book->setSaga($collection->getNom());
            $book->setVolumeNumber(1);
        }
        $em->persist($book);
        return $book;
    }

    public function testBookWithCollectionBreadcrumbHasCollectionLink(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $collection = CollectionFactory::new(['nom' => 'Défis Fantastiques ' . substr(uniqid(), -6)]);
        $em->persist($collection);
        $em->flush();

        $book = $this->createBook($em, 'Le Sorcier de la Montagne de Feu', $collection);
        $em->flush();

        $client->request('GET', '/livre/' . $book->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists(
            sprintf('.crumbs a[href*="/collections/%s"]', $collection->getSlug())
        );
    }

    public function testBookWithoutCollectionBreadcrumbShowsTitleOnly(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $book = $this->createBook($em, 'Livre Sans Collection');
        $em->flush();

        $client->request('GET', '/livre/' . $book->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.crumbs .here', 'Livre Sans Collection');
        $this->assertSelectorNotExists('.crumbs a[href*="/collections/"]');
    }

    public function testSagaVolumeRowWithCollectionHasLink(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $collection = CollectionFactory::new(['nom' => 'Collection Lien ' . substr(uniqid(), -6)]);
        $em->persist($collection);
        $em->flush();

        $book = $this->createBook($em, 'Tome avec Collection', $collection);
        $em->flush();

        $client->request('GET', '/livre/' . $book->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists(
            sprintf('.ttl-eyebrow a[href="/collections/%s"]', $collection->getSlug())
        );
    }
}
