<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserManagementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    private const VALID_ROLES = ['ROLE_USER', 'ROLE_MODERATOR', 'ROLE_ADMIN'];

    #[Route('/users', name: 'admin_users', methods: ['GET'])]
    public function users(UserRepository $repo): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $repo->findAllNonDeleted(),
        ]);
    }

    #[Route('/users/{id}/role', name: 'admin_change_role', methods: ['POST'])]
    public function changeRole(string $id, Request $request, UserRepository $repo, UserManagementService $service): Response
    {
        $target = $repo->find($id);
        if ($target === null) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('admin_users');
        }

        if (!$this->isCsrfTokenValid('admin_user_'.$id, $request->request->get('_csrf_token'))) {
            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        $role = (string) $request->request->get('role');
        if (!in_array($role, self::VALID_ROLES, true)) {
            $this->addFlash('error', 'Rôle invalide.');
            return $this->redirectToRoute('admin_users');
        }

        try {
            /** @var User $actor */
            $actor = $this->getUser();
            $service->changeRole($actor, $target, $role);
            $this->addFlash('success', 'Rôle mis à jour.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/ban', name: 'admin_ban_user', methods: ['POST'])]
    public function ban(string $id, Request $request, UserRepository $repo, UserManagementService $service): Response
    {
        $target = $repo->find($id);
        if ($target === null) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('admin_users');
        }

        if (!$this->isCsrfTokenValid('admin_user_'.$id, $request->request->get('_csrf_token'))) {
            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        try {
            /** @var User $actor */
            $actor = $this->getUser();
            $service->banUser($actor, $target);
            $this->addFlash('success', 'Compte suspendu.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/delete', name: 'admin_delete_user', methods: ['POST'])]
    public function delete(string $id, Request $request, UserRepository $repo, UserManagementService $service): Response
    {
        $target = $repo->find($id);
        if ($target === null) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('admin_users');
        }

        if (!$this->isCsrfTokenValid('admin_user_'.$id, $request->request->get('_csrf_token'))) {
            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        try {
            /** @var User $actor */
            $actor = $this->getUser();
            $service->softDeleteUser($actor, $target);
            $this->addFlash('success', 'Compte supprimé.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/settings', name: 'admin_settings', methods: ['GET'])]
    public function settings(): JsonResponse
    {
        return new JsonResponse(['message' => 'Settings UI coming soon']);
    }
}
