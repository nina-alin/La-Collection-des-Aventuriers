<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\HttpFoundation\Request;

readonly class ContributorFilterState
{
    public function __construct(
        public string  $role             = 'tous',
        public ?string $letter           = null,
        public array   $collectionIds    = [],
        public ?int    $periodMin        = null,
        public ?int    $periodMax        = null,
        public ?string $nationality      = null,
        public ?string $bookCountRange   = null,
        public bool    $onlyFollowed     = false,
        public string  $sort             = 'az',
        public int     $page             = 1,
        public ?string $currentUserId    = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validRoles = ['tous', 'auteur', 'traducteur', 'illustrateur'];
        $role = $request->query->get('role', 'tous');
        if (!in_array($role, $validRoles, true)) {
            $role = 'tous';
        }

        $letter = $request->query->get('letter');
        if ($letter !== null) {
            $letter = strtoupper(trim($letter));
            if (!preg_match('/^[A-Z]$/', $letter)) {
                $letter = null;
            }
        }

        $collectionIds = array_values(array_filter(
            array_map('intval', (array) $request->query->all('collection')),
            fn(int $id) => $id > 0
        ));

        $periodMin = $request->query->get('periodMin');
        $periodMin = ($periodMin !== null && ctype_digit((string) $periodMin)) ? (int) $periodMin : null;

        $periodMax = $request->query->get('periodMax');
        $periodMax = ($periodMax !== null && ctype_digit((string) $periodMax)) ? (int) $periodMax : null;

        if ($periodMin !== null && $periodMax !== null && $periodMin > $periodMax) {
            [$periodMin, $periodMax] = [$periodMax, $periodMin];
        }

        $nationality = $request->query->get('nationality');
        $nationality = ($nationality !== null && trim($nationality) !== '') ? trim($nationality) : null;

        $validRanges = ['1-5', '6-15', '16-30', '30+'];
        $bookCountRange = $request->query->get('bookCountRange');
        if (!in_array($bookCountRange, $validRanges, true)) {
            $bookCountRange = null;
        }

        $onlyFollowed = $request->query->get('onlyFollowed') === '1';

        $validSorts = ['az', 'ouvrages', 'note'];
        $sort = $request->query->get('sort', 'az');
        if (!in_array($sort, $validSorts, true)) {
            $sort = 'az';
        }

        $page = (int) $request->query->get('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        return new self(
            role: $role,
            letter: $letter,
            collectionIds: $collectionIds,
            periodMin: $periodMin,
            periodMax: $periodMax,
            nationality: $nationality,
            bookCountRange: $bookCountRange,
            onlyFollowed: $onlyFollowed,
            sort: $sort,
            page: $page,
        );
    }

    public function toUrlParams(): array
    {
        $params = [];

        if ($this->role !== 'tous') {
            $params['role'] = $this->role;
        }

        if ($this->letter !== null) {
            $params['letter'] = $this->letter;
        }

        if (!empty($this->collectionIds)) {
            $params['collection'] = $this->collectionIds;
        }

        if ($this->periodMin !== null) {
            $params['periodMin'] = $this->periodMin;
        }

        if ($this->periodMax !== null) {
            $params['periodMax'] = $this->periodMax;
        }

        if ($this->nationality !== null) {
            $params['nationality'] = $this->nationality;
        }

        if ($this->bookCountRange !== null) {
            $params['bookCountRange'] = $this->bookCountRange;
        }

        if ($this->onlyFollowed) {
            $params['onlyFollowed'] = '1';
        }

        if ($this->sort !== 'az') {
            $params['sort'] = $this->sort;
        }

        if ($this->page > 1) {
            $params['page'] = $this->page;
        }

        return $params;
    }
}
