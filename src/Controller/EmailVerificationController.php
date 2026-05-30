<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\EmailVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EmailVerificationController extends AbstractController
{
    public function __construct(
        private readonly EmailVerificationService $verificationService,
    ) {
    }

    #[Route('/confirmation-email/{token}', name: 'app_email_verify', methods: ['GET'])]
    public function verify(string $token): Response
    {
        $success = $this->verificationService->verifyToken($token);

        return $this->render('email_verification/verified.html.twig', [
            'state' => $success ? 'success' : 'error',
        ]);
    }

    #[Route('/inscription/renvoyer-confirmation', name: 'app_resend_confirmation', methods: ['POST'])]
    public function resend(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('resend_confirmation', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');

            return $this->redirectToRoute('app_register');
        }

        $email = (string) $request->request->get('email', '');
        $ip = $request->getClientIp() ?? '0.0.0.0';

        try {
            $this->verificationService->resend($email, $ip);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'rate_limited') {
                $this->addFlash('error', 'Trop de demandes. Réessayez dans une heure.');

                return $this->redirectToRoute('app_register');
            }
        }

        $this->addFlash('confirmation', 'Un nouveau lien de confirmation a été envoyé.');

        return $this->redirectToRoute('app_register');
    }
}
