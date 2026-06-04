<?php

namespace App\Event;

use App\Entity\Suggestion;
use App\Entity\User;
use App\Entity\Enum\SuggestionStatus;

final readonly class SuggestionModeratedEvent
{
    public function __construct(
        public User $actor,
        public Suggestion $suggestion,
        public SuggestionStatus $newStatus,
    ) {}
}
