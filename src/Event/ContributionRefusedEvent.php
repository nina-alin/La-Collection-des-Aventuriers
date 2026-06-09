<?php

namespace App\Event;

use App\Entity\User;

final readonly class ContributionRefusedEvent
{
    public function __construct(
        public string $title,
        public User $recipient,
        public ?string $reason,
    ) {}
}
