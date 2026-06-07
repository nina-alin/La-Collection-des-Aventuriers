<?php

declare(strict_types=1);

namespace App\Dto;

readonly class MarqueeItemDto
{
    public function __construct(
        public string $name,
        public string $type,
        public string $url,
        public string $subtitle,
        public string $initials,
        public string $colorClass,
    ) {}
}
