<?php

namespace App\Tests\Unit\Service\Normalizer;

use App\Entity\Collection;
use App\Entity\Enum\GenreCollection;
use App\Entity\Enum\StatutCollection;
use App\Entity\Enum\SuggestionEntityType;
use App\Service\Normalizer\CollectionNormalizer;
use PHPUnit\Framework\TestCase;

class CollectionNormalizerTest extends TestCase
{
    private CollectionNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new CollectionNormalizer();
    }

    private function makeCollection(): Collection
    {
        $collection = new Collection();
        $collection->setNom('Défis Fantastiques');
        $collection->setSlug('defis-fantastiques');
        $collection->setGenre(GenreCollection::MEDIEVAL_FANTASTIQUE);
        $collection->setStatut(StatutCollection::EN_COURS);
        $collection->setDescription('Collection emblématique.');

        return $collection;
    }

    public function testNormalizeReturnsAllExpectedKeys(): void
    {
        $result = $this->normalizer->normalize($this->makeCollection());

        $this->assertArrayHasKey('nom', $result);
        $this->assertArrayHasKey('slug', $result);
        $this->assertArrayHasKey('genre', $result);
        $this->assertArrayHasKey('statut', $result);
        $this->assertArrayHasKey('description', $result);

        $this->assertSame('Défis Fantastiques', $result['nom']);
        $this->assertSame('defis-fantastiques', $result['slug']);
        $this->assertSame('medieval-fantastique', $result['genre']);
        $this->assertSame('en-cours', $result['statut']);
        $this->assertSame('Collection emblématique.', $result['description']);
    }

    public function testGetFieldLabelsCoversAllKeys(): void
    {
        $labels = $this->normalizer->getFieldLabels();

        foreach (['nom', 'slug', 'genre', 'statut', 'description'] as $key) {
            $this->assertArrayHasKey($key, $labels);
        }
    }

    public function testGetFieldTypesReturnsDescriptionAsText(): void
    {
        $types = $this->normalizer->getFieldTypes();

        $this->assertSame('text', $types['description']);
        $this->assertSame('scalar', $types['nom']);
        $this->assertSame('scalar', $types['slug']);
        $this->assertSame('scalar', $types['genre']);
        $this->assertSame('scalar', $types['statut']);
    }

    public function testGetSupportedTypeReturnsCollection(): void
    {
        $this->assertSame(SuggestionEntityType::COLLECTION, $this->normalizer->getSupportedType());
    }
}
