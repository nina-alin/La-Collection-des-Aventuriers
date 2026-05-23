<?php

namespace App\Tests\Twig\Components;

use App\Twig\Components\Toast;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

class ToastTest extends KernelTestCase
{
    use InteractsWithTwigComponents;

    public function testInvalidTypeNormalizesToInfo(): void
    {
        $component = $this->mountTwigComponent(Toast::class, ['message' => 'Test', 'type' => 'invalid']);

        $this->assertSame('info', $component->type);
        $this->assertSame('toast-info', $component->getCssClass());
    }

    public function testErrorType(): void
    {
        $component = $this->mountTwigComponent(Toast::class, ['message' => 'Test', 'type' => 'error']);

        $this->assertSame('toast-error', $component->getCssClass());
    }

    public function testSuccessType(): void
    {
        $component = $this->mountTwigComponent(Toast::class, ['message' => 'Test', 'type' => 'success']);

        $this->assertSame('toast-success', $component->getCssClass());
    }
}
