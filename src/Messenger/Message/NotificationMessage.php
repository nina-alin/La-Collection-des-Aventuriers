<?php

namespace App\Messenger\Message;

final readonly class NotificationMessage
{
    public function __construct(
        public string $userId,
        public string $type,
        public string $message,
        public string $sourceId,
        public ?string $targetUrl = null,
    ) {}
}
