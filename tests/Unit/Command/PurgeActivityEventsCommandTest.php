<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\PurgeActivityEventsCommand;
use App\Repository\ActivityEventRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class PurgeActivityEventsCommandTest extends TestCase
{
    private ActivityEventRepository&MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ActivityEventRepository::class);
    }

    public function testCommandCallsDeleteOlderThanWith30DaysAndOutputsCount(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('deleteOlderThan')
            ->with($this->callback(function (\DateTimeImmutable $before): bool {
                $diff = (new \DateTimeImmutable())->diff($before);
                return $diff->days >= 29 && $diff->days <= 31;
            }))
            ->willReturn(42);

        $command = new PurgeActivityEventsCommand($this->repository);
        $app = new Application();
        $app->add($command);

        $tester = new CommandTester($app->find('app:purge-activity-events'));
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('42', $tester->getDisplay());
    }

    public function testCommandOutputsZeroWhenNothingToDelete(): void
    {
        $this->repository->method('deleteOlderThan')->willReturn(0);

        $command = new PurgeActivityEventsCommand($this->repository);
        $app = new Application();
        $app->add($command);

        $tester = new CommandTester($app->find('app:purge-activity-events'));
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('0', $tester->getDisplay());
    }
}
