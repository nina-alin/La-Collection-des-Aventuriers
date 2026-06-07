<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ResetPasswordTokenRepository;
use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class PasswordResetController extends AbstractController
{
    public function __construct(
        private readonly PasswordResetService $resetService,
        #[Autowire(service: 'limiter.password_reset_limiter')]
        private readonly RateLimiterFactory $resetLimiter,
        #[Autowire(service: 'limiter.resend_limiter')]
        private readonly RateLimiterFactory $resendLimiter,
    ) {
    }

    #[Route('/mot-de-passe-oublie', name: 'app_password_reset_request', methods: ['GET', 'POST'])]
    public function request(Request $request): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('home');
        }

        $state = 'form';
        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('password_reset_request', $request->request->get('_csrf_token'))) {
                $error = 'Token de sécurité invalide.';
            } else {
                $ip = $request->getClientIp() ?? '0.0.0.0';
                $limiter = $this->resetLimiter->create($ip);
                $limit = $limiter->consume();

                if (!$limit->isAccepted()) {
                    $error = 'Trop de demandes. Réessayez dans une heure.';

                    return $this->render('password_reset/request.html.twig', [
                        'state' => 'form',
                        'error' => $error,
                    ], new Response(null, Response::HTTP_TOO_MANY_REQUESTS));
                }

                $email = (string) $request->request->get('email', '');
                try {
                    $this->resetService->requestReset($email);
                    $state = 'sent';
                } catch (\RuntimeException $e) {
                    $error = 'Une erreur est survenue lors de l\'envoi de l\'e-mail. Réessayez.';
                }
            }
        }

        return $this->render('password_reset/request.html.twig', [
            'state' => $state,
            'error' => $error,
        ]);
    }

    #[Route('/mot-de-passe-oublie/renvoyer', name: 'app_password_reset_resend', methods: ['POST'])]
    public function resend(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('password_reset_resend', $request->request->get('_csrf_token'))) {
            return $this->redirectToRoute('app_password_reset_request');
        }

        $ip = $request->getClientIp() ?? '0.0.0.0';
        $email = (string) $request->request->get('email', '');

        try {
            $this->resetService->resend($email, $ip);
        } catch (\RuntimeException) {
            // rate limited — still redirect to sent state
        }

        $this->addFlash('sent', 'Un nouveau lien a été envoyé si cette adresse est associée à un compte.');

        return $this->redirectToRoute('app_password_reset_request');
    }

    #[Route('/reinitialiser-mot-de-passe', name: 'app_password_reset_show', methods: ['GET'])]
    public function showResetForm(Request $request, ResetPasswordTokenRepository $tokenRepository): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('home');
        }

        $tokenString = (string) $request->query->get('token', '');
        $state = 'form';

        if ($tokenString === '' || $tokenRepository->findValidTokenByString($tokenString) === null) {
            $state = 'invalid';
        }

        return $this->render('password_reset/reset.html.twig', [
            'state' => $state,
            'token' => $tokenString,
            'error' => null,
        ]);
    }

    #[Route('/reinitialiser-mot-de-passe', name: 'app_password_reset', methods: ['POST'])]
    public function reset(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('reset_password', $request->request->get('_csrf_token'))) {
            return $this->render('password_reset/reset.html.twig', [
                'state' => 'form',
                'token' => $request->request->get('token', ''),
                'error' => 'Token de sécurité invalide.',
            ]);
        }

        $tokenString = (string) $request->request->get('token', '');
        $plainPassword = (string) $request->request->get('plainPassword', '');
        $passwordConfirm = (string) $request->request->get('passwordConfirm', '');

        try {
            $this->resetService->resetPassword($tokenString, $plainPassword, $passwordConfirm);
            $request->getSession()->invalidate();

            return $this->render('password_reset/reset.html.twig', [
                'state' => 'success',
                'token' => '',
                'error' => null,
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->render('password_reset/reset.html.twig', [
                'state' => 'form',
                'token' => $tokenString,
                'error' => $e->getMessage(),
            ]);
        } catch (\RuntimeException $e) {
            return $this->render('password_reset/reset.html.twig', [
                'state' => 'invalid',
                'token' => $tokenString,
                'error' => null,
            ]);
        }
    }
}
