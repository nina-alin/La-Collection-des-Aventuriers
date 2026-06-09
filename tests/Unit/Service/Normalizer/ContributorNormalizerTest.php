<?php

namespace App\Tests\Unit\Service\Normalizer;

use App\Entity\Contributor;
use App\Entity\Enum\SuggestionEntityType;
use App\Service\Normalizer\ContributorNormalizer;
use PHPUnit\Framework\TestCase;

class ContributorNormalizerTest extends TestCase
{
    private ContributorNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ContributorNormalizer();
    }

    private function makeContributor(): Contributor
    {
        $contributor = new Contributor();
        $contributor->setFirstName('Jean');
        $contributor->setLastName('Dupont');
        $contributor->setPseudo('JD');
        $contributor->setNationality('Française');
        $contributor->setBiography('Une biographie.');
        $contributor->setBirthDate(new \DateTime('1970-01-01'));
        $contributor->setDeathDate(null);

        return $contributor;
    }

    public function testNormalizeReturnsAllExpectedKeys(): void
    {
        $result = $this->normalizer->normalize($this->makeContributor());

        $this->assertArrayHasKey('firstName', $result);
        $this->assertArrayHasKey('lastName', $result);
        $this->assertArrayHasKey('pseudo', $result);
        $this->assertArrayHasKey('nationality', $result);
        $this->assertArrayHasKey('biography', $result);
        $this->assertArrayHasKey('birthDate', $result);
        $this->assertArrayHasKey('deathDate', $result);

        $this->assertSame('Jean', $result['firstName']);
        $this->assertSame('Dupont', $result['lastName']);
        $this->assertSame('1970-01-01', $result['birthDate']);
        $this->assertNull($result['deathDate']);
    }

    public function testGetFieldLabelsCoversAllKeys(): void
    {
        $labels = $this->normalizer->getFieldLabels();

        foreach (['firstName', 'lastName', 'pseudo', 'nationality', 'biography', 'birthDate', 'deathDate'] as $key) {
            $this->assertArrayHasKey($key, $labels);
        }
    }

    public function testGetFieldTypesReturnsBiographyAsText(): void
    {
        $types = $this->normalizer->getFieldTypes();

        $this->assertSame('text', $types['biography']);
        $this->assertSame('scalar', $types['firstName']);
        $this->assertSame('scalar', $types['lastName']);
        $this->assertSame('scalar', $types['pseudo']);
        $this->assertSame('scalar', $types['nationality']);
        $this->assertSame('scalar', $types['birthDate']);
        $this->assertSame('scalar', $types['deathDate']);
    }

    public function testGetSupportedTypeReturnsAuthor(): void
    {
        $this->assertSame(SuggestionEntityType::AUTHOR, $this->normalizer->getSupportedType());
    }
}
