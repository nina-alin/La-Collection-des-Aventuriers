<?php

namespace App\Dto;

use Symfony\Component\HttpFoundation\Request;

readonly class ActiveFilterState
{
    public function __construct(
        public string  $sort             = 'note-desc',
        public array   $editors          = [],
        public array   $contributors     = [],
        public ?int    $paragraphMin     = null,
        public ?int    $paragraphMax     = null,
        public ?string $collectionStatus = null,
        public bool    $onlyFavorites    = false,
        public bool    $hideModeration   = false,
        public ?string $searchQuery      = null,
        public int     $page             = 1,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $sort = $request->query->get('sort', 'note-desc');
        $validSorts = ['note-desc', 'alpha', 'parution-fr', 'parution-orig', 'recent'];
        if (!in_array($sort, $validSorts, true)) {
            $sort = 'note-desc';
        }

        $editors = array_filter(
            array_map('intval', (array) $request->query->all('editors')),
            fn(int $id) => $id > 0
        );

        $contributors = array_values(array_filter(
            array_map('strval', (array) $request->query->all('contributors')),
            fn(string $id) => $id !== ''
        ));

        $paragraphMin = $request->query->get('paragraphMin');
        $paragraphMin = ($paragraphMin !== null && ctype_digit((string) $paragraphMin)) ? (int) $paragraphMin : null;

        $paragraphMax = $request->query->get('paragraphMax');
        $paragraphMax = ($paragraphMax !== null && ctype_digit((string) $paragraphMax)) ? (int) $paragraphMax : null;

        if ($paragraphMin !== null && $paragraphMax !== null && $paragraphMin > $paragraphMax) {
            $paragraphMax = $paragraphMin;
        }

        $validStatuses = ['dans-ma-collection', 'a-acheter', 'a-lire'];
        $collectionStatus = $request->query->get('collectionStatus');
        if (!in_array($collectionStatus, $validStatuses, true)) {
            $collectionStatus = null;
        }

        $onlyFavorites  = $request->query->get('onlyFavorites') === '1';
        $hideModeration = $request->query->get('hideModeration') === '1';

        $searchQuery = $request->query->get('q');
        $searchQuery = ($searchQuery !== null && trim($searchQuery) !== '') ? trim($searchQuery) : null;

        $page = (int) $request->query->get('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        return new self(
            sort: $sort,
            editors: array_values($editors),
            contributors: $contributors,
            paragraphMin: $paragraphMin,
            paragraphMax: $paragraphMax,
            collectionStatus: $collectionStatus,
            onlyFavorites: $onlyFavorites,
            hideModeration: $hideModeration,
            searchQuery: $searchQuery,
            page: $page,
        );
    }

    public function toUrlParams(): array
    {
        $params = [];

        if ($this->sort !== 'note-desc') {
            $params['sort'] = $this->sort;
        }

        if (!empty($this->editors)) {
            $params['editors'] = $this->editors;
        }

        if (!empty($this->contributors)) {
            $params['contributors'] = $this->contributors;
        }

        if ($this->paragraphMin !== null) {
            $params['paragraphMin'] = $this->paragraphMin;
        }

        if ($this->paragraphMax !== null) {
            $params['paragraphMax'] = $this->paragraphMax;
        }

        if ($this->collectionStatus !== null) {
            $params['collectionStatus'] = $this->collectionStatus;
        }

        if ($this->onlyFavorites) {
            $params['onlyFavorites'] = '1';
        }

        if ($this->hideModeration) {
            $params['hideModeration'] = '1';
        }

        if ($this->searchQuery !== null) {
            $params['q'] = $this->searchQuery;
        }

        if ($this->page > 1) {
            $params['page'] = $this->page;
        }

        return $params;
    }

    public function countActiveFilters(): int
    {
        $count = 0;

        $count += count($this->editors);
        $count += count($this->contributors);

        if ($this->paragraphMin !== null || $this->paragraphMax !== null) {
            $count++;
        }

        if ($this->collectionStatus !== null) {
            $count++;
        }

        if ($this->onlyFavorites) {
            $count++;
        }

        if ($this->hideModeration) {
            $count++;
        }

        if ($this->searchQuery !== null) {
            $count++;
        }

        return $count;
    }
}
