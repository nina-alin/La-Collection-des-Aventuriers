<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\Book;
use App\Entity\Enum\BookStatus;
use App\EventListener\BookPublishedFollowListener;
use App\Messenger\Message\BookFollowJob;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class BookPublishedFollowListenerTest extends TestCase
{
    private function makeArgs(object $entity, array $changeSet): PostUpdateEventArgs
    {
        $uow = $this->createMock(UnitOfWork::class);
        $uow->method('getEntityChangeSet')->willReturn($changeSet);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getUnitOfWork')->willReturn($uow);

        return new PostUpdateEventArgs($entity, $em);
    }

    public function testDispatchesJobWhenStatusChangesToPublished(): void
    {
        $book = $this->createMock(Book::class);
        $book->method('getId')->willReturn(99);

        $args = $this->makeArgs($book, [
            'status' => [BookStatus::PENDING, BookStatus::PUBLISHED],
        ]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(BookFollowJob::class))
            ->willReturn(new Envelope(new BookFollowJob('99')));

        $listener = new BookPublishedFollowListener($bus);
        $listener->postUpdate($args);
    }

    public function testDoesNotDispatchWhenStatusChangesToRejected(): void
    {
        $book = $this->createMock(Book::class);

        $args = $this->makeArgs($book, [
            'status' => [BookStatus::PENDING, BookStatus::REJECTED],
        ]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $listener = new BookPublishedFollowListener($bus);
        $listener->postUpdate($args);
    }

    public function testDoesNotDispatchForNonBookEntity(): void
    {
        $nonBook = new \stdClass();

        $uow = $this->createMock(UnitOfWork::class);
        $em  = $this->createMock(EntityManagerInterface::class);
        $em->method('getUnitOfWork')->willReturn($uow);

        $args = new PostUpdateEventArgs($nonBook, $em);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $listener = new BookPublishedFollowListener($bus);
        $listener->postUpdate($args);
    }

    public function testDoesNotDispatchWhenStatusUnchanged(): void
    {
        $book = $this->createMock(Book::class);

        $args = $this->makeArgs($book, [
            'title' => ['Old Title', 'New Title'],
        ]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $listener = new BookPublishedFollowListener($bus);
        $listener->postUpdate($args);
    }
}
