<?php

namespace App\EventListener;

use App\Entity\ActivityEvent;
use App\Entity\Enum\ActivityEventType;
use App\Event\BookAddedToWishlistEvent;
use App\Event\BookPublishedEvent;
use App\Event\ReviewSubmittedEvent;
use App\Event\SuggestionModeratedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ReviewSubmittedEvent::class)]
#[AsEventListener(event: BookPublishedEvent::class)]
#[AsEventListener(event: SuggestionModeratedEvent::class)]
#[AsEventListener(event: BookAddedToWishlistEvent::class)]
class ActivityEventListener
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function __invoke(ReviewSubmittedEvent|BookPublishedEvent|SuggestionModeratedEvent|BookAddedToWishlistEvent $event): void
    {
        match (true) {
            $event instanceof ReviewSubmittedEvent => $this->onReviewSubmitted($event),
            $event instanceof BookPublishedEvent => $this->onBookPublished($event),
            $event instanceof SuggestionModeratedEvent => $this->onSuggestionModerated($event),
            $event instanceof BookAddedToWishlistEvent => $this->onBookAddedToWishlist($event),
        };
    }

    private function onReviewSubmitted(ReviewSubmittedEvent $event): void
    {
        $ae = $this->createBase($event->actor, ActivityEventType::SOCIAL);
        $ae->setBookTitle($event->book->getTitle());
        $ae->setBookSlug($event->book->getSlug());
        $this->persist($ae);
    }

    private function onBookPublished(BookPublishedEvent $event): void
    {
        $ae = $this->createBase($event->actor, ActivityEventType::CONTRIBUTION);
        $ae->setBookTitle($event->book->getTitle());
        $ae->setBookSlug($event->book->getSlug());
        $this->persist($ae);
    }

    private function onSuggestionModerated(SuggestionModeratedEvent $event): void
    {
        $ae = $this->createBase($event->actor, ActivityEventType::MODERATION);
        $formData = $event->suggestion->getFormData();
        $title = $formData['title'] ?? $formData['name'] ?? null;
        $ae->setBookTitle($title);
        $ae->setStatusBadge($event->newStatus->value);
        $this->persist($ae);
    }

    private function onBookAddedToWishlist(BookAddedToWishlistEvent $event): void
    {
        $ae = $this->createBase($event->actor, ActivityEventType::PERSONAL);
        $ae->setBookTitle($event->book->getTitle());
        $ae->setBookSlug($event->book->getSlug());
        $this->persist($ae);
    }

    private function createBase(\App\Entity\User $actor, ActivityEventType $type): ActivityEvent
    {
        $ae = new ActivityEvent();
        $ae->setActorUser($actor);
        $ae->setType($type);
        $ae->setActorPseudo($actor->getPseudo());

        $displayName = $actor->getDisplayName() ?? $actor->getPseudo();
        $words = preg_split('/[\s_\-]+/', $displayName);
        if ($words !== false && count($words) >= 2) {
            $initials = strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[count($words) - 1], 0, 1));
        } else {
            $initials = strtoupper(mb_substr($displayName, 0, min(2, mb_strlen($displayName))));
        }
        $ae->setActorInitials($initials ?: null);

        return $ae;
    }

    private function persist(ActivityEvent $ae): void
    {
        $this->entityManager->persist($ae);
        $this->entityManager->flush();
    }
}
