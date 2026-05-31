<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\GlobalSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SearchController extends AbstractController
{
    public function __construct(private readonly GlobalSearchService $searchService) {}

    #[Route('/api/search', name: 'api_search', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));

        if (mb_strlen($q) > 100) {
            return new JsonResponse(['error' => 'Query too long'], 400);
        }

        try {
            $items = $this->searchService->query($q);

            return new JsonResponse([
                'results' => array_map($this->serializeItem(...), $items),
            ]);
        } catch (\Throwable) {
            return new JsonResponse(['results' => []]);
        }
    }

    #[Route('/api/search/popular', name: 'api_search_popular', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function popular(): JsonResponse
    {
        try {
            $items = $this->searchService->findPopular();

            return new JsonResponse([
                'popular' => array_map($this->serializeItem(...), $items),
            ]);
        } catch (\Throwable) {
            return new JsonResponse(['popular' => []]);
        }
    }

    private function serializeItem(\App\Dto\Search\SearchResultItem $item): array
    {
        return [
            'type'         => $item->type,
            'slug'         => $item->slug,
            'title'        => $item->title,
            'subtitle'     => $item->subtitle,
            'thumbnailUrl' => $item->thumbnailUrl,
            'initials'     => $item->initials,
            'avatarColor'  => $item->avatarColor,
        ];
    }
}
