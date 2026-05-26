<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\Book;
use App\Entity\Contribution;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::onFlush, entity: Book::class)]
class BookSoftDeleteListener
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof Book) {
                continue;
            }

            $changeSet = $uow->getEntityChangeSet($entity);
            if (!isset($changeSet['deletedAt'])) {
                continue;
            }

            [$old, $new] = $changeSet['deletedAt'];
            if ($old !== null || $new === null) {
                continue;
            }

            foreach ($entity->getContributions() as $contribution) {
                $contribution->setDeletedAt($new);
                $contributionMeta = $em->getClassMetadata(Contribution::class);
                $uow->recomputeSingleEntityChangeSet($contributionMeta, $contribution);
            }
        }
    }
}
