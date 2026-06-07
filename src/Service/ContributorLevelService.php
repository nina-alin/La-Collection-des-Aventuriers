<?php

namespace App\Service;

use App\Entity\ContributorLevel;
use App\Entity\Enum\SuggestionStatus;
use App\Entity\User;
use App\Repository\ContributorLevelRepository;
use App\Repository\CorrectionProposalRepository;
use App\Repository\SuggestionRepository;

class ContributorLevelService
{
    public function __construct(
        private readonly ContributorLevelRepository $levelRepository,
        private readonly SuggestionRepository $suggestionRepository,
        private readonly CorrectionProposalRepository $correctionRepo,
    ) {
    }

    public function computeRank(User $user): ?ContributorLevel
    {
        $validatedCount = $this->countValidatedContributions($user);
        return $this->levelRepository->findRankForCount($validatedCount);
    }

    public function computeRankBatch(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $suggestionCounts = $this->suggestionRepository->countBatchValidated($users);
        $correctionCounts = $this->correctionRepo->countBatchPublished($users);
        $levels = $this->levelRepository->findAllSortedByThreshold();

        $result = [];
        foreach ($users as $user) {
            $userId = $user->getId()->toRfc4122();
            $count = ($suggestionCounts[$userId] ?? 0) + ($correctionCounts[$userId] ?? 0);
            $result[$userId] = $this->findRankForCountInMemory($levels, $count);
        }

        return $result;
    }

    public function getDeltaToNextRank(User $user): ?int
    {
        $validatedCount = $this->countValidatedContributions($user);
        $currentLevel   = $this->levelRepository->findRankForCount($validatedCount);
        $nextLevel      = $this->findNextLevel($currentLevel);

        if ($nextLevel === null) {
            return null;
        }

        return $nextLevel->getThreshold() - $validatedCount;
    }

    public function getAcceptanceRate(User $user): ?float
    {
        $validated = $this->suggestionRepository->countByStatus($user, SuggestionStatus::VALIDATED);
        $refused   = $this->suggestionRepository->countByStatus($user, SuggestionStatus::REFUSED);
        $settled   = $validated + $refused;

        if ($settled === 0) {
            return null;
        }

        return $validated / $settled;
    }

    public function getMetrics(User $user): array
    {
        $validatedCount = $this->countValidatedContributions($user);
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
            'nextLevel'      => $nextLevel,
            'deltaToNext'    => $deltaToNext,
        ];
    }

    private function countValidatedContributions(User $user): int
    {
        return $this->suggestionRepository->countByStatus($user, SuggestionStatus::VALIDATED)
            + $this->correctionRepo->countPublishedByUser($user);
    }

    private function findNextLevel(?ContributorLevel $current): ?ContributorLevel
    {
        return $this->levelRepository->findNextLevel($current);
    }

    private function findRankForCountInMemory(array $levels, int $count): ?ContributorLevel
    {
        $matched = null;
        foreach ($levels as $level) {
            if ($level->getThreshold() <= $count) {
                $matched = $level;
            }
        }
        return $matched;
    }
}
