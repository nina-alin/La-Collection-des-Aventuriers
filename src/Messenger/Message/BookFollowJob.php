<?php

declare(strict_types=1);

namespace App\Messenger\Message;

final readonly class BookFollowJob
{
    public function __construct(public readonly string $bookId) {}
}
