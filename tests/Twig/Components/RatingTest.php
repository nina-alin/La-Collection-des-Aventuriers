<?php

namespace App\Tests\Twig\Components;

use App\Twig\Components\Rating;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

class RatingTest extends KernelTestCase
{
    use InteractsWithTwigComponents;

    public function testNegativeValueClampedToZero(): void
    {
        $component = $this->mountTwigComponent(Rating::class, ['value' => -1.0]);

        $this->assertSame(0.0, $component->value);
    }

    public function testValueAboveMaxClamped(): void
    {
        $component = $this->mountTwigComponent(Rating::class, ['value' => 6.0, 'max' => 5]);

        $this->assertSame(5.0, $component->value);
    }

    public function testInvalidSizeNormalizesToMd(): void
    {
        $component = $this->mountTwigComponent(Rating::class, ['value' => 3.0, 'size' => 'invalid']);

        $this->assertSame('md', $component->size);
    }
}
