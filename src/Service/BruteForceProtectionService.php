<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;

class BruteForceProtectionService
{
    private const THRESHOLD = 10;
    private const BLOCK_TTL = 900;

    public function __construct(
        private readonly CacheItemPoolInterface $dbalCache,
    ) {
    }

    public function isBlocked(string $ip): bool
    {
        $item = $this->dbalCache->getItem($this->blockKey($ip));

        return $item->isHit() && $item->get() === true;
    }

    public function recordFailure(string $ip): void
    {
        if ($this->isBlocked($ip)) {
            return;
        }

        $failureKey = $this->failureKey($ip);
        $item = $this->dbalCache->getItem($failureKey);

        $count = $item->isHit() ? (int) $item->get() : 0;
        $count++;

        $item->set($count);
        $item->expiresAfter(self::BLOCK_TTL);
        $this->dbalCache->save($item);

        if ($count >= self::THRESHOLD) {
            $blockItem = $this->dbalCache->getItem($this->blockKey($ip));
            $blockItem->set(true);
            $blockItem->expiresAfter(self::BLOCK_TTL);
            $this->dbalCache->save($blockItem);
        }
    }

    public function resetCounter(string $ip): void
    {
        $this->dbalCache->deleteItem($this->failureKey($ip));
        $this->dbalCache->deleteItem($this->blockKey($ip));
    }

    public function getRemainingBlockTime(string $ip): int
    {
        $item = $this->dbalCache->getItem($this->blockKey($ip));

        if (!$item->isHit() || $item->get() !== true) {
            return 0;
        }

        $metadata = $item->getMetadata();
        $expiry = $metadata[\Symfony\Component\Cache\CacheItem::METADATA_EXPIRY] ?? null;

        if ($expiry === null) {
            return self::BLOCK_TTL;
        }

        return max(0, (int) ceil($expiry - microtime(true)));
    }

    private function failureKey(string $ip): string
    {
        return 'login_failures_'.hash('sha256', $ip);
    }

    private function blockKey(string $ip): string
    {
        return 'login_blocked_'.hash('sha256', $ip);
    }
}
