<?php

declare(strict_types=1);

namespace App\DataFixtures\Factory;

use App\Entity\Contributor;

class ContributorFactory
{
    public static function new(array $overrides = []): Contributor
    {
        $contributor = new Contributor();
        $contributor->setFirstName($overrides['firstName'] ?? 'Prénom');
        $contributor->setLastName($overrides['lastName'] ?? 'Contributeur');

        if (array_key_exists('pseudo', $overrides)) {
            $contributor->setPseudo($overrides['pseudo']);
        }
        if (array_key_exists('biography', $overrides)) {
            $contributor->setBiography($overrides['biography']);
        }
        if (array_key_exists('nationality', $overrides)) {
            $contributor->setNationality($overrides['nationality']);
        }
        if (array_key_exists('birthDate', $overrides)) {
            $contributor->setBirthDate($overrides['birthDate']);
        }
        if (array_key_exists('deathDate', $overrides)) {
            $contributor->setDeathDate($overrides['deathDate']);
        }
        if (array_key_exists('portraitImage', $overrides)) {
            $contributor->setPortraitImage($overrides['portraitImage']);
        }

        return $contributor;
    }

    public static function withoutPortrait(array $overrides = []): Contributor
    {
        return self::new(array_merge($overrides, ['portraitImage' => null]));
    }
}
