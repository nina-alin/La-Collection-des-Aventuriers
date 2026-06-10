<?php

declare(strict_types=1);

namespace App\Twig\Components\Contributeur;

use App\Dto\ContributorFilterState;
use App\Repository\CollectionRepository;
use App\Service\ContributeurService;
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

    // Writable draft filter props
    #[LiveProp(writable: true)]
    public array $selectedCollectionIds = [];

    #[LiveProp(writable: true)]
    public string $collectionSearch = '';

    #[LiveProp(writable: true)]
    public ?int $periodMin = null;

    #[LiveProp(writable: true)]
    public ?int $periodMax = null;

    #[LiveProp(writable: true)]
    public string $nationalitySearch = '';

    #[LiveProp(writable: true)]
    public ?string $bookCountRange = null;

    #[LiveProp(writable: true)]
    public bool $onlyFollowed = false;

    #[LiveProp(writable: true)]
    public bool $showAllCollections = false;

    // Applied (non-writable) props — preserved through Apply
    #[LiveProp]
    public string $appliedRole = 'tous';

    #[LiveProp]
    public ?string $appliedLetter = null;

    #[LiveProp]
    public string $appliedSort = 'az';

    #[LiveProp]
    public int $appliedPage = 1;

    private const LIST_LIMIT = 5;

    private ?array $cachedCollections = null;

    public function __construct(
        private readonly ContributeurService  $service,
        private readonly CollectionRepository $collectionRepository,
    ) {}

    public function mount(ContributorFilterState $state): void
    {
        $this->selectedCollectionIds = $state->collectionIds;
        $this->periodMin             = $state->periodMin;
        $this->periodMax             = $state->periodMax;
        $this->nationalitySearch     = $state->nationality ?? '';
        $this->bookCountRange        = $state->bookCountRange;
        $this->onlyFollowed          = $state->onlyFollowed;

        $this->appliedRole   = $state->role;
        $this->appliedLetter = $state->letter;
        $this->appliedSort   = $state->sort;
        $this->appliedPage   = $state->page;
    }

    public function getExpectedCount(): int
    {
        try {
            return $this->service->countFiltered($this->buildDraftState());
        } catch (\Throwable) {
            return 0;
        }
    }

    public function getVisibleCollections(): array
    {
        $all = $this->cachedCollections ??= $this->collectionRepository->findAll();

        if ($this->collectionSearch !== '') {
            $q   = mb_strtolower($this->collectionSearch);
            $all = array_values(array_filter(
                $all,
                fn($c) => str_contains(mb_strtolower($c->getNom()), $q)
            ));
        }

        if ($this->showAllCollections) {
            return $all;
        }

        return array_slice($all, 0, self::LIST_LIMIT);
    }

    public function getCollectionsMore(): int
    {
        $all = $this->cachedCollections ??= $this->collectionRepository->findAll();
        return max(0, count($all) - self::LIST_LIMIT);
    }

    #[LiveAction]
    public function setDecade(#[LiveArg] int $min, #[LiveArg] ?int $max = null): void
    {
        $this->periodMin = $min;
        $this->periodMax = $max > 0 ? $max : null;
    }

    #[LiveAction]
    public function applyFilters(): RedirectResponse
    {
        $state = $this->buildDraftState();
        $url   = '/createurs';
        $params = $state->toUrlParams();
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return new RedirectResponse($url);
    }

    #[LiveAction]
    public function clearPanel(): void
    {
        $this->selectedCollectionIds = [];
        $this->periodMin             = null;
        $this->periodMax             = null;
        $this->nationalitySearch     = '';
        $this->bookCountRange        = null;
        $this->onlyFollowed          = false;
        $this->collectionSearch      = '';
        $this->showAllCollections    = false;
    }

    private function buildDraftState(): ContributorFilterState
    {
        return new ContributorFilterState(
            role: $this->appliedRole,
            letter: $this->appliedLetter,
            collectionIds: array_values(array_filter(array_map('intval', $this->selectedCollectionIds), fn($id) => $id > 0)),
            periodMin: $this->periodMin,
            periodMax: $this->periodMax,
            nationality: $this->nationalitySearch !== '' ? $this->nationalitySearch : null,
            bookCountRange: $this->bookCountRange,
            onlyFollowed: $this->onlyFollowed,
            sort: $this->appliedSort,
            page: 1,
        );
    }
}
