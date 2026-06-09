<?php

namespace App\EventListener;

use App\Entity\Enum\NotificationType;
use App\Event\ContributionRefusedEvent;
use App\Messenger\Message\NotificationMessage;
use App\Repository\NotificationPreferenceRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener(event: ContributionRefusedEvent::class)]
class ContributionRefusedListener
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly NotificationPreferenceRepository $preferenceRepository,
    ) {}

    public function __invoke(ContributionRefusedEvent $event): void
    {
        $recipient = $event->recipient;

        $preference = $this->preferenceRepository->findByUser($recipient);
        if ($preference === null || $preference->isEnabled(NotificationType::CONTRIBUTION_REFUSED)) {
            $message = 'Ta suggestion "' . $event->title . '" a été refusée.';
            if ($event->reason !== null) {
                $message .= ' Motif : ' . $event->reason;
            }

            $this->bus->dispatch(new NotificationMessage(
                userId: (string) $recipient->getId(),
                type: NotificationType::CONTRIBUTION_REFUSED->value,
                message: $message,
                sourceId: null,
                targetUrl: null,
            ));
        }
    }
}
