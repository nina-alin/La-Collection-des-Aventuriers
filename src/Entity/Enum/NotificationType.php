<?php

namespace App\Entity\Enum;

enum NotificationType: string
{
    case CONTRIBUTION_VALIDATED = 'contribution_validated';
    case BOOK_ACTIVITY          = 'book_activity';
    case MODERATION_PENDING     = 'moderation_pending';
    case RANK_UP                = 'rank_up';
}
