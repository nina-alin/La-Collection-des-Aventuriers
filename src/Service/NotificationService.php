<?php

namespace App\Service;

use App\Entity\Enum\NotificationType;
use App\Entity\User;
use App\Repository\NotificationPreferenceRepository;
use App\Repository\NotificationRepository;

class NotificationService
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly NotificationPreferenceRepository $preferenceRepository,
    ) {}

    public function markRead(User $user, int $id): void
    {
        $this->notificationRepository->markReadById($user, $id);
    }

    public function markAllRead(User $user): void
    {
        $this->notificationRepository->markAllReadForUser($user);
    }

    public function getUnreadCount(User $user): int
    {
        return $this->notificationRepository->countUnreadForUser($user);
    }

    public function deleteUnreadByType(User $user, NotificationType $type): void
    {
        $this->notificationRepository->deleteUnreadByTypeForUser($user, $type);
    }
}
