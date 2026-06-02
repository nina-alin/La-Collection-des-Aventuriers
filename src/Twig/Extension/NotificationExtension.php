<?php

namespace App\Twig\Extension;

use App\Entity\User;
use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class NotificationExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly NotificationRepository $notificationRepository,
    ) {}

    public function getGlobals(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return ['unread_count' => 0];
        }

        return ['unread_count' => $this->notificationRepository->countUnreadForUser($user)];
    }
}
