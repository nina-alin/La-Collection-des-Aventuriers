<?php

declare(strict_types=1);

namespace App\DataFixtures\Factory;

use App\Entity\User;

class UserFactory
{
    public static function new(array $overrides = []): User
    {
        $user = new User();
        $user->setEmail($overrides['email'] ?? 'user_' . substr(uniqid(), -6) . '@example.com');
        $user->setPseudo($overrides['pseudo'] ?? 'user' . substr(uniqid(), -6));
        $user->setRoles($overrides['roles'] ?? []);

        if (array_key_exists('password', $overrides)) {
            $user->setPassword($overrides['password']);
        }
        if (array_key_exists('displayName', $overrides)) {
            $user->setDisplayName($overrides['displayName']);
        }

        return $user;
    }
}
