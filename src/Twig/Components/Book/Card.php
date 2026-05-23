<?php

namespace App\Twig\Components\Book;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsTwigComponent]
class Card
{
    public ?string $title = null;
    public ?string $coverUrl = null;
    public ?string $author = null;
    public ?float $rating = null;
    public ?int $bookId = null;
    public bool $loading = false;

    #[PostMount]
    public function postMount(): void
    {
        $this->title  = $this->title  ?? 'Sans titre';
        $this->author = $this->author ?? 'Auteur inconnu';
    }
}
