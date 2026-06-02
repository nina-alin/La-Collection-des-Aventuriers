<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\NotificationPreference;
use App\Entity\User;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Mapping as ORM;

class UserCreatedListener
{
    #[ORM\PostPersist]
    public function postPersist(User $user, PostPersistEventArgs $event): void
    {
        $em = $event->getObjectManager();
        $preference = new NotificationPreference($user);
        $em->persist($preference);
        $em->flush();
    }
}
