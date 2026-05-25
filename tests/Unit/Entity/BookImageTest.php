<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Book;
use App\Entity\BookImage;
use App\Entity\Editor;
use App\Entity\Enum\BookImageTab;
use App\Entity\Enum\BookStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BookImageTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\BookImage bi')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Book b')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Editor e')->execute();
        parent::tearDown();
    }

    private function createBook(string $title = 'Test Book'): Book
    {
        $editor = (new Editor())->setName('Test Editor ' . uniqid());
        $this->em->persist($editor);

        $book = new Book();
        $book->setTitle($title);
        $book->setEditor($editor);
        $book->setStatus(BookStatus::PUBLISHED);
        $this->em->persist($book);
        $this->em->flush();

        return $book;
    }

    public function testFieldMappingRoundTrip(): void
    {
        $book = $this->createBook();

        $image = new BookImage();
        $image->setTab(BookImageTab::TOME);
        $image->setImagePath('uploads/books/gallery/test.jpg');
        $image->setBook($book);

        $this->em->persist($image);
        $this->em->flush();
        $this->em->clear();

        $found = $this->em->getRepository(BookImage::class)->find($image->getId());

        $this->assertNotNull($found);
        $this->assertSame(BookImageTab::TOME, $found->getTab());
        $this->assertSame('uploads/books/gallery/test.jpg', $found->getImagePath());
    }

    public function testUniqueBookTabConstraintEnforced(): void
    {
        $book = $this->createBook('Unique Tab Book');

        $img1 = (new BookImage())->setTab(BookImageTab::DOS)->setImagePath('a.jpg')->setBook($book);
        $img2 = (new BookImage())->setTab(BookImageTab::DOS)->setImagePath('b.jpg')->setBook($book);

        $this->em->persist($img1);
        $this->em->flush();
        $this->em->persist($img2);

        $this->expectException(\Exception::class);
        $this->em->flush();
    }

    public function testDifferentTabsSameBookAllowed(): void
    {
        $book = $this->createBook('Multi Tab Book');

        foreach ([BookImageTab::TOME, BookImageTab::DOS, BookImageTab::TRANCHE] as $tab) {
            $img = (new BookImage())->setTab($tab)->setImagePath($tab->value . '.jpg')->setBook($book);
            $this->em->persist($img);
        }
        $this->em->flush();

        $this->em->clear();
        $found = $this->em->getRepository(Book::class)->find($book->getId());
        $this->assertCount(3, $found->getGalleryImages());
    }
}
