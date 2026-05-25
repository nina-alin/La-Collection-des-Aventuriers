<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\Collection;
use App\Service\CollectionSlugger;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, entity: Collection::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Collection::class)]
class CollectionListener
{
    public function __construct(private readonly CollectionSlugger $slugger) {}

    public function prePersist(Collection $collection): void
    {
        $collection->setSlug($this->slugger->generateUnique($collection->getNom()));
    }

    public function preUpdate(Collection $collection, PreUpdateEventArgs $eventArgs): void
    {
        if ($eventArgs->hasChangedField('nom')) {
            $collection->setSlug($this->slugger->generateUnique($collection->getNom(), $collection->getSlug()));
        }
    }
}
