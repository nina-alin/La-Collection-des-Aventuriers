<?php

namespace App\Tests\Notification\EventListener;

use App\Entity\Book;
use App\Entity\Collection;
use App\Entity\Enum\NotificationType;
use App\Entity\NotificationPreference;
use App\Entity\User;
use App\Event\BookAddedToCollectionEvent;
use App\EventListener\BookAddedToCollectionListener;
use App\Messenger\Message\NotificationMessage;
use App\Repository\NotificationPreferenceRepository;
use App\Repository\UserCollectionSubscriptionRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class BookAddedToCollectionListenerTest extends TestCase
{
    private function makeUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(Uuid::v4());
        return $user;
    }

    private function makeBook(): Book
    {
        $book = $this->createMock(Book::class);
        $book->method('getId')->willReturn(1);
        $book->method('getTitle')->willReturn('Mon livre');
        return $book;
    }

    private function makeCollection(): Collection
    {
        $col = $this->createMock(Collection::class);
        $col->method('getId')->willReturn(Uuid::v4());
        $col->method('getNom')->willReturn('Ma collection');
        $col->method('getSlug')->willReturn('ma-collection');
        return $col;
    }

    public function testDispatchesOneMessagePerSubscriber(): void
    {
        $user1 = $this->makeUser();
        $user2 = $this->makeUser();
        $book = $this->makeBook();
        $col = $this->makeCollection();

        $subRepo = $this->createMock(UserCollectionSubscriptionRepository::class);
        $subRepo->method('findSubscribersByCollection')->willReturn([$user1, $user2]);

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn(null);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $listener = new BookAddedToCollectionListener($bus, $subRepo, $prefRepo);
        $listener(new BookAddedToCollectionEvent($book, $col));
    }

    public function testUsesSingularTemplateForSingleBook(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $col = $this->makeCollection();

        $subRepo = $this->createMock(UserCollectionSubscriptionRepository::class);
        $subRepo->method('findSubscribersByCollection')->willReturn([$user]);

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn(null);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (NotificationMessage $msg) {
                return str_contains($msg->message, 'Mon livre') && $msg->type === 'book_activity';
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $listener = new BookAddedToCollectionListener($bus, $subRepo, $prefRepo);
        $listener(new BookAddedToCollectionEvent($book, $col, false));
    }

    public function testUsesBatchTemplateWhenIsBatchTrue(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $col = $this->makeCollection();

        $subRepo = $this->createMock(UserCollectionSubscriptionRepository::class);
        $subRepo->method('findSubscribersByCollection')->willReturn([$user]);

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn(null);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (NotificationMessage $msg) {
                return str_contains($msg->message, 'enrichie') && $msg->type === 'book_activity';
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $listener = new BookAddedToCollectionListener($bus, $subRepo, $prefRepo);
        $listener(new BookAddedToCollectionEvent($book, $col, true, 5));
    }

    public function testSkipsSubscribersWithDisabledBookActivityPreference(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $col = $this->makeCollection();

        $subRepo = $this->createMock(UserCollectionSubscriptionRepository::class);
        $subRepo->method('findSubscribersByCollection')->willReturn([$user]);

        $pref = $this->createMock(NotificationPreference::class);
        $pref->method('isEnabled')->with(NotificationType::BOOK_ACTIVITY)->willReturn(false);

        $prefRepo = $this->createMock(NotificationPreferenceRepository::class);
        $prefRepo->method('findByUser')->willReturn($pref);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $listener = new BookAddedToCollectionListener($bus, $subRepo, $prefRepo);
        $listener(new BookAddedToCollectionEvent($book, $col));
    }
}
