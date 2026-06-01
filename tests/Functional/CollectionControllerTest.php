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

class CollectionControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\Book b')->execute();
        $em->createQuery('DELETE FROM App\Entity\Collection c')->execute();
        $em->createQuery('DELETE FROM App\Entity\Editor e WHERE e.name LIKE :prefix')
            ->setParameter('prefix', 'Test Editor%')
            ->execute();
        parent::tearDown();
    }

    private function createEditor(EntityManagerInterface $em): Editor
    {
        $editor = (new Editor())->setName('Test Editor ' . substr(uniqid(), -6));
        $em->persist($editor);
        return $editor;
    }

    private function createBook(EntityManagerInterface $em, Editor $editor, Collection $collection, ?int $volumeNumber = null): Book
    {
        $book = new Book();
        $book->setTitle('Book ' . substr(uniqid(), -6));
        $book->setStatus(BookStatus::PUBLISHED);
        $book->setEditor($editor);
        $book->setCollection($collection);
        if ($volumeNumber !== null) {
            $book->setVolumeNumber($volumeNumber);
        }
        $em->persist($book);
        return $book;
    }

    public function testValidSlugReturns200(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $collection = CollectionFactory::new(['nom' => 'Défis Fantastiques ' . substr(uniqid(), -6)]);
        $em->persist($collection);
        $em->flush();

        $client->request('GET', '/collections/' . $collection->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1.coll-title', $collection->getNom());
    }

    public function testUnknownSlugReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/collections/slug-inexistant-xyz');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPage2With25BooksReturns5Books(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $editor = $this->createEditor($em);
        $collection = CollectionFactory::new(['nom' => 'Collection Pagination ' . substr(uniqid(), -6)]);
        $em->persist($collection);
        $em->flush();

        for ($i = 1; $i <= 25; $i++) {
            $this->createBook($em, $editor, $collection, $i);
        }
        $em->flush();

        $client->request('GET', '/collections/' . $collection->getSlug() . '?page=2');

        $this->assertResponseIsSuccessful();
        $this->assertCount(5, $client->getCrawler()->filter('.tome'));
    }

    public function testPageBeyondMaxReturns404(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $collection = CollectionFactory::new(['nom' => 'Collection Beyond ' . substr(uniqid(), -6)]);
        $em->persist($collection);
        $em->flush();

        $client->request('GET', '/collections/' . $collection->getSlug() . '?page=99');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testNonNumericPageReturns404(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $collection = CollectionFactory::new(['nom' => 'Collection Alpha ' . substr(uniqid(), -6)]);
        $em->persist($collection);
        $em->flush();

        $client->request('GET', '/collections/' . $collection->getSlug() . '?page=abc');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testZeroPageReturns404(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $collection = CollectionFactory::new(['nom' => 'Collection Zero ' . substr(uniqid(), -6)]);
        $em->persist($collection);
        $em->flush();

        $client->request('GET', '/collections/' . $collection->getSlug() . '?page=0');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCollectionWithZeroBooksShowsEmptyState(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $collection = CollectionFactory::new(['nom' => 'Collection Vide ' . substr(uniqid(), -6)]);
        $em->persist($collection);
        $em->flush();

        $client->request('GET', '/collections/' . $collection->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.collection-empty', 'Aucun tome disponible');
    }

    public function testImageLogoRendersImg(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $collection = CollectionFactory::new([
            'nom'       => 'Collection Logo ' . substr(uniqid(), -6),
            'imageLogo' => 'defis-fantastiques.png',
        ]);
        $em->persist($collection);
        $em->flush();

        $client->request('GET', '/collections/' . $collection->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.coll-emblem-inner img[src*="defis-fantastiques.png"]');
    }

    public function testNoImageLogoRendersPlaceholder(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $collection = CollectionFactory::new(['nom' => 'Collection Placeholder ' . substr(uniqid(), -6)]);
        $em->persist($collection);
        $em->flush();

        $client->request('GET', '/collections/' . $collection->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.coll-emblem-glyph');
    }
}
