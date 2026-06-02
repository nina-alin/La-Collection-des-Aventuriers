<?php

namespace App\Twig\Components\Notification;

use App\Entity\Enum\NotificationType;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class NotificationPanelComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly NotificationService $notificationService,
    ) {}

    public function getNotifications(): array
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if ($user === null) {
            return [];
        }

        $notifications = $this->notificationRepository->findRecentForUser($user);

        if (!$this->isGranted('ROLE_MODERATOR')) {
            $notifications = array_values(array_filter(
                $notifications,
                fn ($n) => $n->getType() !== NotificationType::MODERATION_PENDING
            ));
        }

        return $notifications;
    }

    public function getUnreadCount(): int
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if ($user === null) {
            return 0;
        }

        return $this->notificationRepository->countUnreadForUser($user);
    }

    public function getTodayBoundary(): \DateTimeImmutable
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $tz = new \DateTimeZone($user?->getTimezone() ?? 'UTC');

        return new \DateTimeImmutable('today midnight', $tz);
    }

    #[LiveAction]
    #[IsGranted('ROLE_USER')]
    public function markRead(#[LiveArg] int $id): void
    {
        /** @var User $user */
        $user = $this->getUser();

        $notification = $this->notificationRepository->find($id);
        $targetUrl = null;
        if ($notification !== null && $notification->getUser()->getId() === $user->getId()) {
            $targetUrl = $notification->getTargetUrl();
            $this->notificationService->markRead($user, $id);
        }

        $this->dispatchBrowserEvent('notification:panel:redirect', ['targetUrl' => $targetUrl]);
    }

    #[LiveAction]
    #[IsGranted('ROLE_USER')]
    public function markAllRead(): void
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->notificationService->markAllRead($user);
        $this->dispatchBrowserEvent('notification:panel:read-all');
    }
}
