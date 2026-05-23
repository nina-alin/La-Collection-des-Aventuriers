<?php

namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\AuthenticationEventSubscriber;
use App\Service\BruteForceProtectionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class AuthenticationEventSubscriberTest extends TestCase
{
    private LoggerInterface $logger;
    private BruteForceProtectionService $bruteForce;
    private RouterInterface $router;
    private AuthenticationEventSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->bruteForce = $this->createMock(BruteForceProtectionService::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->subscriber = new AuthenticationEventSubscriber(
            $this->logger,
            $this->bruteForce,
            $this->router,
        );
    }

    public function testOnLoginSuccessLogsInfoToSecurityChannel(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $request = Request::create('/connexion', 'POST');
        $event = $this->createMock(LoginSuccessEvent::class);
        $event->method('getUser')->willReturn($user);
        $event->method('getRequest')->willReturn($request);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Login success', $this->arrayHasKey('email'));

        $this->subscriber->onLoginSuccess($event);
    }

    public function testOnLoginFailureLogsWarningToSecurityChannel(): void
    {
        $request = Request::create('/connexion', 'POST');
        $event = $this->createMock(LoginFailureEvent::class);
        $event->method('getRequest')->willReturn($request);
        $event->method('getPassport')->willReturn(null);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Login failure', $this->arrayHasKey('ip'));

        $this->subscriber->onLoginFailure($event);
    }

    public function testOnLogoutLogsInfoToSecurityChannel(): void
    {
        $user = new User();
        $user->setEmail('logout@example.com');
        $token = new UsernamePasswordToken($user, 'main', []);
        $request = Request::create('/deconnexion', 'POST');
        $event = new LogoutEvent($request, $token);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Logout', $this->arrayHasKey('email'));

        $this->subscriber->onLogout($event);
    }

    public function testLogAccountCreationLogsInfo(): void
    {
        $user = new User();
        $user->setEmail('new@example.com');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Account created', $this->arrayHasKey('email'));

        $this->subscriber->logAccountCreation($user);
    }
}
