<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Enum\SuggestionStatus;
use App\Entity\User;
use App\Repository\ReviewRepository;
use App\Repository\SuggestionRepository;
use App\Repository\UserBookRepository;

class ProfileKpiService
{
    public function __construct(
        private readonly UserBookRepository $userBookRepository,
        private readonly ReviewRepository $reviewRepository,
        private readonly SuggestionRepository $suggestionRepository,
    ) {
    }

    public function getBookStats(User $user): array
    {
        $total = $this->userBookRepository->countOwnedByUser($user);
        $toRead = $this->userBookRepository->countToReadByUser($user);
        $toBuy = $this->userBookRepository->countToBuyByUser($user);

        $firstDayOfMonth = new \DateTimeImmutable('first day of this month midnight', new \DateTimeZone('UTC'));
        $firstDayOfLastMonth = $firstDayOfMonth->modify('-1 month');
        $addedThisMonth = $this->userBookRepository->countOwnedAddedSince($user, $firstDayOfMonth);
        $addedLastMonth = $this->userBookRepository->countOwnedAddedSince($user, $firstDayOfLastMonth) - $addedThisMonth;

        return [
            'total' => $total,
            'toRead' => $toRead,
            'toBuy' => $toBuy,
            'addedThisMonth' => $addedThisMonth,
            'addedLastMonth' => $addedLastMonth,
        ];
    }

    public function getRatingStats(User $user): array
    {
        $stats = $this->reviewRepository->getStatsByUser($user);

        return [
            'count' => $stats['count'] ?? 0,
            'average' => $stats['average'] ?? null,
        ];
    }

    public function getSuggestionStats(User $user): array
    {
        $validated = $this->suggestionRepository->countByStatus($user, SuggestionStatus::VALIDATED);
        $refused = $this->suggestionRepository->countByStatus($user, SuggestionStatus::REFUSED);
        $settled = $validated + $refused;
        $acceptanceRate = $settled > 0 ? round($validated / $settled * 100) : null;

        return [
            'validated' => $validated,
            'acceptanceRate' => $acceptanceRate,
        ];
    }

    public function getStreakStats(User $user): array
    {
        return [
            'loginStreak' => $user->getLoginStreak(),
            'lastLoginDate' => $user->getLastLoginDate(),
        ];
    }
}
