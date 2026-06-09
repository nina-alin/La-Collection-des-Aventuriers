<?php

declare(strict_types=1);

namespace App\Service\Normalizer;

use App\Entity\Book;
use App\Entity\Enum\SuggestionEntityType;

class BookNormalizer implements EntityNormalizerInterface
{
    public function normalize(object $entity): array
    {
        assert($entity instanceof Book);

        return [
            'title' => $entity->getTitle(),
            'originalTitle' => $entity->getOriginalTitle(),
            'isbn' => $entity->getIsbn(),
            'pages' => $entity->getPages(),
            'paragraphs' => $entity->getParagraphs(),
            'frenchPublicationYear' => $entity->getFrenchPublicationYear(),
            'originalPublicationYear' => $entity->getOriginalPublicationYear(),
            'editionInfo' => $entity->getEditionInfo(),
            'saga' => $entity->getSaga(),
        ];
    }

    public function getFieldLabels(): array
    {
        return [
            'title' => 'Titre',
            'originalTitle' => 'Titre original',
            'isbn' => 'ISBN',
            'pages' => 'Nombre de pages',
            'paragraphs' => 'Nombre de paragraphes',
            'frenchPublicationYear' => 'Année de publication (FR)',
            'originalPublicationYear' => 'Année de publication (original)',
            'editionInfo' => 'Informations d\'édition',
            'saga' => 'Saga',
        ];
    }

    public function getFieldTypes(): array
    {
        return [
            'title' => 'text',
            'originalTitle' => 'scalar',
            'isbn' => 'scalar',
            'pages' => 'scalar',
            'paragraphs' => 'scalar',
            'frenchPublicationYear' => 'scalar',
            'originalPublicationYear' => 'scalar',
            'editionInfo' => 'scalar',
            'saga' => 'scalar',
        ];
    }

    public function getSupportedType(): SuggestionEntityType
    {
        return SuggestionEntityType::BOOK;
    }
}
