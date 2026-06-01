<?php

namespace App\Entity\Enum;

enum UserBookStatus: string
{
    case DANS_MA_COLLECTION     = 'dans-ma-collection';
    case A_ACHETER              = 'a-acheter';
    case A_LIRE                 = 'a-lire';
    case LU                     = 'lu';
    case PAS_DANS_MA_COLLECTION = 'pas-dans-ma-collection';
}
