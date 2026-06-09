<?php

declare(strict_types=1);

namespace App\Service\Normalizer;

use App\Entity\Collection;
use App\Entity\Enum\SuggestionEntityType;

class CollectionNormalizer implements EntityNormalizerInterface
{
    public function normalize(object $entity): array
    {
        assert($entity instanceof Collection);

        return [
            'nom' => $entity->getNom(),
            'slug' => $entity->getSlug(),
            'genre' => $entity->getGenre()->value,
            'statut' => $entity->getStatut()->value,
            'description' => $entity->getDescription(),
        ];
    }

    public function getFieldLabels(): array
    {
        return [
            'nom' => 'Nom',
            'slug' => 'Slug',
            'genre' => 'Genre',
            'statut' => 'Statut',
            'description' => 'Description',
        ];
    }

    public function getFieldTypes(): array
    {
        return [
            'nom' => 'scalar',
            'slug' => 'scalar',
            'genre' => 'scalar',
            'statut' => 'scalar',
            'description' => 'text',
        ];
    }

    public function getSupportedType(): SuggestionEntityType
    {
        return SuggestionEntityType::COLLECTION;
    }
}
