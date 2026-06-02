<?php

namespace App\Event;

use App\Entity\Book;
use App\Entity\Collection;

final readonly class BookAddedToCollectionEvent
{
    public function __construct(
        public Book $book,
        public Collection $collection,
        public bool $isBatch = false,
        public int $batchCount = 1,
    ) {}
}
