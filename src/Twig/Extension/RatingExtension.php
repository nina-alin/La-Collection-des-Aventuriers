<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class RatingExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('rating_stars', $this->ratingStars(...)),
        ];
    }

    public function ratingStars(float|int|null $score): array
    {
        if ($score === null) {
            return ['full' => 0, 'half' => false, 'empty' => 5];
        }

        $out5 = $score / 2;
        $rounded = round($out5 * 2) / 2;
        $full = (int) floor($rounded);
        $half = ($rounded - $full) >= 0.5;
        $empty = 5 - $full - ($half ? 1 : 0);

        return ['full' => $full, 'half' => $half, 'empty' => $empty];
    }
}
