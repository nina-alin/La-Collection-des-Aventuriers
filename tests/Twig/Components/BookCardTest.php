<?php

namespace App\Tests\Twig\Components;

use App\Twig\Components\Book\Card;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

class BookCardTest extends KernelTestCase
{
    use InteractsWithTwigComponents;

    public function testDefaultFallbacks(): void
    {
        $component = $this->mountTwigComponent(Card::class, []);

        $this->assertSame('Sans titre', $component->title);
        $this->assertSame('Auteur inconnu', $component->author);
    }

    public function testCustomTitle(): void
    {
        $component = $this->mountTwigComponent(Card::class, ['title' => 'Dune']);

        $this->assertSame('Dune', $component->title);
    }

    public function testSkeletonClass(): void
    {
        $rendered = $this->renderTwigComponent(Card::class, ['loading' => true]);

        $this->assertStringContainsString('card-book--skeleton', $rendered->toString());
    }
}
