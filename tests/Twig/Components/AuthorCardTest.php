<?php

namespace App\Tests\Twig\Components;

use App\Twig\Components\Author\Card;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

class AuthorCardTest extends KernelTestCase
{
    use InteractsWithTwigComponents;

    public function testDefaultFallback(): void
    {
        $component = $this->mountTwigComponent(Card::class, []);

        $this->assertSame('Auteur inconnu', $component->name);
    }

    public function testCustomName(): void
    {
        $component = $this->mountTwigComponent(Card::class, ['name' => 'Tolkien']);

        $this->assertSame('Tolkien', $component->name);
    }

    public function testSkeletonClass(): void
    {
        $rendered = $this->renderTwigComponent(Card::class, ['loading' => true]);

        $this->assertStringContainsString('card-author--skeleton', $rendered->toString());
    }
}
