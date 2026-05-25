<?php

namespace App\Entity\Enum;

enum BookStatus: string
{
    case PENDING   = 'PENDING';
    case PUBLISHED = 'PUBLISHED';
    case REJECTED  = 'REJECTED';
}
