<?php

namespace App\Event;

use App\Entity\User;

final readonly class ContributionValidatedEvent
{
    public function __construct(
        public string $title,
        public User $recipient,
    ) {}
}
