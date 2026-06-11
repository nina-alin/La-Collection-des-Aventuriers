<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Book;
use App\Entity\Enum\BookStatus;
use App\Messenger\Message\BookFollowJob;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsDoctrineListener(Events::postUpdate)]
class BookPublishedFollowListener
{
    public function __construct(private readonly MessageBusInterface $bus) {}

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $book = $args->getObject();
        if (!$book instanceof Book) {
            return;
        }

        $em         = $args->getObjectManager();
        $changeSet  = $em->getUnitOfWork()->getEntityChangeSet($book);

        if (!isset($changeSet['status'])) {
            return;
        }

        [, $newStatus] = $changeSet['status'];

        if ($newStatus !== BookStatus::PUBLISHED) {
            return;
        }

        $this->bus->dispatch(new BookFollowJob((string) $book->getId()));
    }
}
