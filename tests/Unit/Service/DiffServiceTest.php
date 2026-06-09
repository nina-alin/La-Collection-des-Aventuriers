<?php

namespace App\Tests\Unit\Service;

use App\Dto\DiffFieldStatus;
use App\Service\DiffService;
use App\Service\Normalizer\EntityNormalizerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

class DiffServiceTest extends TestCase
{
    private DiffService $service;
    /** @var ServiceLocator&MockObject */
    private ServiceLocator $locator;

    protected function setUp(): void
    {
        $this->locator = $this->createMock(ServiceLocator::class);
        $this->service = new DiffService($this->locator);
    }

    public function testAddedWhenKeyOnlyInProposed(): void
    {
        $result = $this->service->compute(
            [],
            ['title' => 'Nouveau titre'],
            ['title' => 'Titre'],
            ['title' => 'scalar'],
        );

        $this->assertSame(1, $result->addedCount);
        $this->assertSame(0, $result->replacedCount);
        $this->assertSame(0, $result->removedCount);
        $this->assertSame(DiffFieldStatus::ADDED, $result->fields[0]->status);
        $this->assertNull($result->fields[0]->currentValue);
        $this->assertSame('Nouveau titre', $result->fields[0]->proposedValue);
    }

    public function testRemovedWhenKeyOnlyInCurrent(): void
    {
        $result = $this->service->compute(
            ['isbn' => '978-000'],
            [],
            ['isbn' => 'ISBN'],
            ['isbn' => 'scalar'],
        );

        $this->assertSame(0, $result->addedCount);
        $this->assertSame(0, $result->replacedCount);
        $this->assertSame(1, $result->removedCount);
        $this->assertSame(DiffFieldStatus::REMOVED, $result->fields[0]->status);
        $this->assertSame('978-000', $result->fields[0]->currentValue);
        $this->assertNull($result->fields[0]->proposedValue);
    }

    public function testReplacedWhenValuesDiffer(): void
    {
        $result = $this->service->compute(
            ['title' => 'Ancien titre'],
            ['title' => 'Nouveau titre'],
            ['title' => 'Titre'],
            ['title' => 'scalar'],
        );

        $this->assertSame(1, $result->replacedCount);
        $this->assertSame(DiffFieldStatus::REPLACED, $result->fields[0]->status);
        $this->assertSame('Ancien titre', $result->fields[0]->currentValue);
        $this->assertSame('Nouveau titre', $result->fields[0]->proposedValue);
    }

    public function testUnchangedWhenValuesIdentical(): void
    {
        $result = $this->service->compute(
            ['pages' => 300],
            ['pages' => 300],
            ['pages' => 'Pages'],
            ['pages' => 'scalar'],
        );

        $this->assertSame(0, $result->addedCount);
        $this->assertSame(0, $result->replacedCount);
        $this->assertSame(0, $result->removedCount);
        $this->assertSame(DiffFieldStatus::UNCHANGED, $result->fields[0]->status);
    }

    public function testTextFieldReplacedHasNonNullAnnotatedHtml(): void
    {
        $result = $this->service->compute(
            ['biography' => 'Ancienne biographie du contributeur.'],
            ['biography' => 'Nouvelle biographie du contributeur.'],
            ['biography' => 'Biographie'],
            ['biography' => 'text'],
        );

        $this->assertSame(DiffFieldStatus::REPLACED, $result->fields[0]->status);
        $this->assertNotNull($result->fields[0]->annotatedHtml);
        $this->assertIsString($result->fields[0]->annotatedHtml);
    }

    public function testScalarFieldReplacedHasNullAnnotatedHtml(): void
    {
        $result = $this->service->compute(
            ['isbn' => '978-old'],
            ['isbn' => '978-new'],
            ['isbn' => 'ISBN'],
            ['isbn' => 'scalar'],
        );

        $this->assertSame(DiffFieldStatus::REPLACED, $result->fields[0]->status);
        $this->assertNull($result->fields[0]->annotatedHtml);
    }

    public function testCountersAreCorrect(): void
    {
        $result = $this->service->compute(
            ['a' => 1, 'b' => 2, 'c' => 3],
            ['a' => 99, 'b' => 2, 'd' => 4],
            ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'],
            ['a' => 'scalar', 'b' => 'scalar', 'c' => 'scalar', 'd' => 'scalar'],
        );

        $this->assertSame(1, $result->addedCount);
        $this->assertSame(1, $result->replacedCount);
        $this->assertSame(1, $result->removedCount);
        $this->assertTrue($result->hasChanges());
    }

    public function testNewEntryAllFieldsAdded(): void
    {
        $result = $this->service->compute(
            [],
            ['title' => 'Un livre', 'isbn' => '978-xxx'],
            ['title' => 'Titre', 'isbn' => 'ISBN'],
            ['title' => 'text', 'isbn' => 'scalar'],
        );

        $this->assertSame(2, $result->addedCount);
        $this->assertSame(0, $result->replacedCount);
        $this->assertSame(0, $result->removedCount);

        foreach ($result->fields as $field) {
            $this->assertSame(DiffFieldStatus::ADDED, $field->status);
        }
    }

    public function testNewEntryFieldTypesPreserved(): void
    {
        $result = $this->service->compute(
            [],
            ['biography' => 'Bio.', 'name' => 'Nom'],
            ['biography' => 'Biographie', 'name' => 'Nom'],
            ['biography' => 'text', 'name' => 'scalar'],
        );

        $fields = array_column($result->fields, null, 'key');
        $this->assertSame('text', $fields['biography']->type);
        $this->assertSame('scalar', $fields['name']->type);
    }

    public function testHasChangesReturnsFalseWhenAllUnchanged(): void
    {
        $result = $this->service->compute(
            ['a' => 1],
            ['a' => 1],
            ['a' => 'A'],
            ['a' => 'scalar'],
        );

        $this->assertFalse($result->hasChanges());
    }
}
