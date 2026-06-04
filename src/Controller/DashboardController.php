<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\DashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly string $forumUrl,
    ) {}

    #[Route('/', name: 'home')]
    public function home(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $dashboardData = $this->dashboardService->buildDashboardData($user);

        return $this->render('home/index.html.twig', [
            'dashboardData' => $dashboardData,
            'forumUrl' => $this->forumUrl,
        ]);
    }
}
