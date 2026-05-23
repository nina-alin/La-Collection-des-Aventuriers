<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsTwigComponent]
class Toast
{
    public string $message;
    public string $title = '';
    public string $type = 'info';
    public int $autoDismissMs = 5000;

    private const VALID_TYPES = ['success', 'error', 'warning', 'info'];

    #[PostMount]
    public function postMount(): void
    {
        if (!in_array($this->type, self::VALID_TYPES, true)) {
            $this->type = 'info';
        }
    }

    public function getCssClass(): string
    {
        return 'toast-' . $this->type;
    }
}
