<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum StatutCollection: string
{
    case EN_COURS = 'en-cours';
    case TERMINEE = 'terminee';
    case REEDITEE = 'reeditee';
}
