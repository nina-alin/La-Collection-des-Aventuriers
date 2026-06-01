<?php

namespace App\Twig\Extension;

use App\Dto\ActiveFilterState;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CatalogueExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('filter_url', [$this, 'filterUrl']),
        ];
    }

    public function filterUrl(ActiveFilterState $state, array $overrides = []): string
    {
        $params = array_merge($state->toUrlParams(), $overrides);

        if (empty($params)) {
            return '/catalogue';
        }

        return '/catalogue?' . http_build_query($params);
    }
}
