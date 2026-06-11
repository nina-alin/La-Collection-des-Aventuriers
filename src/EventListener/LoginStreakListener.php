<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Service\LoginStreakService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginStreakListener
{
    public function __construct(
        private readonly LoginStreakService $loginStreakService,
    ) {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $this->loginStreakService->update($user);
    }
}
