<?php

declare(strict_types=1);

namespace App\Service\Normalizer;

use App\Entity\Enum\SuggestionEntityType;

interface EntityNormalizerInterface
{
    /** @return array<string, mixed> */
    public function normalize(object $entity): array;

    /** @return array<string, string> field key => French label */
    public function getFieldLabels(): array;

    /** @return array<string, string> field key => 'scalar'|'text'|'tags' */
    public function getFieldTypes(): array;

    public function getSupportedType(): SuggestionEntityType;
}
