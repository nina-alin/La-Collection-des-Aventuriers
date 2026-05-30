<?php

namespace App\Tests\Unit\Repository;

use App\Dto\ReviewStats;
use PHPUnit\Framework\TestCase;

class ReviewRepositoryStatsTest extends TestCase
{
    public function testReviewStatsWithZeroReviewsReturnsZeros(): void
    {
        $stats = new ReviewStats(0.0, 0, array_fill(0, 10, 0), array_fill(0, 10, 0.0), []);

        $this->assertSame(0.0, $stats->averageScore);
        $this->assertSame(0, $stats->totalCount);
        $this->assertCount(10, $stats->distribution);
        $this->assertCount(10, $stats->histogramHeights);
        $this->assertCount(0, $stats->lastEvaluators);
        foreach ($stats->distribution as $count) {
            $this->assertSame(0, $count);
        }
        foreach ($stats->histogramHeights as $height) {
            $this->assertSame(0.0, $height);
        }
    }

    public function testReviewStatsStructureWithValues(): void
    {
        $distribution = array_fill(0, 10, 0);
        $distribution[8] = 5;
        $distribution[9] = 3;
        $distribution[6] = 2;

        $histogramHeights = array_map(
            fn (int $count) => $count > 0 ? round($count / 5 * 100.0, 2) : 0.0,
            $distribution
        );

        $stats = new ReviewStats(9.2, 10, $distribution, $histogramHeights, []);

        $this->assertSame(9.2, $stats->averageScore);
        $this->assertSame(10, $stats->totalCount);
        $this->assertSame(100.0, $stats->histogramHeights[8]);
        $this->assertSame(60.0, $stats->histogramHeights[9]);
        $this->assertSame(40.0, $stats->histogramHeights[6]);
        $this->assertSame(0.0, $stats->histogramHeights[0]);
    }

    public function testHistogramMaxBarIs100(): void
    {
        $distribution = [0, 0, 0, 0, 0, 0, 0, 0, 10, 5];
        $maxCount = max($distribution);
        $histogramHeights = array_map(
            fn (int $count) => $maxCount > 0 ? round($count / $maxCount * 100.0, 2) : 0.0,
            $distribution
        );

        $this->assertSame(100.0, max($histogramHeights));
        $this->assertSame(100.0, $histogramHeights[8]);
        $this->assertSame(50.0, $histogramHeights[9]);
    }
}
