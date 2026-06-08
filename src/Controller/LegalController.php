<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LegalController extends AbstractController
{
    public function __construct(private readonly string $legalLastUpdated)
    {
    }

    #[Route('/mentions-legales', name: 'app_mentions_legales', methods: ['GET'])]
    public function mentionsLegales(): Response
    {
        return $this->render('legal/mentions-legales.html.twig', ['lastUpdated' => $this->legalLastUpdated]);
    }
}
