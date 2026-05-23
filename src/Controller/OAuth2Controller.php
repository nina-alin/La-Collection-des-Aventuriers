<?php

namespace App\Controller;

use App\EventSubscriber\AuthenticationEventSubscriber;
use App\Security\GoogleAuthenticator;
use App\Service\GoogleOAuth2Service;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class OAuth2Controller extends AbstractController
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly GoogleOAuth2Service $googleOAuth2Service,
        private readonly Security $security,
        private readonly AuthenticationEventSubscriber $subscriber,
    ) {
    }

    #[Route('/auth/google', name: 'app_oauth_google', methods: ['GET'])]
    public function redirectToGoogle(): Response
    {
        $this->subscriber->logOAuth2Event('start', 'anonymous');

        return $this->clientRegistry
            ->getClient('google')
            ->redirect(['openid', 'email', 'profile']);
    }

    #[Route('/auth/google/consent', name: 'app_oauth_google_consent', methods: ['GET', 'POST'])]
    public function rgpdConsent(Request $request): Response
    {
        $pending = $request->getSession()->get('_google_oauth_pending');

        if ($pending === null) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $token = new CsrfToken('google_consent', $request->request->get('_token', ''));
            if (!$this->csrfTokenManager->isTokenValid($token)) {
                $request->getSession()->remove('_google_oauth_pending');
                $this->addFlash('error', 'Vous devez accepter les conditions pour créer un compte.');
                return $this->redirectToRoute('app_login');
            }

            if ($request->request->get('cancel') !== null || !$request->request->get('rgpdConsent')) {
                $request->getSession()->remove('_google_oauth_pending');
                $this->addFlash('error', 'Vous devez accepter les conditions pour créer un compte.');
                return $this->redirectToRoute('app_login');
            }

            try {
                $user = $this->googleOAuth2Service->findOrCreateUser($pending);
                $request->getSession()->remove('_google_oauth_pending');
                $this->subscriber->logOAuth2Event('account_created', $pending['email'] ?? 'unknown');
                $this->security->login($user, GoogleAuthenticator::class);

                return $this->redirectToRoute('home');
            } catch (\RuntimeException $e) {
                $request->getSession()->remove('_google_oauth_pending');
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('oauth2/consent.html.twig', [
            'csrf_token' => $this->csrfTokenManager->getToken('google_consent')->getValue(),
        ]);
    }
}
