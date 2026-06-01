<?php

declare(strict_types=1);

namespace App\ValueObject;

use App\Entity\Contributor;
use App\Entity\Enum\ContributionRole;

readonly class ContributorPill
{
    public function __construct(
        public Contributor $contributor,
        public ContributionRole $role,
        public int $count,
        public string $initials,
    ) {}
}
