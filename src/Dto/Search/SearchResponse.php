<?php

declare(strict_types=1);

namespace App\Dto\Search;

readonly class SearchResponse
{
    public function __construct(
        /** @var SearchResultItem[] */
        public array $results,
        /** @var SearchResultItem[] */
        public array $popular,
    ) {}
}
