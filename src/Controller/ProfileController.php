<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Enum\NotificationType;
use App\Entity\NotificationPreference;
use App\Entity\User;
use App\Repository\NotificationPreferenceRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    #[Route('/profile/settings', name: 'profile_settings', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function settings(
        NotificationPreferenceRepository $preferenceRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $preference = $preferenceRepository->findByUser($user)
            ?? new NotificationPreference($user);

        return $this->render('profile/settings.html.twig', [
            'preference' => $preference,
        ]);
    }

    #[Route('/profile/settings/notifications', name: 'profile_notification_preferences', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function saveNotificationPreferences(
        Request $request,
        NotificationPreferenceRepository $preferenceRepository,
        NotificationService $notificationService,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('notification_preferences', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var User $user */
        $user = $this->getUser();

        $preference = $preferenceRepository->findByUser($user);
        if ($preference === null) {
            $preference = new NotificationPreference($user);
            $em->persist($preference);
        }

        $newContribution = (bool) $request->request->get('contribution_validated');
        $newBook = (bool) $request->request->get('book_activity');
        $newModeration = $this->isGranted('ROLE_MODERATOR')
            ? (bool) $request->request->get('moderation_pending')
            : $preference->isModerationPending();
        $newRankUp = (bool) $request->request->get('rank_up');

        if ($preference->isContributionValidated() && !$newContribution) {
            $notificationService->deleteUnreadByType($user, NotificationType::CONTRIBUTION_VALIDATED);
        }
        if ($preference->isBookActivity() && !$newBook) {
            $notificationService->deleteUnreadByType($user, NotificationType::BOOK_ACTIVITY);
        }
        if ($preference->isModerationPending() && !$newModeration) {
            $notificationService->deleteUnreadByType($user, NotificationType::MODERATION_PENDING);
        }
        if ($preference->isRankUp() && !$newRankUp) {
            $notificationService->deleteUnreadByType($user, NotificationType::RANK_UP);
        }

        $preference->setContributionValidated($newContribution);
        $preference->setBookActivity($newBook);
        $preference->setModerationPending($newModeration);
        $preference->setRankUp($newRankUp);

        $em->flush();

        $this->addFlash('success', 'Préférences de notification enregistrées.');

        return $this->redirectToRoute('profile_settings');
    }
}
