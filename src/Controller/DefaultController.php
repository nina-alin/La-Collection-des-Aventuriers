<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/catalogue', name: 'catalogue_index')]
    public function catalogue(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/suggestions', name: 'suggestions_index')]
    public function suggestions(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/suggestions/nouveau', name: 'suggestions_new')]
    public function suggestionsNew(): Response
    {
        return $this->render('home/index.html.twig');
    }
}
