<?php

declare(strict_types=1);

namespace App\Dto\Search;

readonly class SearchResultItem
{
    public function __construct(
        public string $type,
        public string $slug,
        public string $title,
        public string $subtitle,
        public ?string $thumbnailUrl,
        public ?string $initials,
        public ?string $avatarColor,
    ) {}
}
