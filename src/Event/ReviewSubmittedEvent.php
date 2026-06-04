<?php

namespace App\Event;

use App\Entity\Book;
use App\Entity\User;

final readonly class ReviewSubmittedEvent
{
    public function __construct(
        public User $actor,
        public Book $book,
    ) {}
}
