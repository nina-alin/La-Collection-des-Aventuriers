<?php

namespace App\Twig\Components\Catalogue;

use App\Dto\ActiveFilterState;
use App\Repository\BookRepository;
use App\Repository\EditorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class FilterPanelComponent extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public array $selectedEditors = [];

    #[LiveProp(writable: true)]
    public ?int $paragraphMin = null;

    #[LiveProp(writable: true)]
    public ?int $paragraphMax = null;

    #[LiveProp(writable: true)]
    public ?string $collectionStatus = null;

    #[LiveProp(writable: true)]
    public bool $onlyFavorites = false;

    #[LiveProp(writable: true)]
    public bool $hideModeration = false;

    #[LiveProp(writable: true)]
    public string $editorSearch = '';

    #[LiveProp(writable: true)]
    public bool $showAllEditors = false;

    /** @var array{min: int, max: int} */
    public array $paragraphBounds = ['min' => 0, 'max' => 999];

    private const LIST_LIMIT = 5;

    /** @var array<\App\Entity\Editor>|null */
    private ?array $cachedEditors = null;

    /** Applied state (set on mount, restored by clearPanel) */
    #[LiveProp]
    public string $appliedSort = 'note-desc';

    #[LiveProp]
    public array $appliedEditors = [];

    #[LiveProp]
    public ?int $appliedParagraphMin = null;

    #[LiveProp]
    public ?int $appliedParagraphMax = null;

    #[LiveProp]
    public ?string $appliedCollectionStatus = null;

    #[LiveProp]
    public bool $appliedOnlyFavorites = false;

    #[LiveProp]
    public bool $appliedHideModeration = false;

    #[LiveProp]
    public ?string $appliedSearchQuery = null;

    public function __construct(
        private readonly BookRepository   $bookRepository,
        private readonly EditorRepository $editorRepository,
    ) {}

    public function mount(ActiveFilterState $activeFilterState, array $paragraphBounds = ['min' => 0, 'max' => 999]): void
    {
        $this->paragraphBounds  = $paragraphBounds;
        $this->selectedEditors  = $activeFilterState->editors;
        $this->paragraphMin         = $activeFilterState->paragraphMin;
        $this->paragraphMax     = $activeFilterState->paragraphMax;
        $this->collectionStatus = $activeFilterState->collectionStatus;
        $this->onlyFavorites    = $activeFilterState->onlyFavorites;
        $this->hideModeration   = $activeFilterState->hideModeration;

        $this->appliedSort    = $activeFilterState->sort;
        $this->appliedEditors = $activeFilterState->editors;
        $this->appliedParagraphMin     = $activeFilterState->paragraphMin;
        $this->appliedParagraphMax     = $activeFilterState->paragraphMax;
        $this->appliedCollectionStatus = $activeFilterState->collectionStatus;
        $this->appliedOnlyFavorites    = $activeFilterState->onlyFavorites;
        $this->appliedHideModeration   = $activeFilterState->hideModeration;
        $this->appliedSearchQuery      = $activeFilterState->searchQuery;
    }

    public function getExpectedCount(): int
    {
        try {
            $state = $this->buildDraftState();
            return $this->bookRepository->countFiltered($state);
        } catch (\Throwable) {
            return 0;
        }
    }

    public function getVisibleEditors(): array
    {
        $all = $this->cachedEditors ??= $this->editorRepository->findByNameSearch($this->editorSearch, 20);
        if (!$this->showAllEditors && $this->editorSearch === '') {
            return array_slice($all, 0, self::LIST_LIMIT);
        }
        return $all;
    }

    public function getEditorsMore(): int
    {
        $all = $this->cachedEditors ??= $this->editorRepository->findByNameSearch($this->editorSearch, 20);
        return max(0, count($all) - self::LIST_LIMIT);
    }

    #[LiveAction]
    public function showMoreEditors(): void
    {
        $this->showAllEditors = true;
    }

    #[LiveAction]
    public function setPreset(#[LiveArg] int $min, #[LiveArg] int $max): void
    {
        $this->paragraphMin = $min;
        $this->paragraphMax = $max;
    }

    #[LiveAction]
    public function applyFilters(): RedirectResponse
    {
        $state = $this->buildDraftState();
        $url   = '/catalogue';
        $params = $state->toUrlParams();
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return new RedirectResponse($url);
    }

    #[LiveAction]
    public function clearPanel(): void
    {
        $this->selectedEditors = $this->appliedEditors;
        $this->paragraphMin    = $this->appliedParagraphMin;
        $this->paragraphMax         = $this->appliedParagraphMax;
        $this->collectionStatus     = $this->appliedCollectionStatus;
        $this->onlyFavorites        = $this->appliedOnlyFavorites;
        $this->hideModeration       = $this->appliedHideModeration;
        $this->editorSearch         = '';
        $this->showAllEditors = false;
    }

    private function buildDraftState(): ActiveFilterState
    {
        return new ActiveFilterState(
            sort: $this->appliedSort,
            editors: array_values(array_filter(array_map('intval', $this->selectedEditors), fn($id) => $id > 0)),
            paragraphMin: $this->paragraphMin,
            paragraphMax: $this->paragraphMax,
            collectionStatus: $this->collectionStatus,
            onlyFavorites: $this->onlyFavorites,
            hideModeration: $this->hideModeration,
            searchQuery: $this->appliedSearchQuery,
        );
    }
}
