<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CollectionListFilterState;
use App\Entity\User;
use App\Repository\CollectionRepository;
use App\Repository\UserCollectionSubscriptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CollectionListController extends AbstractController
{
    #[Route('/collections', name: 'app_collections', methods: ['GET'])]
    public function index(
        Request $request,
        CollectionRepository $collectionRepository,
        UserCollectionSubscriptionRepository $subscriptionRepository,
    ): Response {
        $state = CollectionListFilterState::fromRequest($request);

        /** @var User|null $user */
        $user = $this->getUser();

        $paginator   = $collectionRepository->findPaginatedFiltered($state, $user);
        $totalItems  = count($paginator);
        $perPage     = 12;
        $totalPages  = max(1, (int) ceil($totalItems / $perPage));

        if ($state->page > $totalPages) {
            $redirectState = new CollectionListFilterState(
                followed: $state->followed,
                genre: $state->genre,
                statut: $state->statut,
                page: $totalPages,
            );
            return $this->redirect($this->generateUrl('app_collections', $redirectState->toUrlParams()));
        }

        $followedCollectionIds = $user !== null
            ? $subscriptionRepository->findFollowedCollectionIds($user)
            : [];

        return $this->render('collections/index.html.twig', [
            'filterState'          => $state,
            'collections'          => iterator_to_array($paginator->getIterator()),
            'totalItems'           => $totalItems,
            'totalPages'           => $totalPages,
            'isAuthenticated'      => $user !== null,
            'followedCollectionIds' => $followedCollectionIds,
        ]);
    }
}
