<?php

namespace App\Entity\Enum;

enum ActivityEventType: string
{
    case SOCIAL       = 'social';
    case CONTRIBUTION = 'contribution';
    case MODERATION   = 'moderation';
    case PERSONAL     = 'personal';
}
