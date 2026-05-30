<?php

namespace App\Entity\Enum;

enum SuggestionEntityType: string
{
    case BOOK        = 'BOOK';
    case AUTHOR      = 'AUTHOR';
    case ILLUSTRATOR = 'ILLUSTRATOR';
    case TRADUCTOR   = 'TRADUCTOR';
    case EDITOR      = 'EDITOR';
    case COLLECTION  = 'COLLECTION';
}
