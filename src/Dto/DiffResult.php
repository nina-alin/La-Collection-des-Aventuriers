<?php

declare(strict_types=1);

namespace App\Dto;

class DiffResult
{
    /** @param DiffField[] $fields */
    public function __construct(
        public readonly array $fields,
        public readonly int $addedCount,
        public readonly int $replacedCount,
        public readonly int $removedCount,
    ) {}

    public function hasChanges(): bool
    {
        return $this->addedCount + $this->replacedCount + $this->removedCount > 0;
    }
}
