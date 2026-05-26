<?php

namespace App\Twig\Components\Contributor;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsTwigComponent]
class Card
{
    public ?string $name = null;
    public ?string $avatarUrl = null;
    public ?int $bookCount = null;
    public bool $loading = false;

    #[PostMount]
    public function postMount(): void
    {
        $this->name = $this->name ?? 'Contributeur inconnu';
    }
}
