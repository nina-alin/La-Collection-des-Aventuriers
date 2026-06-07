<?php

namespace App\Tests\Unit\Service;

use App\Entity\ContributorLevel;
use App\Entity\Enum\SuggestionStatus;
use App\Entity\User;
use App\Repository\ContributorLevelRepository;
use App\Repository\CorrectionProposalRepository;
use App\Repository\SuggestionRepository;
use App\Service\ContributorLevelService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ContributorLevelServiceTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = $this->createMock(User::class);
    }

    private function makeService(
        int $validatedCount  = 0,
        int $refusedCount    = 0,
        int $pendingCount    = 0,
        int $correctionCount = 0,
        ?ContributorLevel $currentLevel = null,
        ?ContributorLevel $nextLevel    = null,
    ): ContributorLevelService {
        $suggestionRepo = $this->createMock(SuggestionRepository::class);
        $levelRepo      = $this->createMock(ContributorLevelRepository::class);
        $correctionRepo = $this->createMock(CorrectionProposalRepository::class);

        $suggestionRepo->method('countByStatus')->willReturnCallback(
            fn (User $u, SuggestionStatus $status) => match ($status) {
                SuggestionStatus::VALIDATED => $validatedCount,
                SuggestionStatus::REFUSED   => $refusedCount,
                default                     => 0,
            }
        );
        $suggestionRepo->method('findPendingCountByUser')->willReturn($pendingCount);
        $correctionRepo->method('countPublishedByUser')->willReturn($correctionCount);

        $levelRepo->method('findRankForCount')->willReturn($currentLevel);
        $levelRepo->method('findNextLevel')->willReturn($nextLevel);

        return new ContributorLevelService($levelRepo, $suggestionRepo, $correctionRepo);
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

    public function testComputeRankUsesCombinedCount(): void
    {
        $service = $this->makeService(validatedCount: 3, correctionCount: 4);

        $levelRepo      = $this->createMock(ContributorLevelRepository::class);
        $suggestionRepo = $this->createMock(SuggestionRepository::class);
        $correctionRepo = $this->createMock(CorrectionProposalRepository::class);

        $apprenti = new ContributorLevel();
        $apprenti->setRankNumber(2);
        $apprenti->setName('Apprenti');
        $apprenti->setThreshold(5);

        $suggestionRepo->method('countByStatus')->willReturnCallback(
            fn (User $u, SuggestionStatus $status) => match ($status) {
                SuggestionStatus::VALIDATED => 3,
                default => 0,
            }
        );
        $correctionRepo->method('countPublishedByUser')->willReturn(4);
        $levelRepo->method('findRankForCount')->willReturnCallback(
            fn (int $count) => $count >= 5 ? $apprenti : null
        );
        $levelRepo->method('findNextLevel')->willReturn(null);
        $suggestionRepo->method('findPendingCountByUser')->willReturn(0);

        $service = new ContributorLevelService($levelRepo, $suggestionRepo, $correctionRepo);
        $rank = $service->computeRank($this->user);

        $this->assertSame($apprenti, $rank, 'Combined count 3+4=7 should yield rank Apprenti');
    }

    public function testComputeRankBatchReturnsCorrectMap(): void
    {
        $uuid1 = Uuid::v4();
        $uuid2 = Uuid::v4();

        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn($uuid1);

        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn($uuid2);

        $novice = new ContributorLevel();
        $novice->setRankNumber(1);
        $novice->setName('Novice');
        $novice->setThreshold(0);

        $apprenti = new ContributorLevel();
        $apprenti->setRankNumber(2);
        $apprenti->setName('Apprenti');
        $apprenti->setThreshold(5);

        $levelRepo      = $this->createMock(ContributorLevelRepository::class);
        $suggestionRepo = $this->createMock(SuggestionRepository::class);
        $correctionRepo = $this->createMock(CorrectionProposalRepository::class);

        $levelRepo->method('findAllSortedByThreshold')->willReturn([$novice, $apprenti]);
        $suggestionRepo->method('countBatchValidated')->willReturn([
            $uuid1->toRfc4122() => 3,
            $uuid2->toRfc4122() => 7,
        ]);
        $correctionRepo->method('countBatchPublished')->willReturn([
            $uuid1->toRfc4122() => 0,
            $uuid2->toRfc4122() => 1,
        ]);

        $service = new ContributorLevelService($levelRepo, $suggestionRepo, $correctionRepo);
        $result = $service->computeRankBatch([$user1, $user2]);

        $this->assertArrayHasKey($uuid1->toRfc4122(), $result);
        $this->assertArrayHasKey($uuid2->toRfc4122(), $result);
        $this->assertSame($novice, $result[$uuid1->toRfc4122()], 'User1 count=3, rank Novice');
        $this->assertSame($apprenti, $result[$uuid2->toRfc4122()], 'User2 count=8, rank Apprenti');
    }

    public function testGetMetricsReturnsNextLevel(): void
    {
        $chroniqueur = new ContributorLevel();
        $chroniqueur->setRankNumber(3);
        $chroniqueur->setName('Chroniqueur confirmé');
        $chroniqueur->setThreshold(15);

        $archiviste = new ContributorLevel();
        $archiviste->setRankNumber(4);
        $archiviste->setName('Archiviste');
        $archiviste->setThreshold(30);

        $service = $this->makeService(
            validatedCount: 20,
            currentLevel:   $chroniqueur,
            nextLevel:      $archiviste,
        );

        $metrics = $service->getMetrics($this->user);

        $this->assertArrayHasKey('nextLevel', $metrics);
        $this->assertSame($archiviste, $metrics['nextLevel']);
    }

    public function testGetMetricsNextLevelIsNullAtMaxRank(): void
    {
        $grandSage = new ContributorLevel();
        $grandSage->setRankNumber(6);
        $grandSage->setName('Grand Sage');
        $grandSage->setThreshold(100);

        $service = $this->makeService(
            validatedCount: 150,
            currentLevel:   $grandSage,
            nextLevel:      null,
        );

        $metrics = $service->getMetrics($this->user);

        $this->assertArrayHasKey('nextLevel', $metrics);
        $this->assertNull($metrics['nextLevel']);
    }
}
