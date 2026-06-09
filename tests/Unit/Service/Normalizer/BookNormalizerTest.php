<?php

namespace App\Tests\Unit\Service\Normalizer;

use App\Entity\Book;
use App\Entity\Enum\SuggestionEntityType;
use App\Service\Normalizer\BookNormalizer;
use PHPUnit\Framework\TestCase;

class BookNormalizerTest extends TestCase
{
    private BookNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new BookNormalizer();
    }

    public function testNormalizeReturnsAllExpectedKeys(): void
    {
        $book = new Book();
        $book->setTitle('Le Seigneur des Anneaux');
        $book->setOriginalTitle('The Lord of the Rings');
        $book->setIsbn('978-2-07-036024-5');
        $book->setPages(1200);
        $book->setParagraphs(100);
        $book->setFrenchPublicationYear(1972);
        $book->setOriginalPublicationYear(1954);
        $book->setEditionInfo('Édition Gallimard');
        $book->setSaga('Terre du Milieu');

        $result = $this->normalizer->normalize($book);

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('originalTitle', $result);
        $this->assertArrayHasKey('isbn', $result);
        $this->assertArrayHasKey('pages', $result);
        $this->assertArrayHasKey('paragraphs', $result);
        $this->assertArrayHasKey('frenchPublicationYear', $result);
        $this->assertArrayHasKey('originalPublicationYear', $result);
        $this->assertArrayHasKey('editionInfo', $result);
        $this->assertArrayHasKey('saga', $result);

        $this->assertSame('Le Seigneur des Anneaux', $result['title']);
        $this->assertSame('The Lord of the Rings', $result['originalTitle']);
        $this->assertSame('978-2-07-036024-5', $result['isbn']);
        $this->assertSame(1200, $result['pages']);
        $this->assertSame(100, $result['paragraphs']);
        $this->assertSame(1972, $result['frenchPublicationYear']);
        $this->assertSame(1954, $result['originalPublicationYear']);
        $this->assertSame('Édition Gallimard', $result['editionInfo']);
        $this->assertSame('Terre du Milieu', $result['saga']);
    }

    public function testGetFieldLabelsCoversAllKeys(): void
    {
        $labels = $this->normalizer->getFieldLabels();

        $expectedKeys = ['title', 'originalTitle', 'isbn', 'pages', 'paragraphs',
            'frenchPublicationYear', 'originalPublicationYear', 'editionInfo', 'saga'];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $labels);
            $this->assertIsString($labels[$key]);
        }
    }

    public function testGetFieldTypesReturnsCorrectTypes(): void
    {
        $types = $this->normalizer->getFieldTypes();

        $this->assertSame('text', $types['title']);
        $this->assertSame('scalar', $types['originalTitle']);
        $this->assertSame('scalar', $types['isbn']);
        $this->assertSame('scalar', $types['pages']);
        $this->assertSame('scalar', $types['paragraphs']);
        $this->assertSame('scalar', $types['frenchPublicationYear']);
        $this->assertSame('scalar', $types['originalPublicationYear']);
        $this->assertSame('scalar', $types['editionInfo']);
        $this->assertSame('scalar', $types['saga']);
    }

    public function testGetSupportedTypeReturnsBook(): void
    {
        $this->assertSame(SuggestionEntityType::BOOK, $this->normalizer->getSupportedType());
    }
}
