<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Collection;
use App\Repository\CollectionPublishingHistoryRepository;
use App\Repository\CollectionRepository;
use App\Repository\ContributionRepository;
use App\ValueObject\ContributorPill;
use App\ValueObject\HeroMeta;
use App\ValueObject\RecurringContributorsResult;

class CollectionService
{
    public function __construct(
        private CollectionRepository $collectionRepo,
        private ContributionRepository $contributionRepo,
        private CollectionPublishingHistoryRepository $historyRepo,
    ) {}

    public function getHeroMeta(Collection $collection): HeroMeta
    {
        $range = $this->collectionRepo->getPublicationYearRange($collection);
        $avgRating = $this->collectionRepo->computeAverageRating($collection);

        return new HeroMeta(
            yearMin: $range['min'],
            yearMax: $range['max'],
            averageRating: $avgRating,
        );
    }

    public function getRecurringContributors(Collection $collection): RecurringContributorsResult
    {
        $rows = $this->contributionRepo->findRecurringByCollection($collection);

        $seenContributorIds = [];
        $pills = [];

        foreach ($rows as $row) {
            $contributor = $row['contributor'];
            $pills[] = new ContributorPill(
                contributor: $contributor,
                role: $row['role'],
                count: $row['count'],
                initials: $this->computeInitials($contributor->getFirstName(), $contributor->getLastName()),
            );
            $id = $contributor->getId()->toRfc4122();
            $seenContributorIds[$id] = true;
        }

        return new RecurringContributorsResult(
            uniqueCount: count($seenContributorIds),
            pills: $pills,
        );
    }

    /** @return \App\Entity\CollectionPublishingHistory[] */
    public function getPublishingHistory(Collection $collection): array
    {
        return $this->historyRepo->findByCollection($collection);
    }

    private function computeInitials(string $firstName, string $lastName): string
    {
        if ($lastName !== '') {
            return mb_strtoupper(mb_substr($firstName, 0, 1) . mb_substr($lastName, 0, 1));
        }

        return mb_strtoupper(mb_substr($firstName, 0, 2));
    }
}
