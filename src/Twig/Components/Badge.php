<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsTwigComponent]
class Badge
{
    public string $label;
    public string $variant = 'primary';

    private const VALID_VARIANTS = ['primary', 'pending', 'validated', 'rejected', 'archived', 'user', 'mod', 'admin'];

    #[PostMount]
    public function postMount(): void
    {
        if (!in_array($this->variant, self::VALID_VARIANTS, true)) {
            $this->variant = 'primary';
        }
    }
}
