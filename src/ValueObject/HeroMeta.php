<?php

declare(strict_types=1);

namespace App\ValueObject;

readonly class HeroMeta
{
    public function __construct(
        public ?int $yearMin,
        public ?int $yearMax,
        public ?float $averageRating,
    ) {}
}
