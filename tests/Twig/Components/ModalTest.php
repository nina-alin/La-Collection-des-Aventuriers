<?php

namespace App\Tests\Twig\Components;

use App\Twig\Components\Modal;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

class ModalTest extends KernelTestCase
{
    use InteractsWithTwigComponents;

    public function testInvalidVariantNormalizesToDefault(): void
    {
        $component = $this->mountTwigComponent(Modal::class, ['id' => 'test', 'title' => 'Test', 'variant' => 'invalid']);

        $this->assertSame('default', $component->variant);
    }

    public function testInvalidSizeNormalizesToMd(): void
    {
        $component = $this->mountTwigComponent(Modal::class, ['id' => 'test', 'title' => 'Test', 'size' => 'invalid']);

        $this->assertSame('md', $component->size);
    }

    public function testDangerVariantClass(): void
    {
        $rendered = $this->renderTwigComponent(Modal::class, ['id' => 'test', 'title' => 'Test', 'variant' => 'danger']);

        $this->assertStringContainsString('danger-accent', $rendered->toString());
    }
}
