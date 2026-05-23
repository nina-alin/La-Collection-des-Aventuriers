<?php

namespace App\Tests\Unit\Service;

use App\Service\BruteForceProtectionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class BruteForceProtectionServiceTest extends TestCase
{
    private ArrayAdapter $cache;
    private BruteForceProtectionService $service;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        $this->service = new BruteForceProtectionService($this->cache);
    }

    public function testRecordFailureIncrementsCounter(): void
    {
        $ip = '127.0.0.1';
        $this->service->recordFailure($ip);
        $this->service->recordFailure($ip);

        $this->assertFalse($this->service->isBlocked($ip));
    }

    public function testIsBlockedFalseBelowThreshold(): void
    {
        $ip = '127.0.0.1';
        for ($i = 0; $i < 9; $i++) {
            $this->service->recordFailure($ip);
        }

        $this->assertFalse($this->service->isBlocked($ip));
    }

    public function testIsBlockedTrueAtExactlyTenFailures(): void
    {
        $ip = '127.0.0.1';
        for ($i = 0; $i < 10; $i++) {
            $this->service->recordFailure($ip);
        }

        $this->assertTrue($this->service->isBlocked($ip));
    }

    public function testResetCounterDeletesBothKeysOnSuccess(): void
    {
        $ip = '127.0.0.1';
        for ($i = 0; $i < 10; $i++) {
            $this->service->recordFailure($ip);
        }

        $this->assertTrue($this->service->isBlocked($ip));

        $this->service->resetCounter($ip);

        $this->assertFalse($this->service->isBlocked($ip));
    }

    public function testRecordFailureDuringBlockDoesNotExtendBlockTtl(): void
    {
        $ip = '192.168.1.1';
        for ($i = 0; $i < 10; $i++) {
            $this->service->recordFailure($ip);
        }

        $this->assertTrue($this->service->isBlocked($ip));

        $this->service->recordFailure($ip);

        $this->assertTrue($this->service->isBlocked($ip));
    }

    public function testGetRemainingBlockTimeReturnsPositiveWhenBlocked(): void
    {
        $ip = '10.0.0.1';
        for ($i = 0; $i < 10; $i++) {
            $this->service->recordFailure($ip);
        }

        $remaining = $this->service->getRemainingBlockTime($ip);
        $this->assertGreaterThan(0, $remaining);
        $this->assertLessThanOrEqual(900, $remaining);
    }

    public function testGetRemainingBlockTimeZeroWhenNotBlocked(): void
    {
        $ip = '10.0.0.2';
        $remaining = $this->service->getRemainingBlockTime($ip);
        $this->assertSame(0, $remaining);
    }
}
