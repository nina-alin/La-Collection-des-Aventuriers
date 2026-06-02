<?php

namespace App\EventListener;

use App\Entity\Enum\NotificationType;
use App\Event\ModerationPendingEvent;
use App\Messenger\Message\NotificationMessage;
use App\Repository\NotificationPreferenceRepository;
use App\Repository\UserRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener(event: ModerationPendingEvent::class)]
class ModerationPendingListener
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly UserRepository $userRepository,
        private readonly NotificationPreferenceRepository $preferenceRepository,
    ) {}

    public function __invoke(ModerationPendingEvent $event): void
    {
        $moderators = $this->userRepository->findByRole('ROLE_MODERATOR');

        foreach ($moderators as $moderator) {
            $preference = $this->preferenceRepository->findByUser($moderator);
            if ($preference !== null && !$preference->isEnabled(NotificationType::MODERATION_PENDING)) {
                continue;
            }

            $this->bus->dispatch(new NotificationMessage(
                userId: (string) $moderator->getId(),
                type: NotificationType::MODERATION_PENDING->value,
                message: 'Une nouvelle suggestion attend ta modération.',
                sourceId: 'moderation_pending:' . $event->suggestion->getId() . ':' . $moderator->getId(),
                targetUrl: '/suggestions/' . $event->suggestion->getId(),
            ));
        }
    }
}
