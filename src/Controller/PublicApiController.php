<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\LandingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class PublicApiController extends AbstractController
{
    public function __construct(
        private readonly LandingService $landingService,
    ) {}

    #[Route('/api/public/stats', name: 'api_public_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        try {
            $dto = $this->landingService->getStats();

            return new JsonResponse([
                'total_books' => $dto->totalBooks,
                'total_users' => $dto->totalUsers,
                'new_this_week' => $dto->newThisWeek,
                'total_contributors' => $dto->totalContributors,
            ]);
        } catch (\Throwable) {
            return new JsonResponse(['error' => 'unavailable'], 503);
        }
    }

    #[Route('/api/public/marquee', name: 'api_public_marquee', methods: ['GET'])]
    public function marquee(): JsonResponse
    {
        try {
            $items = $this->landingService->getMarqueeItems();

            if ($items === []) {
                return new JsonResponse([]);
            }

            $data = array_map(static fn ($item) => [
                'name' => $item->name,
                'type' => $item->type,
                'url' => $item->url,
                'subtitle' => $item->subtitle,
                'initials' => $item->initials,
                'color_class' => $item->colorClass,
            ], $items);

            return new JsonResponse($data);
        } catch (\Throwable) {
            return new JsonResponse(['error' => 'unavailable'], 503);
        }
    }
}
