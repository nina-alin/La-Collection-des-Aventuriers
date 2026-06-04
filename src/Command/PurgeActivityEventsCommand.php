<?php

namespace App\Command;

use App\Repository\ActivityEventRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:purge-activity-events', description: 'Purge activity events older than 30 days')]
class PurgeActivityEventsCommand extends Command
{
    public function __construct(
        private readonly ActivityEventRepository $activityEventRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $before = new \DateTimeImmutable('-30 days', new \DateTimeZone('UTC'));
        $deleted = $this->activityEventRepository->deleteOlderThan($before);
        $output->writeln(sprintf('Purged %d activity event(s) older than 30 days.', $deleted));

        return Command::SUCCESS;
    }
}
