<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ContributorFilterState;
use App\Entity\User;
use App\Repository\ContributorRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

class ContributeurService
{
    public function __construct(
        private readonly ContributorRepository $contributorRepository,
    ) {}

    public function getPaginatedResults(ContributorFilterState $state, ?User $user = null): Paginator
    {
        return $this->contributorRepository->findPaginatedFiltered($state, $user);
    }

    /** @return string[] */
    public function getAvailableLetters(ContributorFilterState $state, ?User $user = null): array
    {
        return $this->contributorRepository->findAvailableLetters($state, $user);
    }

    public function getCardDataBatch(array $contributorIds): array
    {
        if (empty($contributorIds)) {
            return [];
        }

        $cardData       = $this->contributorRepository->findCardDataBatch($contributorIds);
        $topCollections = $this->contributorRepository->findTopCollectionsBatch($contributorIds);

        foreach ($contributorIds as $id) {
            $idStr = (string) $id;
            if (!isset($cardData[$idStr])) {
                $cardData[$idStr] = ['bookCount' => 0, 'avgScore' => null, 'roles' => []];
            }
            $cardData[$idStr]['topCollections'] = $topCollections[$idStr] ?? [];
        }

        return $cardData;
    }

    public function countFiltered(ContributorFilterState $state): int
    {
        return $this->contributorRepository->countFiltered($state);
    }

    /** @return array{auteur: int, traducteur: int, illustrateur: int, tous: int} */
    public function getRoleCounts(): array
    {
        return $this->contributorRepository->findRoleCounts();
    }

    public function getAutocompleteResults(string $q): array
    {
        return $this->contributorRepository->findForAutocomplete($q);
    }
}
