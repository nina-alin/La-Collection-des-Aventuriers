<?php

namespace App\Event;

use App\Entity\Suggestion;

final readonly class ModerationPendingEvent
{
    public function __construct(
        public Suggestion $suggestion,
    ) {}
}
