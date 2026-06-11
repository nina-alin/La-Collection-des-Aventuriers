<?php

declare(strict_types=1);

namespace App\Tests\Messenger;

use App\Entity\Book;
use App\Entity\Collection;
use App\Entity\Contributor;
use App\Entity\Enum\NotificationType;
use App\Entity\Notification;
use App\Entity\User;
use App\Messenger\Handler\BookFollowJobHandler;
use App\Messenger\Message\BookFollowJob;
use App\Repository\BookRepository;
use App\Repository\UserFollowedContributorRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class BookFollowJobHandlerTest extends TestCase
{
    private BookRepository&MockObject $bookRepository;
    private UserFollowedContributorRepository&MockObject $followRepository;
    private EntityManagerInterface&MockObject $em;
    private BookFollowJobHandler $handler;

    protected function setUp(): void
    {
        $this->bookRepository   = $this->createMock(BookRepository::class);
        $this->followRepository = $this->createMock(UserFollowedContributorRepository::class);
        $this->em               = $this->createMock(EntityManagerInterface::class);

        $this->handler = new BookFollowJobHandler(
            $this->em,
            $this->bookRepository,
            $this->followRepository,
        );
    }

    public function testBookNotFoundReturnsEarly(): void
    {
        $this->bookRepository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        ($this->handler)(new BookFollowJob('non-existent-id'));
    }

    public function testBookAlreadyNotifiedReturnsEarly(): void
    {
        $book = $this->createMock(Book::class);
        $book->method('getFollowNotificationSentAt')
             ->willReturn(new \DateTimeImmutable());

        $this->bookRepository->method('find')->willReturn($book);

        $this->em->expects($this->never())->method('persist');

        ($this->handler)(new BookFollowJob('book-id'));
    }

    public function testOneFollowerCreatesOneNotification(): void
    {
        $book = $this->createMock(Book::class);
        $book->method('getFollowNotificationSentAt')->willReturn(null);
        $book->method('getId')->willReturn(42);
        $book->method('getTitle')->willReturn('Mon Livre');
        $book->method('getCollection')->willReturn(null);

        $this->bookRepository->method('find')->willReturn($book);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(Uuid::v4());

        $contributor = $this->createMock(Contributor::class);
        $contributor->method('getFirstName')->willReturn('Jean');
        $contributor->method('getLastName')->willReturn('Dupont');

        $this->followRepository->method('findRecipientsForBook')->willReturn([
            [
                'user'         => $user,
                'templateType' => 'contributor',
                'contributor'  => $contributor,
                'collection'   => null,
            ],
        ]);

        $persistedItems = [];
        $this->em->method('persist')->willReturnCallback(function ($entity) use (&$persistedItems) {
            $persistedItems[] = $entity;
        });
        $this->em->expects($this->atLeastOnce())->method('flush');

        ($this->handler)(new BookFollowJob('book-id'));

        $notifications = array_filter($persistedItems, fn($e) => $e instanceof Notification);
        $this->assertCount(1, $notifications);

        $notification = array_values($notifications)[0];
        $this->assertSame(NotificationType::FOLLOW_NOVELTY, $notification->getType());
    }

    public function testMultipleFollowersCreatesMultipleNotifications(): void
    {
        $book = $this->createMock(Book::class);
        $book->method('getFollowNotificationSentAt')->willReturn(null);
        $book->method('getId')->willReturn(42);
        $book->method('getTitle')->willReturn('Mon Livre');
        $book->method('getCollection')->willReturn(null);

        $this->bookRepository->method('find')->willReturn($book);

        $recipients = [];
        for ($i = 0; $i < 3; $i++) {
            $user = $this->createMock(User::class);
            $user->method('getId')->willReturn(Uuid::v4());
            $contrib = $this->createMock(Contributor::class);
            $contrib->method('getFirstName')->willReturn('F');
            $contrib->method('getLastName')->willReturn('L');
            $recipients[] = ['user' => $user, 'templateType' => 'contributor', 'contributor' => $contrib, 'collection' => null];
        }

        $this->followRepository->method('findRecipientsForBook')->willReturn($recipients);

        $persistedItems = [];
        $this->em->method('persist')->willReturnCallback(function ($entity) use (&$persistedItems) {
            $persistedItems[] = $entity;
        });
        $this->em->method('flush');

        ($this->handler)(new BookFollowJob('book-id'));

        $notifications = array_filter($persistedItems, fn($e) => $e instanceof Notification);
        $this->assertCount(3, $notifications);
    }

    public function testFollowNotificationSentAtSetAfterDispatching(): void
    {
        $book = $this->createMock(Book::class);
        $book->method('getFollowNotificationSentAt')->willReturn(null);
        $book->method('getId')->willReturn(42);
        $book->method('getTitle')->willReturn('Mon Livre');
        $book->method('getCollection')->willReturn(null);

        $book->expects($this->once())
             ->method('setFollowNotificationSentAt')
             ->with($this->isInstanceOf(\DateTimeImmutable::class));

        $this->bookRepository->method('find')->willReturn($book);
        $this->followRepository->method('findRecipientsForBook')->willReturn([]);
        $this->em->method('persist');
        $this->em->method('flush');

        ($this->handler)(new BookFollowJob('book-id'));
    }

    public function testCollectionTemplateTypeUsedWhenOnlyCollectionFollow(): void
    {
        $book = $this->createMock(Book::class);
        $book->method('getFollowNotificationSentAt')->willReturn(null);
        $book->method('getId')->willReturn(42);
        $book->method('getTitle')->willReturn('Mon Livre');

        $collection = $this->createMock(Collection::class);
        $collection->method('getNom')->willReturn('Ma Collection');
        $collection->method('getSlug')->willReturn('ma-collection');
        $book->method('getCollection')->willReturn($collection);

        $this->bookRepository->method('find')->willReturn($book);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(Uuid::v4());

        $this->followRepository->method('findRecipientsForBook')->willReturn([
            [
                'user'         => $user,
                'templateType' => 'collection',
                'contributor'  => null,
                'collection'   => $collection,
            ],
        ]);

        $persistedItems = [];
        $this->em->method('persist')->willReturnCallback(function ($entity) use (&$persistedItems) {
            $persistedItems[] = $entity;
        });
        $this->em->method('flush');

        ($this->handler)(new BookFollowJob('book-id'));

        $notifications = array_filter($persistedItems, fn($e) => $e instanceof Notification);
        $this->assertCount(1, $notifications);
    }
}
