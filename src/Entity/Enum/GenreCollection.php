<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum GenreCollection: string
{
    case MEDIEVAL_FANTASTIQUE = 'medieval-fantastique';
    case SCIENCE_FICTION      = 'science-fiction';
    case HORREUR              = 'horreur';
    case ESPIONNAGE           = 'espionnage';
    case AVENTURE             = 'aventure';
    case CONTEMPORAIN         = 'contemporain';
}
