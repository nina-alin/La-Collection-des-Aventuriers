<?php

namespace App\Event;

use App\Entity\ContributorLevel;
use App\Entity\User;

final readonly class RankUpEvent
{
    public function __construct(
        public User $user,
        public ContributorLevel $newLevel,
    ) {}
}
