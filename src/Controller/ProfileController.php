<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Enum\NotificationType;
use App\Entity\Enum\UserListType;
use App\Entity\GhostUser;
use App\Entity\NotificationPreference;
use App\Entity\User;
use App\Repository\NotificationPreferenceRepository;
use App\Repository\UserBookRepository;
use App\Repository\UserCollectionSubscriptionRepository;
use App\Repository\UserContributorSubscriptionRepository;
use App\Repository\UserListVisibilityRepository;
use App\Repository\UserRepository;
use App\Service\AccountDeletionService;
use App\Service\ContributorLevelService;
use App\Service\EmailChangeService;
use App\Service\LoginStreakService;
use App\Service\NotificationService;
use App\Service\ProfileKpiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    private const MODERATOR_PERMISSIONS = [
        ['label' => 'Lire le catalogue intégral', 'granted' => true],
        ['label' => 'Déposer notes & commentaires', 'granted' => true],
        ['label' => 'Suggérer de nouvelles fiches', 'granted' => true],
        ['label' => 'Valider les suggestions des autres', 'granted' => true],
        ['label' => 'Corriger les fiches existantes', 'granted' => true],
        ['label' => 'Signaler du contenu', 'granted' => true],
        ['label' => 'Bannir des aventuriers', 'granted' => false],
        ['label' => "Modifier les rôles d'autrui", 'granted' => false],
    ];

    private const ADMIN_PERMISSIONS = [
        ['label' => 'Lire le catalogue intégral', 'granted' => true],
        ['label' => 'Déposer notes & commentaires', 'granted' => true],
        ['label' => 'Suggérer de nouvelles fiches', 'granted' => true],
        ['label' => 'Valider les suggestions des autres', 'granted' => true],
        ['label' => 'Corriger les fiches existantes', 'granted' => true],
        ['label' => 'Signaler du contenu', 'granted' => true],
        ['label' => 'Bannir des aventuriers', 'granted' => true],
        ['label' => "Modifier les rôles d'autrui", 'granted' => true],
        ['label' => "Paramètres d'administration", 'granted' => true],
    ];

    private const LIST_FLAG_MAP = [
        'collection' => 'isOwned',
        'to_read'    => 'isToRead',
        'to_buy'     => 'isToBuy',
        'favorites'  => 'isFavorite',
    ];

    #[Route('/profil', name: 'profile_dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function dashboard(
        Request $request,
        ProfileKpiService $kpiService,
        UserListVisibilityRepository $listVisibilityRepository,
        UserContributorSubscriptionRepository $contributorSubRepository,
        UserCollectionSubscriptionRepository $collectionSubRepository,
        ContributorLevelService $contributorLevelService,
        UserBookRepository $userBookRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $kpiBook       = $kpiService->getBookStats($user);
        $kpiRating     = $kpiService->getRatingStats($user);
        $kpiSuggestion = $kpiService->getSuggestionStats($user);
        $kpiStreak     = $kpiService->getStreakStats($user);

        $listVisibilities = $listVisibilityRepository->findAllByUser($user);

        $tab  = $request->query->get('tab', 'collection');
        $page = max(1, (int) $request->query->get('page', 1));
        $sort = $request->query->get('sort', 'recently_added');

        if (!isset(self::LIST_FLAG_MAP[$tab])) {
            $tab = 'collection';
        }
        $listFlag   = self::LIST_FLAG_MAP[$tab];
        $perPage    = 20;

        $tabBooks   = $userBookRepository->findPaginatedByUserAndList($user, $listFlag, $page, $perPage, $sort);
        $tabTotal   = $userBookRepository->countByUserAndList($user, $listFlag);
        $totalPages = (int) ceil($tabTotal / $perPage);

        $followedContributors = $contributorSubRepository->findFollowedByUser($user);
        $followedCollections  = $collectionSubRepository->findFollowedByUser($user);

        $permissions  = null;
        $rankLevel    = null;
        $deltaToNext  = null;
        $isRoleUser   = $this->isGranted('ROLE_ADMIN') === false && $this->isGranted('ROLE_MODERATOR') === false;

        if ($this->isGranted('ROLE_ADMIN')) {
            $permissions = self::ADMIN_PERMISSIONS;
        } elseif ($this->isGranted('ROLE_MODERATOR')) {
            $permissions = self::MODERATOR_PERMISSIONS;
        } else {
            $rankLevel   = $contributorLevelService->computeRank($user);
            $deltaToNext = $contributorLevelService->getDeltaToNextRank($user);
        }

        return $this->render('profile/dashboard.html.twig', [
            'kpiBook'              => $kpiBook,
            'kpiRating'            => $kpiRating,
            'kpiSuggestion'        => $kpiSuggestion,
            'kpiStreak'            => $kpiStreak,
            'listVisibilities'     => $listVisibilities,
            'activeTab'            => $tab,
            'page'                 => $page,
            'sort'                 => $sort,
            'perPage'              => $perPage,
            'tabBooks'             => $tabBooks,
            'tabTotal'             => $tabTotal,
            'totalPages'           => $totalPages,
            'followedContributors' => $followedContributors,
            'followedCollections'  => $followedCollections,
            'permissions'          => $permissions,
            'rankLevel'            => $rankLevel,
            'deltaToNext'          => $deltaToNext,
            'isRoleUser'           => $isRoleUser,
        ]);
    }

    #[Route('/profil/delete-account', name: 'profile_delete_account', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteAccount(
        Request $request,
        AccountDeletionService $deletionService,
        TokenStorageInterface $tokenStorage,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_account', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ($request->request->get('confirmation') !== 'SUPPRIMER') {
            $this->addFlash('error', 'Vous devez saisir exactement "SUPPRIMER" pour confirmer.');
            return $this->redirectToRoute('profile_dashboard');
        }

        /** @var User $user */
        $user = $this->getUser();

        $deletionService->delete($user);

        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        $this->addFlash('success', 'Votre compte a été supprimé.');
        return $this->redirectToRoute('home');
    }

    #[Route('/profil/list/{listType}/visibility', name: 'profile_list_visibility', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleListVisibility(
        string $listType,
        Request $request,
        UserListVisibilityRepository $listVisibilityRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('list_visibility_' . $listType, $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $listTypeEnum = UserListType::tryFrom($listType);
        if ($listTypeEnum === null) {
            return new JsonResponse(['error' => 'Invalid list type'], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $visibility = $listVisibilityRepository->findByUserAndType($user, $listTypeEnum);
            if ($visibility === null) {
                $visibility = new \App\Entity\UserListVisibility($user, $listTypeEnum, true);
                $em->persist($visibility);
            } else {
                $visibility->setIsPublic(!$visibility->isPublic());
            }
            $em->flush();

            return new JsonResponse(['isPublic' => $visibility->isPublic()]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Erreur serveur'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/profil/unfollow/contributor/{id}', name: 'profile_unfollow_contributor', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function unfollowContributor(
        string $id,
        Request $request,
        UserContributorSubscriptionRepository $repository,
        EntityManagerInterface $em,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('unfollow_contributor_' . $id, $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        /** @var User $user */
        $user = $this->getUser();

        $contributor = $em->find(\App\Entity\Contributor::class, $id);
        if ($contributor === null) {
            return new JsonResponse(['success' => true]);
        }

        $subscription = $repository->findByUserAndContributor($user, $contributor);
        if ($subscription !== null) {
            $em->remove($subscription);
            $em->flush();
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/profil/unfollow/collection/{id}', name: 'profile_unfollow_collection', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function unfollowCollection(
        string $id,
        Request $request,
        UserCollectionSubscriptionRepository $repository,
        EntityManagerInterface $em,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('unfollow_collection_' . $id, $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        /** @var User $user */
        $user = $this->getUser();

        $collection = $em->find(\App\Entity\Collection::class, $id);
        if ($collection === null) {
            return new JsonResponse(['success' => true]);
        }

        $subscription = $repository->findByUserAndCollection($user, $collection);
        if ($subscription !== null) {
            $em->remove($subscription);
            $em->flush();
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/profil/settings/pseudo', name: 'profile_update_pseudo', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function updatePseudo(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('update_pseudo', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var User $user */
        $user  = $this->getUser();
        $pseudo = trim((string) $request->request->get('pseudo', ''));

        if ($pseudo === '') {
            $this->addFlash('error', 'Le pseudonyme ne peut pas être vide.');
            return $this->redirectToRoute('profile_dashboard');
        }

        $existing = $userRepository->findOneBy(['pseudo' => $pseudo]);
        if ($existing !== null && (string) $existing->getId() !== (string) $user->getId()) {
            $this->addFlash('error', 'Ce pseudonyme est déjà utilisé.');
            return $this->redirectToRoute('profile_dashboard');
        }

        $user->setPseudo($pseudo);
        $user->setDisplayName($pseudo);
        $em->flush();

        $this->addFlash('success', 'Pseudonyme mis à jour.');
        return $this->redirectToRoute('profile_dashboard');
    }

    #[Route('/profil/settings/email', name: 'profile_request_email_change', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function requestEmailChange(
        Request $request,
        EmailChangeService $emailChangeService,
    ): Response {
        if (!$this->isCsrfTokenValid('email_change', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var User $user */
        $user     = $this->getUser();
        $newEmail = trim((string) $request->request->get('new_email', ''));

        if ($newEmail === '') {
            $this->addFlash('error', 'L\'adresse email ne peut pas être vide.');
            return $this->redirectToRoute('profile_dashboard');
        }

        try {
            $emailChangeService->requestChange($user, $newEmail);
            $this->addFlash('success', sprintf('Un lien de confirmation a été envoyé à %s.', $newEmail));
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Impossible d\'envoyer l\'email de confirmation. Veuillez réessayer.');
        }

        return $this->redirectToRoute('profile_dashboard');
    }

    #[Route('/profil/email/confirm/{token}', name: 'profile_confirm_email', methods: ['GET'])]
    public function confirmEmail(
        string $token,
        EmailChangeService $emailChangeService,
        TokenStorageInterface $tokenStorage,
        Request $request,
    ): Response {
        try {
            $emailChangeService->confirmChange($token);
            $tokenStorage->setToken(null);
            $request->getSession()->invalidate();
            $this->addFlash('success', 'Votre adresse email a été mise à jour. Veuillez vous reconnecter.');
            return $this->redirectToRoute('app_login');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Lien invalide ou expiré. Veuillez faire une nouvelle demande.');
            return $this->redirectToRoute('profile_dashboard');
        }
    }

    #[Route('/profil/settings/avatar', name: 'profile_update_avatar', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function updateAvatar(
        Request $request,
        EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('update_avatar', $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        /** @var User $user */
        $user = $this->getUser();

        /** @var UploadedFile|null $file */
        $file = $request->files->get('avatar');
        if ($file === null) {
            return new JsonResponse(['error' => 'Aucun fichier reçu.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($file->getSize() > 2 * 1024 * 1024) {
            return new JsonResponse(['error' => 'Le fichier dépasse 2 Mo.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $mime = $file->getMimeType();
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return new JsonResponse(['error' => 'Format non supporté. Utilisez JPEG, PNG ou WebP.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ext       = match ($mime) { 'image/png' => 'png', 'image/webp' => 'webp', default => 'jpg' };
        $filename  = (string) $user->getId() . '.' . $ext;
        $uploadDir = $projectDir . '/public/uploads/avatars';

        $oldAvatarUrl = $user->getAvatarUrl();
        if ($oldAvatarUrl !== null && str_starts_with($oldAvatarUrl, '/uploads/avatars/')) {
            $oldPath = $projectDir . '/public' . $oldAvatarUrl;
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        $file->move($uploadDir, $filename);

        $avatarUrl = '/uploads/avatars/' . $filename;
        $user->setAvatarUrl($avatarUrl);
        $em->flush();

        return new JsonResponse(['avatarUrl' => $avatarUrl]);
    }

    #[Route('/profil/settings/region', name: 'profile_update_region', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function updateRegion(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('update_region', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var User $user */
        $user   = $this->getUser();
        $region = trim((string) $request->request->get('region', ''));

        $user->setRegion($region !== '' ? $region : null);
        $em->flush();

        $this->addFlash('success', 'Région mise à jour.');
        return $this->redirectToRoute('profile_dashboard');
    }

    #[Route('/profil/settings/unlink-google', name: 'profile_unlink_google', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function unlinkGoogle(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('unlink_google', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPassword() === null) {
            $this->addFlash('error', 'Définissez un mot de passe avant de délier votre compte Google.');
            return $this->redirectToRoute('profile_dashboard');
        }

        $user->setGoogleId(null);
        $em->flush();

        $this->addFlash('success', 'Compte Google délié.');
        return $this->redirectToRoute('profile_dashboard');
    }

    #[Route('/profil/settings/password', name: 'profile_update_password', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function updatePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        if (!$this->isCsrfTokenValid('update_password', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPassword() === null) {
            $this->addFlash('error', 'Impossible de changer le mot de passe : aucun mot de passe défini.');
            return $this->redirectToRoute('profile_dashboard', [], Response::HTTP_SEE_OTHER);
        }

        $currentPassword = (string) $request->request->get('current_password', '');
        $newPassword     = (string) $request->request->get('new_password', '');

        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Mot de passe actuel incorrect.');
            return $this->redirectToRoute('profile_dashboard');
        }

        if (strlen($newPassword) < 8) {
            $this->addFlash('error', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
            return $this->redirectToRoute('profile_dashboard');
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $em->flush();

        $this->addFlash('success', 'Mot de passe mis à jour.');
        return $this->redirectToRoute('profile_dashboard');
    }

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

    #[Route('/profil/{pseudo}', name: 'profile_public', methods: ['GET'])]
    public function publicProfile(
        string $pseudo,
        UserRepository $userRepository,
        ContributorLevelService $contributorLevelService,
        UserListVisibilityRepository $listVisibilityRepository,
    ): Response {
        $profileUser = $userRepository->findOneByPseudo($pseudo);
        if ($profileUser === null) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $isRoleUser = in_array('ROLE_USER', $profileUser->getRoles(), true)
            && !in_array('ROLE_MODERATOR', $profileUser->getRoles(), true)
            && !in_array('ROLE_ADMIN', $profileUser->getRoles(), true);

        $rankLevel    = null;
        $validatedCount = 0;
        if ($isRoleUser) {
            $rankLevel      = $contributorLevelService->computeRank($profileUser);
            $metrics        = $contributorLevelService->getMetrics($profileUser);
            $validatedCount = $metrics['validatedCount'];
        }

        $listVisibilities = $listVisibilityRepository->findAllByUser($profileUser);

        return $this->render('profile/show.html.twig', [
            'profileUser'      => $profileUser,
            'rankLevel'        => $rankLevel,
            'validatedCount'   => $validatedCount,
            'isRankVisible'    => $isRoleUser,
            'listVisibilities' => $listVisibilities,
        ]);
    }
}
