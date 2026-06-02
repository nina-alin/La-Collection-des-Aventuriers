<?php

namespace App\Messenger\Handler;

use App\Entity\Enum\NotificationType;
use App\Entity\Notification;
use App\Entity\NotificationPreference;
use App\Messenger\Message\NotificationMessage;
use App\Repository\NotificationPreferenceRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NotificationMessageHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly NotificationRepository $notificationRepository,
        private readonly NotificationPreferenceRepository $preferenceRepository,
    ) {}

    public function __invoke(NotificationMessage $message): void
    {
        $user = $this->userRepository->find($message->userId);
        if ($user === null) {
            return;
        }

        $type = NotificationType::from($message->type);

        $preference = $this->preferenceRepository->findByUser($user);
        if ($preference !== null && !$preference->isEnabled($type)) {
            return;
        }

        $notification = new Notification(
            $user,
            $type,
            $message->message,
            $message->sourceId,
            $message->targetUrl,
        );

        try {
            $this->em->persist($notification);
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            return;
        }

        $count = $this->notificationRepository->countForUser($user);
        if ($count > 500) {
            $this->notificationRepository->deleteOldestForUser($user, $count - 500);
        }
    }
}
