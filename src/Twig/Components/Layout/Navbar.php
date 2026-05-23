<?php

namespace App\Twig\Components\Layout;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Navbar
{
    public ?string $currentRoute = null;
}
