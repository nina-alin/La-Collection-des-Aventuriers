<?php

declare(strict_types=1);

namespace App\Dto;

final class ProfileMenuDto
{
    public function __construct(
        public readonly string $pseudo,
        public readonly ?string $displayName,
        public readonly ?string $avatarUrl,
        public readonly string $highestRole,
        public readonly ?string $rankName,
        public readonly int $validatedCount,
        public readonly int $pendingModerationCount,
    ) {}
}
