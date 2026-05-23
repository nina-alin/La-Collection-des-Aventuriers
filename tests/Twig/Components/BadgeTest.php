<?php

namespace App\Tests\Twig\Components;

use App\Twig\Components\Badge;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

class BadgeTest extends KernelTestCase
{
    use InteractsWithTwigComponents;

    public function testUnrecognizedVariantNormalizesToPrimary(): void
    {
        $component = $this->mountTwigComponent(Badge::class, ['label' => 'Test', 'variant' => 'xyz']);

        $this->assertSame('primary', $component->variant);
    }

    public function testPendingVariantClass(): void
    {
        $rendered = $this->renderTwigComponent(Badge::class, ['label' => 'Pending', 'variant' => 'pending']);

        $this->assertStringContainsString('badge-status-pending', $rendered->toString());
    }
}
