<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\Contributor;
use App\Service\ContributorSlugger;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, entity: Contributor::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Contributor::class)]
class ContributorListener
{
    public function __construct(private readonly ContributorSlugger $slugger) {}

    public function prePersist(Contributor $contributor): void
    {
        $contributor->setSlug($this->slugger->generateUnique($contributor));
    }

    public function preUpdate(Contributor $contributor, PreUpdateEventArgs $event): void
    {
        if ($event->hasChangedField('pseudo') || $event->hasChangedField('firstName') || $event->hasChangedField('lastName')) {
            $contributor->setSlug($this->slugger->generateUnique($contributor));
        }
    }
}
