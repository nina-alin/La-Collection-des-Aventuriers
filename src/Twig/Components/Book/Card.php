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
    public ?string $slug = null;
    public ?string $editionInfo = null;
    public ?int $publicationYear = null;
    public ?string $collectionRef = null;
    public bool $loading = false;
    public bool $isFavorite = false;
    public bool $isOwned = false;
    public bool $isWishlist = false;

    #[PostMount]
    public function postMount(): void
    {
        $this->title  = $this->title  ?? 'Sans titre';
        $this->author = $this->author ?? 'Auteur inconnu';
    }
}
