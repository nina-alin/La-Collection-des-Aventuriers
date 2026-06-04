<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Twig\Extension\RatingExtension;
use PHPUnit\Framework\TestCase;

class RatingExtensionTest extends TestCase
{
    private RatingExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new RatingExtension();
    }

    public function testScoreZeroReturnsNoStars(): void
    {
        $result = $this->extension->ratingStars(0);
        $this->assertSame(0, $result['full']);
        $this->assertFalse($result['half']);
        $this->assertSame(5, $result['empty']);
    }

    public function testScoreTenReturnsFiveStars(): void
    {
        $result = $this->extension->ratingStars(10);
        $this->assertSame(5, $result['full']);
        $this->assertFalse($result['half']);
        $this->assertSame(0, $result['empty']);
    }

    public function testScoreSevenReturnsThreeAndHalfStars(): void
    {
        $result = $this->extension->ratingStars(7);
        $this->assertSame(3, $result['full']);
        $this->assertTrue($result['half']);
        $this->assertSame(1, $result['empty']);
    }

    public function testScoreEightReturnsFourStars(): void
    {
        $result = $this->extension->ratingStars(8);
        $this->assertSame(4, $result['full']);
        $this->assertFalse($result['half']);
        $this->assertSame(1, $result['empty']);
    }

    public function testNullScoreReturnsZeroStars(): void
    {
        $result = $this->extension->ratingStars(null);
        $this->assertSame(0, $result['full']);
        $this->assertFalse($result['half']);
        $this->assertSame(5, $result['empty']);
    }

    public function testFullAndHalfAndEmptyAlwaysSumToFive(): void
    {
        foreach ([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10] as $score) {
            $result = $this->extension->ratingStars($score);
            $sum = $result['full'] + ($result['half'] ? 1 : 0) + $result['empty'];
            $this->assertSame(5, $sum, "Sum must be 5 for score $score");
        }
    }
}
