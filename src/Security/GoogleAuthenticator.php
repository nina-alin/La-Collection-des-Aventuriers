<?php

namespace App\Security;

use App\EventSubscriber\AuthenticationEventSubscriber;
use App\Repository\UserRepository;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\HttpUtils;

class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly RouterInterface $router,
        private readonly AuthenticationEventSubscriber $subscriber,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'app_oauth_google_callback';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');

        try {
            $guzzleClient = new GuzzleClient(['connect_timeout' => 10, 'timeout' => 10]);
            $accessToken = $this->fetchAccessToken($client, ['collaborators' => ['httpClient' => $guzzleClient]]);
        } catch (ConnectException|RequestException $e) {
            $request->getSession()->getFlashBag()->add('error', 'Le service Google est indisponible. Utilisez la connexion classique.');
            $this->subscriber->logOAuth2Event('error', 'unknown', $e->getMessage());
            throw new CustomUserMessageAuthenticationException('google_unavailable');
        }

        try {
            $googleUser = $client->fetchUserFromToken($accessToken);
        } catch (ConnectException|RequestException $e) {
            $request->getSession()->getFlashBag()->add('error', 'Le service Google est indisponible. Utilisez la connexion classique.');
            $this->subscriber->logOAuth2Event('error', 'unknown', $e->getMessage());
            throw new CustomUserMessageAuthenticationException('google_unavailable');
        }

        $googleEmail = $googleUser->getEmail();

        $rawData = $googleUser->toArray();
        $emailVerified = $rawData['email_verified'] ?? false;

        if (!$emailVerified) {
            $request->getSession()->getFlashBag()->add('error', 'Adresse Google non vérifiée. Utilisez la connexion classique.');
            $this->subscriber->logOAuth2Event('email_not_verified', (string) $googleEmail);
            throw new CustomUserMessageAuthenticationException('email_not_verified');
        }

        $grantedScopes = $accessToken->getValues()['scope'] ?? '';
        if (!str_contains($grantedScopes, 'email') || !str_contains($grantedScopes, 'profile')) {
            $request->getSession()->getFlashBag()->add('error', 'Connexion Google annulée.');
            $this->subscriber->logOAuth2Event('scopes_rejected', (string) $googleEmail);
            throw new CustomUserMessageAuthenticationException('scopes_rejected');
        }

        $request->getSession()->set('_google_oauth_pending', [
            'email' => strtolower((string) $googleEmail),
            'google_id' => $rawData['sub'] ?? null,
            'display_name' => $rawData['name'] ?? null,
            'avatar_url' => $rawData['picture'] ?? null,
            'email_verified' => $emailVerified,
        ]);

        return new SelfValidatingPassport(
            new UserBadge((string) $googleEmail, fn() => $this->userRepository->loadUserByIdentifier((string) $googleEmail))
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $request->getSession()->remove('_google_oauth_pending');
        $this->subscriber->logOAuth2Event('success', $token->getUserIdentifier());

        $targetPath = $request->getSession()->get('_security.'.$firewallName.'.target_path');
        if ($targetPath) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $pending = $request->getSession()->get('_google_oauth_pending');

        if ($exception->getMessage() === 'google_unavailable'
            || $exception->getMessage() === 'email_not_verified'
            || $exception->getMessage() === 'scopes_rejected'
        ) {
            return new RedirectResponse($this->router->generate('app_login'));
        }

        if ($pending !== null) {
            $this->subscriber->logOAuth2Event('consent_required', $pending['email'] ?? 'unknown');
            return new RedirectResponse($this->router->generate('app_oauth_google_consent'));
        }

        $request->getSession()->getFlashBag()->add('error', 'Une erreur est survenue lors de la connexion Google.');
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
