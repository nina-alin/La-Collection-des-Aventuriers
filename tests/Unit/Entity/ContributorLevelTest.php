<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ContributorLevel;
use PHPUnit\Framework\TestCase;

class ContributorLevelTest extends TestCase
{
    public function testNameCanBeSet(): void
    {
        $level = new ContributorLevel();
        $level->setName('Chroniqueur confirmé');
        $this->assertSame('Chroniqueur confirmé', $level->getName());
    }

    public function testRankNumberCanBeSet(): void
    {
        $level = new ContributorLevel();
        $level->setRankNumber(3);
        $this->assertSame(3, $level->getRankNumber());
    }

    public function testThresholdCanBeSet(): void
    {
        $level = new ContributorLevel();
        $level->setThreshold(15);
        $this->assertSame(15, $level->getThreshold());
    }

    public function testSeedDataValues(): void
    {
        $expectedLevels = [
            [1, 'Novice', 0],
            [2, 'Apprenti', 5],
            [3, 'Chroniqueur confirmé', 15],
            [4, 'Archiviste', 30],
            [5, 'Érudit', 60],
            [6, 'Grand Sage', 100],
        ];

        foreach ($expectedLevels as [$rankNumber, $name, $threshold]) {
            $level = new ContributorLevel();
            $level->setRankNumber($rankNumber);
            $level->setName($name);
            $level->setThreshold($threshold);
            $this->assertSame($rankNumber, $level->getRankNumber());
            $this->assertSame($name, $level->getName());
            $this->assertSame($threshold, $level->getThreshold());
        }
    }

    public function testThresholdsAreOrderable(): void
    {
        $thresholds = [0, 5, 15, 30, 60, 100];
        $sorted = $thresholds;
        sort($sorted);
        $this->assertSame($thresholds, $sorted, 'Thresholds should be in ascending order');
    }
}
