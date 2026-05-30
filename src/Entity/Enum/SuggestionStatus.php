<?php

namespace App\Entity\Enum;

enum SuggestionStatus: string
{
    case PENDING   = 'PENDING';
    case VALIDATED = 'VALIDATED';
    case REFUSED   = 'REFUSED';
}
