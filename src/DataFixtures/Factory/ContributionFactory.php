<?php

declare(strict_types=1);

namespace App\DataFixtures\Factory;

use App\Entity\Book;
use App\Entity\Contribution;
use App\Entity\Contributor;
use App\Entity\Enum\ContributionRole;

class ContributionFactory
{
    public static function new(array $overrides = []): Contribution
    {
        $contribution = new Contribution();
        $contribution->setRole($overrides['role'] ?? ContributionRole::Author);

        if (array_key_exists('details', $overrides)) {
            $contribution->setDetails($overrides['details']);
        }
        if (isset($overrides['contributor'])) {
            $contribution->setContributor($overrides['contributor']);
        }
        if (isset($overrides['book'])) {
            $contribution->setBook($overrides['book']);
        }

        return $contribution;
    }
}
