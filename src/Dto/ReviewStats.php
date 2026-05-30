<?php

namespace App\Dto;

use App\Entity\User;

readonly class ReviewStats
{
    /**
     * @param int[] $distribution int[10]: index 0=score 1, index 9=score 10
     * @param float[] $histogramHeights float[10]: 0.0–100.0
     * @param (User|null)[] $lastEvaluators max 4, ordered by updatedAt DESC
     */
    public function __construct(
        public float $averageScore,
        public int $totalCount,
        public array $distribution,
        public array $histogramHeights,
        public array $lastEvaluators,
    ) {
    }
}
