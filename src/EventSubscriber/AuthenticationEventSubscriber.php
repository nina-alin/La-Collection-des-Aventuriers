<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\BruteForceProtectionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class AuthenticationEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.security')]
        private readonly LoggerInterface $securityLogger,
        private readonly BruteForceProtectionService $bruteForce,
        private readonly RouterInterface $router,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
            LogoutEvent::class => 'onLogout',
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->getMethod() !== 'POST' || $request->getPathInfo() !== '/connexion') {
            return;
        }

        $ip = $request->getClientIp() ?? '0.0.0.0';

        if (!$this->bruteForce->isBlocked($ip)) {
            return;
        }

        $remaining = (int) ceil($this->bruteForce->getRemainingBlockTime($ip) / 60);
        $message = sprintf('Trop de tentatives. Réessayez dans %d minute%s.', $remaining, $remaining > 1 ? 's' : '');

        if ($request->hasSession()) {
            $request->getSession()->getFlashBag()->add('error', $message);
        }

        $event->setResponse(new RedirectResponse($this->router->generate('app_login')));
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        $ip = $event->getRequest()->getClientIp() ?? 'unknown';

        $this->securityLogger->info('Login success', [
            'email' => $user->getUserIdentifier(),
            'ip' => $ip,
        ]);

        $this->bruteForce->resetCounter($ip);
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $ip = $event->getRequest()->getClientIp() ?? 'unknown';
        $passport = $event->getPassport();
        $identifier = $passport?->getUser()?->getUserIdentifier() ?? 'unknown';

        $this->securityLogger->warning('Login failure', [
            'email' => $identifier,
            'ip' => $ip,
        ]);

        $this->bruteForce->recordFailure($ip);
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        $identifier = $token?->getUserIdentifier() ?? 'unknown';
        $ip = $event->getRequest()->getClientIp() ?? 'unknown';

        $this->securityLogger->info('Logout', [
            'email' => $identifier,
            'ip' => $ip,
        ]);
    }

    public function logAccountCreation(User $user): void
    {
        $this->securityLogger->info('Account created', [
            'email' => $user->getUserIdentifier(),
        ]);
    }

    public function logOAuth2Event(string $event, string $email, ?string $error = null): void
    {
        if ($error !== null) {
            $this->securityLogger->warning('OAuth2 event: '.$event, [
                'email' => $email,
                'error' => $error,
            ]);
        } else {
            $this->securityLogger->info('OAuth2 event: '.$event, [
                'email' => $email,
            ]);
        }
    }
}
