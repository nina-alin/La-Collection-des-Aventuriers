<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Enum\SuggestionEntityType;
use App\Entity\Enum\SuggestionStatus;
use App\Repository\BookRepository;
use App\Repository\CollectionRepository;
use App\Repository\ContributorRepository;
use App\Repository\CorrectionProposalRepository;
use App\Repository\EditorRepository;
use App\Repository\SuggestionRepository;
use App\Repository\WorkEntryRepository;
use App\Service\ContributorLevelService;
use App\Service\DiffService;
use App\Service\ModerationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        SuggestionRepository $suggestionRepo,
        ContributorLevelService $contributorLevelService,
        DiffService $diffService,
        BookRepository $bookRepo,
        ContributorRepository $contributorRepo,
        EditorRepository $editorRepo,
        CollectionRepository $collectionRepo,
    ): Response {
        $pendingEntries = $workEntryRepo->findPending();
        $pendingProposals = $correctionRepo->findPending();
        $pendingSuggestions = $suggestionRepo->findPending();

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
        foreach ($pendingSuggestions as $suggestion) {
            $authors[] = $suggestion->getUser();
        }

        $ranksByUserId = $contributorLevelService->computeRankBatch($authors);

        $firstSuggestion = $suggestionRepo->findFirstPending();
        $diffResult = null;

        if ($firstSuggestion !== null) {
            $sourceEntity = $this->loadSourceEntity($firstSuggestion->getEntityType(), $firstSuggestion->getSourceEntityId()?->toRfc4122(), $bookRepo, $contributorRepo, $editorRepo, $collectionRepo);
            $diffResult = $diffService->computeForSuggestion($firstSuggestion, $sourceEntity);
        }

        $pendingCount = $suggestionRepo->countGlobalPending();

        return $this->render('moderation/dashboard.html.twig', [
            'pendingEntries' => $pendingEntries,
            'pendingProposals' => $pendingProposals,
            'pendingSuggestions' => $pendingSuggestions,
            'ranksByUserId' => $ranksByUserId,
            'suggestion' => $firstSuggestion,
            'diffResult' => $diffResult,
            'pendingCount' => $pendingCount,
            'currentDate' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/suggestion/{id}/diff-partial', name: 'moderation_diff_partial', methods: ['GET'])]
    public function diffPartial(
        string $id,
        SuggestionRepository $suggestionRepo,
        DiffService $diffService,
        BookRepository $bookRepo,
        ContributorRepository $contributorRepo,
        EditorRepository $editorRepo,
        CollectionRepository $collectionRepo,
    ): Response {
        $suggestion = $suggestionRepo->find($id);
        if ($suggestion === null) {
            throw $this->createNotFoundException();
        }

        $sourceEntity = $this->loadSourceEntity($suggestion->getEntityType(), $suggestion->getSourceEntityId()?->toRfc4122(), $bookRepo, $contributorRepo, $editorRepo, $collectionRepo);
        $diffResult = $diffService->computeForSuggestion($suggestion, $sourceEntity);

        return $this->render('moderation/_diff_panel.html.twig', [
            'suggestion' => $suggestion,
            'diffResult' => $diffResult,
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

    #[Route('/suggestion/{id}/approve', name: 'moderation_approve_suggestion', methods: ['POST'])]
    public function approveSuggestion(string $id, Request $request, SuggestionRepository $repo, ModerationService $service): Response
    {
        $suggestion = $repo->find($id);
        if ($suggestion === null) {
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return $this->json(['success' => false, 'message' => 'Suggestion introuvable.'], 404);
            }
            $this->addFlash('error', 'Suggestion introuvable.');
            return $this->redirectToRoute('moderation_dashboard');
        }

        if (!$this->isCsrfTokenValid('moderate_'.$id, $request->request->get('_csrf_token'))) {
            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        /** @var \App\Entity\User $moderator */
        $moderator = $this->getUser();
        $service->moderateSuggestion($moderator, $suggestion, SuggestionStatus::VALIDATED);

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            $next = $repo->findNextPending($suggestion);
            return $this->json(['success' => true, 'nextSuggestionId' => $next?->getId()->toRfc4122()]);
        }

        $this->addFlash('success', 'Suggestion validée.');
        return $this->redirectToRoute('moderation_dashboard');
    }

    #[Route('/suggestion/{id}/refuse', name: 'moderation_refuse_suggestion', methods: ['POST'])]
    public function refuseSuggestion(string $id, Request $request, SuggestionRepository $repo, ModerationService $service): Response
    {
        $suggestion = $repo->find($id);
        if ($suggestion === null) {
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return $this->json(['success' => false, 'message' => 'Suggestion introuvable.'], 404);
            }
            $this->addFlash('error', 'Suggestion introuvable.');
            return $this->redirectToRoute('moderation_dashboard');
        }

        if (!$this->isCsrfTokenValid('moderate_'.$id, $request->request->get('_csrf_token'))) {
            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            $reason = trim((string) $request->request->get('reason', ''));
            if ($reason === '') {
                return $this->json(['success' => false, 'message' => 'Le motif de refus est requis.'], 422);
            }

            /** @var \App\Entity\User $moderator */
            $moderator = $this->getUser();
            $service->moderateSuggestion($moderator, $suggestion, SuggestionStatus::REFUSED, $reason);

            $next = $repo->findNextPending($suggestion);
            return $this->json(['success' => true, 'nextSuggestionId' => $next?->getId()->toRfc4122()]);
        }

        /** @var \App\Entity\User $moderator */
        $moderator = $this->getUser();
        $service->moderateSuggestion($moderator, $suggestion, SuggestionStatus::REFUSED);
        $this->addFlash('success', 'Suggestion refusée.');

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

    #[Route('/entities', name: 'moderation_entities_list', methods: ['GET'])]
    public function entitiesList(
        Request $request,
        BookRepository $bookRepo,
        ContributorRepository $contributorRepo,
        EditorRepository $editorRepo,
        CollectionRepository $collectionRepo,
    ): Response {
        $search = (string) $request->query->get('search', '');
        $type = (string) $request->query->get('type', '');

        $entities = [];

        if ($type === '' || $type === 'BOOK') {
            $books = $search !== ''
                ? $bookRepo->findByTitleLike($search, 30)
                : $bookRepo->createQueryBuilder('b')->setMaxResults(30)->getQuery()->getResult();
            foreach ($books as $book) {
                $entities[] = [
                    'id' => (string) $book->getId(),
                    'name' => $book->getTitle(),
                    'type' => 'BOOK',
                    'status' => $book->getStatus()->value,
                    'updatedAt' => $book->getUpdatedAt() ?? new \DateTimeImmutable(),
                    'refusalReason' => null,
                ];
            }
        }

        if ($type === '' || in_array($type, ['AUTHOR', 'ILLUSTRATOR', 'TRADUCTOR'], true)) {
            $contributors = $contributorRepo->createQueryBuilder('c')
                ->setMaxResults(30)
                ->getQuery()->getResult();
            if ($search !== '') {
                $contributors = $contributorRepo->createQueryBuilder('c')
                    ->where("LOWER(CONCAT(c.firstName, ' ', c.lastName)) LIKE LOWER(:q)")
                    ->setParameter('q', '%' . $search . '%')
                    ->setMaxResults(30)
                    ->getQuery()->getResult();
            }
            foreach ($contributors as $contributor) {
                $entities[] = [
                    'id' => $contributor->getId()->toRfc4122(),
                    'name' => $contributor->getFirstName() . ' ' . $contributor->getLastName(),
                    'type' => 'AUTHOR',
                    'status' => 'published',
                    'updatedAt' => new \DateTimeImmutable(),
                    'refusalReason' => null,
                ];
            }
        }

        if ($type === '' || $type === 'EDITOR') {
            $editors = $search !== ''
                ? $editorRepo->findByNameSearch($search, 30)
                : $editorRepo->createQueryBuilder('e')->setMaxResults(30)->getQuery()->getResult();
            foreach ($editors as $editor) {
                $entities[] = [
                    'id' => (string) $editor->getId(),
                    'name' => $editor->getName(),
                    'type' => 'EDITOR',
                    'status' => 'published',
                    'updatedAt' => new \DateTimeImmutable(),
                    'refusalReason' => null,
                ];
            }
        }

        if ($type === '' || $type === 'COLLECTION') {
            $collections = $collectionRepo->createQueryBuilder('c')
                ->setMaxResults(30);
            if ($search !== '') {
                $collections->where('LOWER(c.nom) LIKE LOWER(:q)')->setParameter('q', '%' . $search . '%');
            }
            $collections = $collections->getQuery()->getResult();
            foreach ($collections as $collection) {
                $entities[] = [
                    'id' => $collection->getId()->toRfc4122(),
                    'name' => $collection->getNom(),
                    'type' => 'COLLECTION',
                    'status' => $collection->getStatut()->value,
                    'updatedAt' => new \DateTimeImmutable(),
                    'refusalReason' => null,
                ];
            }
        }

        usort($entities, fn ($a, $b) => $b['updatedAt'] <=> $a['updatedAt']);
        $entities = array_slice($entities, 0, 100);

        return $this->render('moderation/_entities_table.html.twig', ['entities' => $entities]);
    }

    #[Route('/entities/{type}/{id}', name: 'moderation_entity_delete', methods: ['DELETE'])]
    public function deleteEntity(
        string $type,
        string $id,
        Request $request,
        BookRepository $bookRepo,
        ContributorRepository $contributorRepo,
        EditorRepository $editorRepo,
        CollectionRepository $collectionRepo,
        \Doctrine\ORM\EntityManagerInterface $em,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('delete_entity_' . $id, $request->headers->get('X-CSRF-Token') ?? $request->request->get('_csrf_token'))) {
            return $this->json(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
        }

        $entity = $this->resolveEntity($type, $id, $bookRepo, $contributorRepo, $editorRepo, $collectionRepo);
        if ($entity === null) {
            return $this->json(['success' => false, 'message' => 'Entité introuvable.'], 404);
        }

        $em->remove($entity);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/entities/{type}/{id}/depublish', name: 'moderation_entity_depublish', methods: ['PATCH'])]
    public function depublishEntity(
        string $type,
        string $id,
        Request $request,
        BookRepository $bookRepo,
        ContributorRepository $contributorRepo,
        EditorRepository $editorRepo,
        CollectionRepository $collectionRepo,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('delete_entity_' . $id, $request->headers->get('X-CSRF-Token') ?? $request->request->get('_csrf_token'))) {
            return $this->json(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
        }

        return $this->json(['success' => false, 'message' => 'Type non dépubliable.'], 422);
    }

    private function resolveEntity(
        string $type,
        string $id,
        BookRepository $bookRepo,
        ContributorRepository $contributorRepo,
        EditorRepository $editorRepo,
        CollectionRepository $collectionRepo,
    ): ?object {
        return match ($type) {
            'BOOK' => $bookRepo->find($id),
            'AUTHOR', 'ILLUSTRATOR', 'TRADUCTOR' => $contributorRepo->find($id),
            'EDITOR' => $editorRepo->find($id),
            'COLLECTION' => $collectionRepo->find($id),
            default => null,
        };
    }

    private function loadSourceEntity(
        SuggestionEntityType $type,
        ?string $sourceEntityId,
        BookRepository $bookRepo,
        ContributorRepository $contributorRepo,
        EditorRepository $editorRepo,
        CollectionRepository $collectionRepo,
    ): ?object {
        if ($sourceEntityId === null) {
            return null;
        }

        return match ($type) {
            SuggestionEntityType::BOOK => $bookRepo->find($sourceEntityId),
            SuggestionEntityType::AUTHOR,
            SuggestionEntityType::ILLUSTRATOR,
            SuggestionEntityType::TRADUCTOR => $contributorRepo->find($sourceEntityId),
            SuggestionEntityType::EDITOR => $editorRepo->find($sourceEntityId),
            SuggestionEntityType::COLLECTION => $collectionRepo->find($sourceEntityId),
        };
    }
}
