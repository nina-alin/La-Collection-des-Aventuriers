<?php

namespace App\Service;

use App\Entity\ContributorLevel;
use App\Entity\Enum\SuggestionStatus;
use App\Entity\User;
use App\Repository\ContributorLevelRepository;
use App\Repository\SuggestionRepository;

class ContributorLevelService
{
    public function __construct(
        private readonly ContributorLevelRepository $levelRepository,
        private readonly SuggestionRepository $suggestionRepository,
    ) {
    }

    public function computeRank(User $user): ?ContributorLevel
    {
        $validatedCount = $this->countByStatus($user, SuggestionStatus::VALIDATED);
        return $this->levelRepository->findRankForCount($validatedCount);
    }

    public function getDeltaToNextRank(User $user): ?int
    {
        $validatedCount  = $this->countByStatus($user, SuggestionStatus::VALIDATED);
        $currentLevel    = $this->levelRepository->findRankForCount($validatedCount);
        $nextLevel       = $this->findNextLevel($currentLevel);

        if ($nextLevel === null) {
            return null;
        }

        return $nextLevel->getThreshold() - $validatedCount;
    }

    public function getAcceptanceRate(User $user): ?float
    {
        $validated = $this->countByStatus($user, SuggestionStatus::VALIDATED);
        $refused   = $this->countByStatus($user, SuggestionStatus::REFUSED);
        $settled   = $validated + $refused;

        if ($settled === 0) {
            return null;
        }

        return $validated / $settled;
    }

    public function getMetrics(User $user): array
    {
        $validatedCount = $this->countByStatus($user, SuggestionStatus::VALIDATED);
        $pendingCount   = $this->suggestionRepository->findPendingCountByUser($user);
        $currentLevel   = $this->levelRepository->findRankForCount($validatedCount);
        $nextLevel      = $this->findNextLevel($currentLevel);
        $deltaToNext    = $nextLevel !== null ? $nextLevel->getThreshold() - $validatedCount : null;
        $acceptanceRate = $this->getAcceptanceRate($user);

        return [
            'validatedCount' => $validatedCount,
            'pendingCount'   => $pendingCount,
            'acceptanceRate' => $acceptanceRate,
            'currentLevel'   => $currentLevel,
            'deltaToNext'    => $deltaToNext,
        ];
    }

    private function countByStatus(User $user, SuggestionStatus $status): int
    {
        return $this->suggestionRepository->countByStatus($user, $status);
    }

    private function findNextLevel(?ContributorLevel $current): ?ContributorLevel
    {
        return $this->levelRepository->findNextLevel($current);
    }
}
