<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\ContributorFilterState;
use App\Repository\UserFollowedContributorRepository;
use App\Service\ContributeurService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreateursController extends AbstractController
{
    public function __construct(
        private readonly ContributeurService $service,
        private readonly UserFollowedContributorRepository $followRepository,
    ) {}

    #[Route('/createurs', name: 'app_createurs')]
    public function index(Request $request): Response
    {
        $state     = ContributorFilterState::fromRequest($request);
        $user      = $this->getUser();
        $paginator = $this->service->getPaginatedResults($state, $user);

        $totalItems = count($paginator);
        $perPage    = 12;
        $totalPages = max(1, (int) ceil($totalItems / $perPage));

        if ($state->page > $totalPages) {
            $redirectState = new ContributorFilterState(
                role: $state->role,
                letter: $state->letter,
                collectionIds: $state->collectionIds,
                periodMin: $state->periodMin,
                periodMax: $state->periodMax,
                nationality: $state->nationality,
                bookCountRange: $state->bookCountRange,
                onlyFollowed: $state->onlyFollowed,
                sort: $state->sort,
                page: $totalPages,
            );
            return $this->redirect($this->generateUrl('app_createurs', $redirectState->toUrlParams()));
        }

        $contributors = iterator_to_array($paginator->getIterator());
        $ids = array_values(array_map(
            static fn($c) => (string) $c->getId(),
            $contributors
        ));

        $cardData        = $this->service->getCardDataBatch($ids);
        $availableLetters = $this->service->getAvailableLetters($state, $user);
        $roleCounts      = $this->service->getRoleCounts();

        $followedContributorIds = $user !== null
            ? $this->followRepository->findFollowedContributorIds($user)
            : [];

        return $this->render('createurs/index.html.twig', [
            'filterState'           => $state,
            'contributors'          => $contributors,
            'cardData'              => $cardData,
            'availableLetters'      => $availableLetters,
            'roleCounts'            => $roleCounts,
            'totalItems'            => $totalItems,
            'totalPages'            => $totalPages,
            'followedContributorIds' => $followedContributorIds,
            'isAuthenticated'       => $user !== null,
        ]);
    }

    #[Route('/createurs/search', name: 'app_createurs_search')]
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));

        if ($q === '') {
            return $this->json([]);
        }

        return $this->json($this->service->getAutocompleteResults($q));
    }
}
