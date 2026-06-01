<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ProfileMenuDto;
use App\Entity\Enum\SuggestionStatus;
use App\Entity\User;
use App\Repository\CorrectionProposalRepository;
use App\Repository\SuggestionRepository;
use App\Repository\WorkEntryRepository;

class ProfileMenuService
{
    public function __construct(
        private readonly ContributorLevelService $contributorLevelService,
        private readonly SuggestionRepository $suggestionRepository,
        private readonly WorkEntryRepository $workEntryRepository,
        private readonly CorrectionProposalRepository $correctionProposalRepository,
    ) {}

    public function getMenuData(User $user): ProfileMenuDto
    {
        $rank = $this->contributorLevelService->computeRank($user);
        $validatedCount = $this->suggestionRepository->countByStatus($user, SuggestionStatus::VALIDATED);
        $pendingModerationCount = $this->workEntryRepository->countPending()
            + $this->correctionProposalRepository->countPending();

        $roles = $user->getRoles();
        $highestRole = 'ROLE_USER';
        if (in_array('ROLE_ADMIN', $roles, true)) {
            $highestRole = 'ROLE_ADMIN';
        } elseif (in_array('ROLE_MODERATOR', $roles, true)) {
            $highestRole = 'ROLE_MODERATOR';
        }

        return new ProfileMenuDto(
            pseudo: $user->getPseudo(),
            displayName: $user->getDisplayName(),
            avatarUrl: $user->getAvatarUrl(),
            highestRole: $highestRole,
            rankName: $rank?->getName(),
            validatedCount: $validatedCount,
            pendingModerationCount: $pendingModerationCount,
        );
    }
}
