<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsTwigComponent]
class Rating
{
    public float $value;
    public int $max = 5;
    public string $size = 'md';

    private const VALID_SIZES = ['sm', 'md', 'lg'];

    #[PostMount]
    public function postMount(): void
    {
        $this->value = max(0.0, min($this->value, (float) $this->max));

        if (!in_array($this->size, self::VALID_SIZES, true)) {
            $this->size = 'md';
        }
    }
}
