<?php

namespace App\Dto;

final readonly class DashboardData
{
    public function __construct(
        public string $greeting,
        public string $formattedDate,
        public string $headerSubtitle,
        public int $collectionCount,
        public int $collectionDelta,
        public int $toReadCount,
        public int $toBuyCount,
        public int $suggestionsTotal,
        public int $suggestionsPending,
        public int $suggestionsValidatedRecently,
        public string $suggestionsValidatedLabel,
        public int $catalogueBookCount,
        public int $catalogueAuthorCount,
        public int $libraryBookCount,
        public int $libraryToReadCount,
        public int $wishlistCount,
        public int $globalPendingSuggestions,
        public array $recentBooks,
        public array $averageRatings,
        public array $activityEvents,
        public bool $isModerator,
        public array $errors = [],
    ) {}
}
