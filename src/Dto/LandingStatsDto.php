<?php

declare(strict_types=1);

namespace App\Dto;

readonly class LandingStatsDto
{
    public function __construct(
        public int $totalBooks,
        public int $totalUsers,
        public int $newThisWeek,
    ) {}
}
