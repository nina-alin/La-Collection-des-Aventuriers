<?php

namespace App\Twig\Extension;

use App\Entity\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class UserInitialsExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('user_initials', [$this, 'getUserInitials']),
        ];
    }

    public function getUserInitials(?User $user): ?string
    {
        if ($user === null || $user->getDisplayName() === null) {
            return null;
        }
        $parts = explode(' ', trim($user->getDisplayName()), 2);
        if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }
        return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    }
}
