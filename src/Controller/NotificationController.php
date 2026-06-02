<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class NotificationController extends AbstractController
{
    #[Route('/notifications/{id}/read', name: 'notification_mark_read', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function markRead(
        int $id,
        Request $request,
        NotificationRepository $notificationRepository,
        NotificationService $notificationService,
    ): Response {
        if (!$this->isCsrfTokenValid('notification_read_' . $id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var User $user */
        $user = $this->getUser();

        $notification = $notificationRepository->find($id);
        if ($notification === null || $notification->getUser()->getId() !== $user->getId()) {
            throw new NotFoundHttpException();
        }

        $notificationService->markRead($user, $id);

        $targetUrl = $notification->getTargetUrl();
        if ($targetUrl === null) {
            $this->addFlash('info', 'Cette notification n\'a plus de cible.');
            return $this->redirectToRoute('home');
        }

        return $this->redirect($targetUrl);
    }

    #[Route('/notifications/read-all', name: 'notification_mark_all_read', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function markAllRead(
        Request $request,
        NotificationService $notificationService,
    ): Response {
        if (!$this->isCsrfTokenValid('notifications_read_all', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var User $user */
        $user = $this->getUser();
        $notificationService->markAllRead($user);

        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?? $this->generateUrl('notification_history'));
    }

    #[Route('/notifications', name: 'notification_history', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(
        Request $request,
        NotificationRepository $notificationRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $rawPage = $request->query->get('page', '1');
        if (!ctype_digit((string) $rawPage) || (int) $rawPage < 1) {
            $rawPage = '1';
        }
        $page = (int) $rawPage;

        $notifications = $notificationRepository->findPaginatedForUser($user, $page);
        $totalItems = count($notifications);
        $perPage = 20;
        $totalPages = $totalItems > 0 ? (int) ceil($totalItems / $perPage) : 1;

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
            'currentPage'   => $page,
            'totalPages'    => $totalPages,
        ]);
    }
}
