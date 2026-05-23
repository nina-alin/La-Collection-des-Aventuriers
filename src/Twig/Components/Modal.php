<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsTwigComponent]
class Modal
{
    public string $id;
    public string $title;
    public string $variant = 'default';
    public string $size = 'md';

    private const VALID_VARIANTS = ['default', 'danger'];
    private const VALID_SIZES    = ['sm', 'md', 'lg', 'xl'];

    #[PostMount]
    public function postMount(): void
    {
        if (!in_array($this->variant, self::VALID_VARIANTS, true)) {
            $this->variant = 'default';
        }

        if (!in_array($this->size, self::VALID_SIZES, true)) {
            $this->size = 'md';
        }
    }
}
