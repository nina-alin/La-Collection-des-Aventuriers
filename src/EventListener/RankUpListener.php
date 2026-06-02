<?php

namespace App\EventListener;

use App\Entity\Enum\NotificationType;
use App\Event\RankUpEvent;
use App\Messenger\Message\NotificationMessage;
use App\Repository\NotificationPreferenceRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener(event: RankUpEvent::class)]
class RankUpListener
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly NotificationPreferenceRepository $preferenceRepository,
    ) {}

    public function __invoke(RankUpEvent $event): void
    {
        $user = $event->user;

        $preference = $this->preferenceRepository->findByUser($user);
        if ($preference !== null && !$preference->isEnabled(NotificationType::RANK_UP)) {
            return;
        }

        $this->bus->dispatch(new NotificationMessage(
            userId: (string) $user->getId(),
            type: NotificationType::RANK_UP->value,
            message: sprintf('Félicitations, tu as atteint le niveau %s !', $event->newLevel->getName()),
            sourceId: sprintf('rank_up:%s:%s', $user->getId(), $event->newLevel->getRankNumber()),
            targetUrl: null,
        ));
    }
}
