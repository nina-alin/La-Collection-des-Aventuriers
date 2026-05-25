<?php

namespace App\Entity\Enum;

enum BookImageTab: string
{
    case TOME    = 'Tome';
    case DOS     = 'Dos';
    case TRANCHE = 'Tranche';
    case PAGES   = 'Pages';
    case CARTE   = 'Carte';
}
