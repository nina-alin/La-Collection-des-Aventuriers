<?php

declare(strict_types=1);

namespace App\DataFixtures\Factory;

use App\Entity\Collection;
use App\Entity\Enum\GenreCollection;
use App\Entity\Enum\StatutCollection;

class CollectionFactory
{
    public static function new(array $overrides = []): Collection
    {
        $collection = new Collection();
        $collection->setNom($overrides['nom'] ?? 'Test Collection ' . substr(uniqid(), -6));
        $collection->setDescription($overrides['description'] ?? 'Test description de la collection.');
        $collection->setGenre($overrides['genre'] ?? GenreCollection::AVENTURE);
        $collection->setStatut($overrides['statut'] ?? StatutCollection::EN_COURS);
        $collection->setCreateurs($overrides['createurs'] ?? []);

        if (array_key_exists('nomOriginal', $overrides)) {
            $collection->setNomOriginal($overrides['nomOriginal']);
        }
        if (array_key_exists('imageLogo', $overrides)) {
            $collection->setImageLogo($overrides['imageLogo']);
        }
        if (array_key_exists('anneeCreation', $overrides)) {
            $collection->setAnneeCreation($overrides['anneeCreation']);
        }
        if (array_key_exists('editeurHistorique', $overrides)) {
            $collection->setEditeurHistorique($overrides['editeurHistorique']);
        }

        return $collection;
    }
}
