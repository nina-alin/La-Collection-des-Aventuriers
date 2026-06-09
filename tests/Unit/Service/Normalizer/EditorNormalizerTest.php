<?php

namespace App\Tests\Unit\Service\Normalizer;

use App\Entity\Editor;
use App\Entity\Enum\SuggestionEntityType;
use App\Service\Normalizer\EditorNormalizer;
use PHPUnit\Framework\TestCase;

class EditorNormalizerTest extends TestCase
{
    private EditorNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new EditorNormalizer();
    }

    public function testNormalizeReturnsName(): void
    {
        $editor = new Editor();
        $editor->setName('Gallimard');

        $result = $this->normalizer->normalize($editor);

        $this->assertArrayHasKey('name', $result);
        $this->assertSame('Gallimard', $result['name']);
    }

    public function testGetFieldLabelsCoversAllKeys(): void
    {
        $labels = $this->normalizer->getFieldLabels();

        $this->assertArrayHasKey('name', $labels);
        $this->assertIsString($labels['name']);
    }

    public function testGetFieldTypesReturnsNameAsScalar(): void
    {
        $types = $this->normalizer->getFieldTypes();

        $this->assertSame('scalar', $types['name']);
    }

    public function testGetSupportedTypeReturnsEditor(): void
    {
        $this->assertSame(SuggestionEntityType::EDITOR, $this->normalizer->getSupportedType());
    }
}
