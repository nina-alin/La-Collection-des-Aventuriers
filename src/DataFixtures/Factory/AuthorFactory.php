<?php

declare(strict_types=1);

namespace App\DataFixtures\Factory;

use App\Entity\Author;

class AuthorFactory
{
    public static function new(array $overrides = []): Author
    {
        $author = new Author();
        $author->setFirstName($overrides['firstName'] ?? 'Prénom');
        $author->setLastName($overrides['lastName'] ?? 'Auteur');

        return $author;
    }
}
