<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TokenTestController extends AbstractController
{
    #[Route('/test-tokens', name: 'test_tokens')]
    public function index(): Response
    {
        return $this->render('test/tokens.html.twig');
    }
}
