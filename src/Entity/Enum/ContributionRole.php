<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum ContributionRole: string
{
    case Author = 'Author';
    case Illustrator = 'Illustrator';
    case Traductor = 'Traductor';
}
