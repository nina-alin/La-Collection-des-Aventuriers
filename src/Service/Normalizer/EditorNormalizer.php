<?php

declare(strict_types=1);

namespace App\Service\Normalizer;

use App\Entity\Editor;
use App\Entity\Enum\SuggestionEntityType;

class EditorNormalizer implements EntityNormalizerInterface
{
    public function normalize(object $entity): array
    {
        assert($entity instanceof Editor);

        return [
            'name' => $entity->getName(),
        ];
    }

    public function getFieldLabels(): array
    {
        return [
            'name' => 'Nom',
        ];
    }

    public function getFieldTypes(): array
    {
        return [
            'name' => 'scalar',
        ];
    }

    public function getSupportedType(): SuggestionEntityType
    {
        return SuggestionEntityType::EDITOR;
    }
}
