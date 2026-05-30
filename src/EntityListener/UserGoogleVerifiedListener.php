<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\User;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;

class UserGoogleVerifiedListener
{
    #[ORM\PrePersist]
    public function prePersist(User $user, PrePersistEventArgs $event): void
    {
        if ($user->getGoogleId() !== null) {
            $user->setIsEmailVerified(true);
        }
    }

    #[ORM\PreUpdate]
    public function preUpdate(User $user, PreUpdateEventArgs $event): void
    {
        if ($user->getGoogleId() !== null) {
            $user->setIsEmailVerified(true);
        }
    }
}
