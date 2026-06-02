<?php

namespace App\EventListener;

use App\Entity\Enum\NotificationType;
use App\Event\BookAddedToCollectionEvent;
use App\Messenger\Message\NotificationMessage;
use App\Repository\NotificationPreferenceRepository;
use App\Repository\UserCollectionSubscriptionRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener(event: BookAddedToCollectionEvent::class)]
class BookAddedToCollectionListener
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly UserCollectionSubscriptionRepository $subscriptionRepository,
        private readonly NotificationPreferenceRepository $preferenceRepository,
    ) {}

    public function __invoke(BookAddedToCollectionEvent $event): void
    {
        $subscribers = $this->subscriptionRepository->findSubscribersByCollection($event->collection);

        foreach ($subscribers as $subscriber) {
            $preference = $this->preferenceRepository->findByUser($subscriber);
            if ($preference !== null && !$preference->isEnabled(NotificationType::BOOK_ACTIVITY)) {
                continue;
            }

            if ($event->isBatch) {
                $message = sprintf(
                    'La collection %s a été enrichie de %d nouvelles fiches.',
                    $event->collection->getNom(),
                    $event->batchCount
                );
                $sourceId = sprintf(
                    'book_activity:batch:%s:%d',
                    $event->collection->getId(),
                    time()
                );
            } else {
                $message = sprintf(
                    '%s a publié une nouvelle fiche dans une collection que tu suis (%s).',
                    $event->book->getTitle(),
                    $event->collection->getNom()
                );
                $sourceId = sprintf(
                    'book_activity:%s:%s',
                    $event->collection->getId(),
                    $event->book->getId()
                );
            }

            $this->bus->dispatch(new NotificationMessage(
                userId: (string) $subscriber->getId(),
                type: NotificationType::BOOK_ACTIVITY->value,
                message: $message,
                sourceId: $sourceId,
                targetUrl: '/collections/' . $event->collection->getSlug(),
            ));
        }
    }
}
