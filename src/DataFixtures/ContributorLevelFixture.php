<?php

namespace App\DataFixtures;

use App\Entity\ContributorLevel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class ContributorLevelFixture extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['contributor_level'];
    }

    public function load(ObjectManager $manager): void
    {
        $levels = [
            [1, 'Novice', 0],
            [2, 'Apprenti', 5],
            [3, 'Chroniqueur confirmé', 15],
            [4, 'Archiviste', 30],
            [5, 'Érudit', 60],
            [6, 'Grand Sage', 100],
        ];

        foreach ($levels as [$rankNumber, $name, $threshold]) {
            $level = new ContributorLevel();
            $level->setRankNumber($rankNumber);
            $level->setName($name);
            $level->setThreshold($threshold);
            $manager->persist($level);
        }

        $manager->flush();
    }
}
