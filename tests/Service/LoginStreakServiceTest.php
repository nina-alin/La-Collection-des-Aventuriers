<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\LoginStreakService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class LoginStreakServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private LoginStreakService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->service = new LoginStreakService($this->em);
    }

    private function makeUser(string $timezone = 'UTC'): User
    {
        $user = new User();
        $user->setTimezone($timezone);
        return $user;
    }

    public function testFirstLoginInitializesStreakToOne(): void
    {
        $user = $this->makeUser();
        $this->assertNull($user->getLastLoginDate());

        $this->em->expects($this->once())->method('flush');

        $this->service->update($user);

        $this->assertSame(1, $user->getLoginStreak());
        $this->assertNotNull($user->getLastLoginDate());
    }

    public function testYesterdayLoginIncrementsStreak(): void
    {
        $user = $this->makeUser();
        $user->setLoginStreak(5);

        $yesterday = new \DateTimeImmutable('yesterday midnight', new \DateTimeZone('UTC'));
        $user->setLastLoginDate($yesterday);

        $this->em->expects($this->once())->method('flush');

        $this->service->update($user);

        $this->assertSame(6, $user->getLoginStreak());
    }

    public function testBeforeYesterdayResetsStreakToOne(): void
    {
        $user = $this->makeUser();
        $user->setLoginStreak(10);

        $threeDaysAgo = new \DateTimeImmutable('-3 days midnight', new \DateTimeZone('UTC'));
        $user->setLastLoginDate($threeDaysAgo);

        $this->em->expects($this->once())->method('flush');

        $this->service->update($user);

        $this->assertSame(1, $user->getLoginStreak());
    }

    public function testSameDayLoginIsNoOp(): void
    {
        $user = $this->makeUser();
        $user->setLoginStreak(3);

        $today = new \DateTimeImmutable('today midnight', new \DateTimeZone('UTC'));
        $user->setLastLoginDate($today);

        $this->em->expects($this->never())->method('flush');

        $this->service->update($user);

        $this->assertSame(3, $user->getLoginStreak());
    }

    public function testTimezoneHandlingNonUtcUser(): void
    {
        $user = $this->makeUser('America/New_York');
        $user->setLoginStreak(2);

        $tz = new \DateTimeZone('America/New_York');
        $yesterday = new \DateTimeImmutable('yesterday midnight', $tz);
        $user->setLastLoginDate($yesterday);

        $this->em->expects($this->once())->method('flush');

        $this->service->update($user);

        $this->assertSame(3, $user->getLoginStreak());
    }
}
