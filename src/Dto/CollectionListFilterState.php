<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\HttpFoundation\Request;

readonly class CollectionListFilterState
{
    public function __construct(
        public bool    $followed = false,
        public ?string $genre    = null,
        public ?string $statut   = null,
        public int     $page     = 1,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $followed = $request->query->get('followed') === 'true';

        $genre = $request->query->get('genre');
        $genre = ($genre !== null && trim($genre) !== '') ? trim($genre) : null;

        $statut = $request->query->get('statut');
        $statut = ($statut !== null && trim($statut) !== '') ? trim($statut) : null;

        $page = (int) $request->query->get('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        return new self(
            followed: $followed,
            genre: $genre,
            statut: $statut,
            page: $page,
        );
    }

    public function toUrlParams(): array
    {
        $params = [];

        if ($this->followed) {
            $params['followed'] = 'true';
        }

        if ($this->genre !== null) {
            $params['genre'] = $this->genre;
        }

        if ($this->statut !== null) {
            $params['statut'] = $this->statut;
        }

        if ($this->page > 1) {
            $params['page'] = $this->page;
        }

        return $params;
    }
}
