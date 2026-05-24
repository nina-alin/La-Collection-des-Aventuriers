<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class TokenTestController extends AbstractController
{
    #[Route('/test-tokens', name: 'test_tokens')]
    public function index(): Response
    {
        return $this->render('test/tokens.html.twig');
    }

    #[Route('/test-tokens/csrf/{tokenId}', name: 'test_csrf_token', methods: ['GET'])]
    public function csrfToken(string $tokenId, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        return $this->json(['token' => $csrfTokenManager->getToken($tokenId)->getValue()]);
    }
}
