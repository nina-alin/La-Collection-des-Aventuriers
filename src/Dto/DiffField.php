<?php

declare(strict_types=1);

namespace App\Dto;

enum DiffFieldStatus: string
{
    case ADDED = 'ADDED';
    case REMOVED = 'REMOVED';
    case REPLACED = 'REPLACED';
    case UNCHANGED = 'UNCHANGED';
}

class DiffField
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly DiffFieldStatus $status,
        public readonly mixed $currentValue,
        public readonly mixed $proposedValue,
        public readonly ?string $annotatedHtml = null,
        public readonly string $type = 'scalar',
    ) {}
}
