<?php

namespace App\Entity\Enum;

enum NotificationType: string
{
    case CONTRIBUTION_VALIDATED = 'contribution_validated';
    case CONTRIBUTION_REFUSED   = 'contribution_refused';
    case BOOK_ACTIVITY          = 'book_activity';
    case MODERATION_PENDING     = 'moderation_pending';
    case RANK_UP                = 'rank_up';
    case FOLLOW_NOVELTY        = 'follow_novelty';
}
