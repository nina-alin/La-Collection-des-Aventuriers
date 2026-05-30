<?php

namespace App\Entity\Enum;

enum SuggestionRefusalAction: string
{
    case VOIR_FICHE = 'VOIR_FICHE';
    case MASQUER    = 'MASQUER';
}
