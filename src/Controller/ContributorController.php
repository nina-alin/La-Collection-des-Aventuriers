<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Enum\ContributionRole;
use App\Repository\ContributorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContributorController extends AbstractController
{
    #[Route('/authors/{slug}', name: 'app_author_show', methods: ['GET'])]
    public function authorShow(string $slug, ContributorRepository $repository): Response
    {
        $contributor = $repository->findBySlugAndRole($slug, ContributionRole::Author);

        if ($contributor === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('contributeur/author_show.html.twig', [
            'contributor' => $contributor,
            'contributions' => $contributor->getContributions(),
        ]);
    }

    #[Route('/illustrators/{slug}', name: 'app_illustrator_show', methods: ['GET'])]
    public function illustratorShow(string $slug, ContributorRepository $repository): Response
    {
        $contributor = $repository->findBySlugAndRole($slug, ContributionRole::Illustrator);

        if ($contributor === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('contributeur/illustrator_show.html.twig', [
            'contributor' => $contributor,
            'contributions' => $contributor->getContributions(),
        ]);
    }

    #[Route('/traductors/{slug}', name: 'app_traductor_show', methods: ['GET'])]
    public function traductorShow(string $slug, ContributorRepository $repository): Response
    {
        $contributor = $repository->findBySlugAndRole($slug, ContributionRole::Traductor);

        if ($contributor === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('contributeur/traductor_show.html.twig', [
            'contributor' => $contributor,
            'contributions' => $contributor->getContributions(),
        ]);
    }
}
