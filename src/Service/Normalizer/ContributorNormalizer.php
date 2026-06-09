<?php

declare(strict_types=1);

namespace App\Service\Normalizer;

use App\Entity\Contributor;
use App\Entity\Enum\SuggestionEntityType;

class ContributorNormalizer implements EntityNormalizerInterface
{
    public function normalize(object $entity): array
    {
        assert($entity instanceof Contributor);

        return [
            'firstName' => $entity->getFirstName(),
            'lastName' => $entity->getLastName(),
            'pseudo' => $entity->getPseudo(),
            'nationality' => $entity->getNationality(),
            'biography' => $entity->getBiography(),
            'birthDate' => $entity->getBirthDate()?->format('Y-m-d'),
            'deathDate' => $entity->getDeathDate()?->format('Y-m-d'),
        ];
    }

    public function getFieldLabels(): array
    {
        return [
            'firstName' => 'Prénom',
            'lastName' => 'Nom',
            'pseudo' => 'Pseudonyme',
            'nationality' => 'Nationalité',
            'biography' => 'Biographie',
            'birthDate' => 'Date de naissance',
            'deathDate' => 'Date de décès',
        ];
    }

    public function getFieldTypes(): array
    {
        return [
            'firstName' => 'scalar',
            'lastName' => 'scalar',
            'pseudo' => 'scalar',
            'nationality' => 'scalar',
            'biography' => 'text',
            'birthDate' => 'scalar',
            'deathDate' => 'scalar',
        ];
    }

    public function getSupportedType(): SuggestionEntityType
    {
        return SuggestionEntityType::AUTHOR;
    }
}
