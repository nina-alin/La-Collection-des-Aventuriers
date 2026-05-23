<?php

namespace App\Controller;

use App\Service\BruteForceProtectionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly BruteForceProtectionService $bruteForce,
        private readonly AuthenticationUtils $authenticationUtils,
    ) {
    }

    #[Route('/connexion', name: 'app_login', methods: ['GET'])]
    public function login(Request $request): Response
    {
        $ip = $request->getClientIp() ?? '0.0.0.0';
        $blocked = $this->bruteForce->isBlocked($ip);
        $remainingMinutes = 0;

        if ($blocked) {
            $remainingMinutes = (int) ceil($this->bruteForce->getRemainingBlockTime($ip) / 60);
        }

        return $this->render('security/login.html.twig', [
            'lastUsername' => $this->authenticationUtils->getLastUsername(),
            'authenticationError' => $this->authenticationUtils->getLastAuthenticationError(),
            'brute_blocked' => $blocked,
            'remaining_minutes' => $remainingMinutes,
        ]);
    }
}
