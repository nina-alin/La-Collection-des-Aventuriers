<?php

namespace App\Tests\Twig\Components;

use App\Dto\ActiveFilterState;
use App\Repository\BookRepository;
use App\Repository\EditorRepository;
use App\Twig\Components\Catalogue\FilterPanelComponent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FilterPanelComponentTest extends TestCase
{
    private BookRepository&MockObject $bookRepository;
    private EditorRepository&MockObject $editorRepository;
    private FilterPanelComponent $component;

    protected function setUp(): void
    {
        $this->bookRepository   = $this->createMock(BookRepository::class);
        $this->editorRepository = $this->createMock(EditorRepository::class);
        $this->component        = new FilterPanelComponent($this->bookRepository, $this->editorRepository);
    }

    public function testExpectedCountCallsBookRepository(): void
    {
        $this->bookRepository
            ->expects($this->once())
            ->method('countFiltered')
            ->willReturn(42);

        $this->component->sort = 'note-desc';

        $count = $this->component->getExpectedCount();

        $this->assertSame(42, $count);
    }

    public function testExpectedCountFallsBackToZeroOnException(): void
    {
        $this->bookRepository
            ->method('countFiltered')
            ->willThrowException(new \RuntimeException('DB error'));

        $count = $this->component->getExpectedCount();

        $this->assertSame(0, $count);
    }

    public function testClearPanelResetsDraftToAppliedState(): void
    {
        $appliedState = new ActiveFilterState(
            sort: 'alpha',
            editors: [3, 7],
            paragraphMin: 100,
            paragraphMax: 400,
            onlyFavorites: true,
        );
        $this->component->mount($appliedState);

        // Mutate draft state
        $this->component->sort            = 'note-desc';
        $this->component->selectedEditors = [1];
        $this->component->paragraphMin    = null;
        $this->component->onlyFavorites   = false;

        // Clear resets to last applied
        $this->component->clearPanel();

        $this->assertSame('alpha', $this->component->sort);
        $this->assertSame([3, 7], $this->component->selectedEditors);
        $this->assertSame(100, $this->component->paragraphMin);
        $this->assertSame(400, $this->component->paragraphMax);
        $this->assertTrue($this->component->onlyFavorites);
    }

    public function testMountInitializesDraftFromActiveFilterState(): void
    {
        $state = new ActiveFilterState(
            sort: 'parution-fr',
            editors: [5],
            collectionStatus: 'lu',
        );

        $this->component->mount($state);

        $this->assertSame('parution-fr', $this->component->sort);
        $this->assertSame([5], $this->component->selectedEditors);
        $this->assertSame('lu', $this->component->collectionStatus);
    }

    public function testGetVisibleEditorsCallsEditorRepository(): void
    {
        $this->editorRepository
            ->expects($this->once())
            ->method('findByNameSearch')
            ->with('')
            ->willReturn([]);

        $this->component->editorSearch = '';
        $result = $this->component->getVisibleEditors();

        $this->assertIsArray($result);
    }
}
