<?php

namespace App\Event;

use App\Entity\User;
use App\Entity\WorkEntry;

final readonly class ContributionValidatedEvent
{
    public function __construct(
        public WorkEntry $workEntry,
        public User $recipient,
    ) {}
}
