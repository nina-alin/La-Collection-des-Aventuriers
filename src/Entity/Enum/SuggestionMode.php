<?php

namespace App\Entity\Enum;

enum SuggestionMode: string
{
    case NEW_ENTRY  = 'NEW_ENTRY';
    case CORRECTION = 'CORRECTION';
}
