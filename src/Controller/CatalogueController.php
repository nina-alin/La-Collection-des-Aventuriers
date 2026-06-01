<?php

namespace App\Controller;

use App\Dto\ActiveFilterState;
use App\Entity\Book;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\ContributorRepository;
use App\Repository\EditorRepository;
use App\Service\CatalogueService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CatalogueController extends AbstractController
{
    public function __construct(
        private readonly CatalogueService      $catalogueService,
        private readonly BookRepository        $bookRepository,
        private readonly ContributorRepository $contributorRepository,
        private readonly EditorRepository      $editorRepository,
    ) {}

    #[Route('/catalogue', name: 'app_catalogue')]
    public function index(Request $request): Response
    {
        $state = ActiveFilterState::fromRequest($request);

        try {
            $paragraphBounds = $this->catalogueService->getParagraphBounds();
        } catch (\Throwable) {
            $paragraphBounds = ['min' => 0, 'max' => 999];
        }

        /** @var User|null $user */
        $user = $this->getUser();
        $paginator = $this->catalogueService->getFilteredResults($state, $user);

        $totalItems  = count($paginator);
        $perPage     = 24;
        $totalPages  = (int) ceil($totalItems / $perPage);
        $totalPages  = max(1, $totalPages);

        if ($state->page > $totalPages) {
            $redirectState = new ActiveFilterState(
                sort: $state->sort,
                editors: $state->editors,
                contributors: $state->contributors,
                paragraphMin: $state->paragraphMin,
                paragraphMax: $state->paragraphMax,
                collectionStatus: $state->collectionStatus,
                onlyFavorites: $state->onlyFavorites,
                hideModeration: $state->hideModeration,
                searchQuery: $state->searchQuery,
                page: $totalPages,
            );
            return $this->redirect($this->generateUrl('app_catalogue', $redirectState->toUrlParams()));
        }

        $books   = iterator_to_array($paginator->getIterator());
        $bookIds = array_values(array_filter(array_map(fn($b) => $b->getId(), $books)));

        $userBooksMap      = $this->catalogueService->getUserBooksForPage($user, $bookIds);
        $ratingsMap        = $this->bookRepository->findAverageRatingsByIds($bookIds);
        $authorsMap        = [];
        $collectionRefsMap = [];
        foreach ($books as $book) {
            $id = $book->getId();
            $authorsMap[$id]        = $this->getAuthorName($book);
            $collectionRefsMap[$id] = $this->buildCollectionRef($book);
        }

        $editorsMap = [];
        if (!empty($state->editors)) {
            foreach ($this->editorRepository->findBy(['id' => $state->editors]) as $editor) {
                $editorsMap[$editor->getId()] = $editor->getName();
            }
        }

        $contributorsMap = [];
        if (!empty($state->contributors)) {
            $uuids = [];
            foreach ($state->contributors as $id) {
                try { $uuids[] = \Symfony\Component\Uid\Uuid::fromString($id); } catch (\Throwable) {}
            }
            foreach ($this->contributorRepository->findBy(['id' => $uuids]) as $contributor) {
                $contributorsMap[(string) $contributor->getId()] = trim($contributor->getFirstName() . ' ' . $contributor->getLastName());
            }
        }

        return $this->render('catalogue/index.html.twig', [
            'activeFilterState' => $state,
            'books'             => $books,
            'userBooksMap'      => $userBooksMap,
            'ratingsMap'        => $ratingsMap,
            'authorsMap'        => $authorsMap,
            'collectionRefsMap' => $collectionRefsMap,
            'paragraphBounds'   => $paragraphBounds,
            'totalItems'        => $totalItems,
            'totalPages'        => $totalPages,
            'currentPage'       => $state->page,
            'editorsMap'        => $editorsMap,
            'contributorsMap'   => $contributorsMap,
        ]);
    }

    #[Route('/catalogue/search-suggestions', name: 'app_catalogue_search_suggestions')]
    public function searchSuggestions(Request $request): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));

        if (strlen($q) < 1) {
            return $this->json(['books' => [], 'authors' => []]);
        }

        $bookEntities = $this->bookRepository->findForGlobalSearch($q, 5);
        $bookIds      = array_filter(array_map(fn($b) => $b->getId(), $bookEntities));
        $ratings      = $this->bookRepository->findAverageRatingsByIds(array_values($bookIds));
        $books = array_map(fn($b) => [
            'id'              => $b->getId(),
            'slug'            => $b->getSlug(),
            'title'           => $b->getTitle(),
            'author'          => $this->getAuthorName($b),
            'year'            => $b->getFrenchPublicationYear(),
            'collectionLabel' => $this->buildSuggestionLabel($b),
            'rating'          => isset($ratings[$b->getId()]) ? round((float) $ratings[$b->getId()], 1) : null,
        ], $bookEntities);

        $authorEntities = $this->contributorRepository->findForGlobalSearch($q, 5);
        $authors = array_map(fn($c) => [
            'id'   => (string) $c->getId(),
            'slug' => $c->getSlug(),
            'name' => trim($c->getFirstName() . ' ' . $c->getLastName()),
        ], $authorEntities);

        return $this->json([
            'books'   => $books,
            'authors' => $authors,
        ]);
    }

    private function getAuthorName(Book $book): string
    {
        $authors = [];
        foreach ($book->getContributions() as $contribution) {
            if ($contribution->getRole()->value === 'Author') {
                $c = $contribution->getContributor();
                $authors[] = trim($c->getFirstName() . ' ' . $c->getLastName());
            }
        }
        if (!empty($authors)) {
            return implode(' & ', $authors);
        }
        foreach ($book->getContributions() as $contribution) {
            $c = $contribution->getContributor();
            return trim($c->getFirstName() . ' ' . $c->getLastName());
        }
        return '';
    }

    private function buildSuggestionLabel(Book $book): ?string
    {
        $coll = $book->getCollection();
        if ($coll === null) {
            return null;
        }
        $vol = $book->getVolumeNumber();
        return $coll->getNom() . ($vol !== null ? " nº{$vol}" : '');
    }

    private function buildCollectionRef(Book $book): string
    {
        $lca = sprintf('LCA-%04d', $book->getId());
        $coll = $book->getCollection();
        if ($coll === null) {
            return $lca;
        }

        $words = preg_split('/[-\s]+/', $coll->getSlug()) ?: [];
        $abbrev = implode('', array_map(
            static fn(string $w) => strtoupper($w[0] ?? ''),
            $words
        ));

        $vol = $book->getVolumeNumber();
        $volStr = $vol !== null ? " nº{$vol}" : '';

        return "{$lca} · {$abbrev}{$volStr}";
    }
}
