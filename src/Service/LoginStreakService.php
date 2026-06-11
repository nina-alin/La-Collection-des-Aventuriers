<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class LoginStreakService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function update(User $user): void
    {
        $tz = new \DateTimeZone($user->getTimezone() ?? 'UTC');
        $today = new \DateTimeImmutable('today midnight', $tz);

        $lastLoginDate = $user->getLastLoginDate();

        if ($lastLoginDate === null) {
            $user->setLoginStreak(1);
            $user->setLastLoginDate($today);
            $this->em->flush();
            return;
        }

        $lastInUserTz = $lastLoginDate->setTimezone($tz);
        $diff = (int) $today->diff($lastInUserTz)->days;

        if ($diff === 0) {
            return;
        }

        if ($diff === 1) {
            $user->setLoginStreak($user->getLoginStreak() + 1);
        } else {
            $user->setLoginStreak(1);
        }

        $user->setLastLoginDate($today);
        $this->em->flush();
    }
}
