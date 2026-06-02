<?php

namespace App\EventListener;

use App\Entity\Enum\NotificationType;
use App\Event\ContributionValidatedEvent;
use App\Event\RankUpEvent;
use App\Messenger\Message\NotificationMessage;
use App\Repository\NotificationPreferenceRepository;
use App\Service\ContributorLevelService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener(event: ContributionValidatedEvent::class)]
class ContributionValidatedListener
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly NotificationPreferenceRepository $preferenceRepository,
        private readonly ContributorLevelService $contributorLevelService,
    ) {}

    public function __invoke(ContributionValidatedEvent $event): void
    {
        $recipient = $event->recipient;

        $preference = $this->preferenceRepository->findByUser($recipient);
        if ($preference === null || $preference->isEnabled(NotificationType::CONTRIBUTION_VALIDATED)) {
            $oldRank = $this->contributorLevelService->computeRank($recipient);

            $this->bus->dispatch(new NotificationMessage(
                userId: (string) $recipient->getId(),
                type: NotificationType::CONTRIBUTION_VALIDATED->value,
                message: 'Ta contribution "' . $event->workEntry->getTitle() . '" a été validée !',
                sourceId: 'contribution_validated:' . $event->workEntry->getId(),
                targetUrl: null,
            ));

            $newRank = $this->contributorLevelService->computeRank($recipient);
            if ($newRank !== null && ($oldRank === null || $oldRank->getId() !== $newRank->getId())) {
                $this->dispatcher->dispatch(new RankUpEvent($recipient, $newRank));
            }
        }
    }
}
