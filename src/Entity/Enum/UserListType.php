<?php

namespace App\Entity\Enum;

enum UserListType: string
{
    case Collection = 'collection';
    case ToRead     = 'to_read';
    case ToBuy      = 'to_buy';
    case Favorites  = 'favorites';
}
