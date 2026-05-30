<?php

namespace App\Tests\Unit\Service;

use App\Entity\ContributorLevel;
use App\Entity\Enum\SuggestionStatus;
use App\Entity\User;
use App\Repository\ContributorLevelRepository;
use App\Repository\SuggestionRepository;
use App\Service\ContributorLevelService;
use PHPUnit\Framework\TestCase;

class ContributorLevelServiceTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = $this->createMock(User::class);
    }

    private function makeService(
        int $validatedCount = 0,
        int $refusedCount   = 0,
        int $pendingCount   = 0,
        ?ContributorLevel $currentLevel = null,
        ?ContributorLevel $nextLevel    = null,
    ): ContributorLevelService {
        $suggestionRepo = $this->createMock(SuggestionRepository::class);
        $levelRepo      = $this->createMock(ContributorLevelRepository::class);

        $suggestionRepo->method('countByStatus')->willReturnCallback(
            fn (User $u, SuggestionStatus $status) => match ($status) {
                SuggestionStatus::VALIDATED => $validatedCount,
                SuggestionStatus::REFUSED   => $refusedCount,
                default                     => 0,
            }
        );
        $suggestionRepo->method('findPendingCountByUser')->willReturn($pendingCount);

        $levelRepo->method('findRankForCount')->willReturn($currentLevel);
        $levelRepo->method('findNextLevel')->willReturn($nextLevel);

        return new ContributorLevelService($levelRepo, $suggestionRepo);
    }

    public function testAcceptanceRateExcludesPending(): void
    {
        $service = $this->makeService(validatedCount: 8, refusedCount: 2, pendingCount: 5);
        $rate    = $service->getAcceptanceRate($this->user);

        $this->assertEqualsWithDelta(0.8, $rate, 0.001, '8/(8+2) = 0.8, pending ignored');
    }

    public function testAcceptanceRateIsNullWhenNoSettledSuggestions(): void
    {
        $service = $this->makeService(validatedCount: 0, refusedCount: 0, pendingCount: 3);
        $rate    = $service->getAcceptanceRate($this->user);

        $this->assertNull($rate);
    }

    public function testDeltaIsNullAtHighestRank(): void
    {
        $grandSage = new ContributorLevel();
        $grandSage->setRankNumber(6);
        $grandSage->setName('Grand Sage');
        $grandSage->setThreshold(100);

        $service = $this->makeService(
            validatedCount: 120,
            currentLevel:   $grandSage,
            nextLevel:      null,
        );

        $delta = $service->getDeltaToNextRank($this->user);
        $this->assertNull($delta);
    }

    public function testDeltaComputedCorrectly(): void
    {
        $chroniqueur = new ContributorLevel();
        $chroniqueur->setThreshold(15);
        $chroniqueur->setRankNumber(3);
        $chroniqueur->setName('Chroniqueur confirmé');

        $archiviste = new ContributorLevel();
        $archiviste->setThreshold(30);
        $archiviste->setRankNumber(4);
        $archiviste->setName('Archiviste');

        $service = $this->makeService(
            validatedCount: 20,
            currentLevel:   $chroniqueur,
            nextLevel:      $archiviste,
        );

        $delta = $service->getDeltaToNextRank($this->user);
        $this->assertSame(10, $delta, '30 - 20 = 10');
    }
}
