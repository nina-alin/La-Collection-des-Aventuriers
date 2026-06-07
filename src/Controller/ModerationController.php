<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CorrectionProposalRepository;
use App\Repository\WorkEntryRepository;
use App\Service\ContributorLevelService;
use App\Service\ModerationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/moderation')]
#[IsGranted('ROLE_MODERATOR')]
class ModerationController extends AbstractController
{
    #[Route('', name: 'moderation_dashboard', methods: ['GET'])]
    public function index(
        WorkEntryRepository $workEntryRepo,
        CorrectionProposalRepository $correctionRepo,
        ContributorLevelService $contributorLevelService,
    ): Response {
        $pendingEntries = $workEntryRepo->findPending();
        $pendingProposals = $correctionRepo->findPending();

        $authors = [];
        foreach ($pendingEntries as $entry) {
            if ($entry->getAuthor() !== null) {
                $authors[] = $entry->getAuthor();
            }
        }
        foreach ($pendingProposals as $proposal) {
            if ($proposal->getAuthor() !== null) {
                $authors[] = $proposal->getAuthor();
            }
        }

        $ranksByUserId = $contributorLevelService->computeRankBatch($authors);

        return $this->render('moderation/dashboard.html.twig', [
            'pendingEntries' => $pendingEntries,
            'pendingProposals' => $pendingProposals,
            'ranksByUserId' => $ranksByUserId,
        ]);
    }

    #[Route('/work-entry/{id}/approve', name: 'moderation_approve_work_entry', methods: ['POST'])]
    public function approveWorkEntry(string $id, Request $request, WorkEntryRepository $repo, ModerationService $service): Response
    {
        $entry = $repo->find($id);
        if ($entry === null) {
            $this->addFlash('error', 'Entrée introuvable.');
            return $this->redirectToRoute('moderation_dashboard');
        }

        if (!$this->isCsrfTokenValid('moderate_'.$id, $request->request->get('_csrf_token'))) {
            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        try {
            $service->approve($entry, (string) $this->getUser()->getUserIdentifier());
            $this->addFlash('success', 'Entrée approuvée.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Cette entrée ne peut pas être approuvée dans son état actuel.');
        }

        return $this->redirectToRoute('moderation_dashboard');
    }

    #[Route('/work-entry/{id}/reject', name: 'moderation_reject_work_entry', methods: ['POST'])]
    public function rejectWorkEntry(string $id, Request $request, WorkEntryRepository $repo, ModerationService $service): Response
    {
        $entry = $repo->find($id);
        if ($entry === null) {
            $this->addFlash('error', 'Entrée introuvable.');
            return $this->redirectToRoute('moderation_dashboard');
        }

        if (!$this->isCsrfTokenValid('moderate_'.$id, $request->request->get('_csrf_token'))) {
            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        try {
            $service->reject($entry, (string) $this->getUser()->getUserIdentifier(), $request->request->get('reason'));
            $this->addFlash('success', 'Entrée rejetée.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Cette entrée ne peut pas être rejetée dans son état actuel.');
        }

        return $this->redirectToRoute('moderation_dashboard');
    }

    #[Route('/work-entry/{id}/edit', name: 'moderation_edit_work_entry', methods: ['POST'])]
    public function editWorkEntry(string $id, Request $request, WorkEntryRepository $repo, ModerationService $service): Response
    {
        $entry = $repo->find($id);
        if ($entry === null) {
            $this->addFlash('error', 'Entrée introuvable.');
            return $this->redirectToRoute('moderation_dashboard');
        }

        if (!$this->isCsrfTokenValid('moderate_'.$id, $request->request->get('_csrf_token'))) {
            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        try {
            $service->editPendingWorkEntry($entry, (string) $request->request->get('title'), (string) $this->getUser()->getUserIdentifier());
            $this->addFlash('success', 'Entrée modifiée.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Cette entrée ne peut pas être modifiée dans son état actuel.');
        }

        return $this->redirectToRoute('moderation_dashboard');
    }

    #[Route('/correction-proposal/{id}/approve', name: 'moderation_approve_correction', methods: ['POST'])]
    public function approveCorrectionProposal(string $id, Request $request, CorrectionProposalRepository $repo, ModerationService $service): Response
    {
        $proposal = $repo->find($id);
        if ($proposal === null) {
            $this->addFlash('error', 'Correction introuvable.');
            return $this->redirectToRoute('moderation_dashboard');
        }

        if (!$this->isCsrfTokenValid('moderate_'.$id, $request->request->get('_csrf_token'))) {
            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        try {
            $service->approve($proposal, (string) $this->getUser()->getUserIdentifier());
            $this->addFlash('success', 'Correction approuvée.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Cette correction ne peut pas être approuvée dans son état actuel.');
        }

        return $this->redirectToRoute('moderation_dashboard');
    }

    #[Route('/correction-proposal/{id}/reject', name: 'moderation_reject_correction', methods: ['POST'])]
    public function rejectCorrectionProposal(string $id, Request $request, CorrectionProposalRepository $repo, ModerationService $service): Response
    {
        $proposal = $repo->find($id);
        if ($proposal === null) {
            $this->addFlash('error', 'Correction introuvable.');
            return $this->redirectToRoute('moderation_dashboard');
        }

        if (!$this->isCsrfTokenValid('moderate_'.$id, $request->request->get('_csrf_token'))) {
            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        try {
            $service->reject($proposal, (string) $this->getUser()->getUserIdentifier(), $request->request->get('reason'));
            $this->addFlash('success', 'Correction rejetée.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Cette correction ne peut pas être rejetée dans son état actuel.');
        }

        return $this->redirectToRoute('moderation_dashboard');
    }

    #[Route('/correction-proposal/{id}/edit', name: 'moderation_edit_correction', methods: ['POST'])]
    public function editCorrectionProposal(string $id, Request $request, CorrectionProposalRepository $repo, ModerationService $service): Response
    {
        $proposal = $repo->find($id);
        if ($proposal === null) {
            $this->addFlash('error', 'Correction introuvable.');
            return $this->redirectToRoute('moderation_dashboard');
        }

        if (!$this->isCsrfTokenValid('moderate_'.$id, $request->request->get('_csrf_token'))) {
            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        try {
            $service->editPendingCorrection($proposal, (string) $request->request->get('proposedContent'), (string) $this->getUser()->getUserIdentifier());
            $this->addFlash('success', 'Correction modifiée.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Cette correction ne peut pas être modifiée dans son état actuel.');
        }

        return $this->redirectToRoute('moderation_dashboard');
    }
}
