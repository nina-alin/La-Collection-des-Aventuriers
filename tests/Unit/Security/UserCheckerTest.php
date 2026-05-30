<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class UserCheckerTest extends TestCase
{
    private UserChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new UserChecker();
    }

    public function testThrowsWhenEmailNotVerifiedAndNoGoogleId(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPseudo('testuser');
        $user->setIsEmailVerified(false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('email_not_verified');

        $this->checker->checkPreAuth($user);
    }

    public function testNoExceptionWhenEmailVerified(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPseudo('testuser');
        $user->setIsEmailVerified(true);

        $this->checker->checkPreAuth($user);
        $this->assertTrue(true);
    }

    public function testNoExceptionWhenGoogleIdIsSet(): void
    {
        $user = new User();
        $user->setEmail('google@example.com');
        $user->setPseudo('googleuser');
        $user->setIsEmailVerified(false);
        $user->setGoogleId('google-id-123');

        $this->checker->checkPreAuth($user);
        $this->assertTrue(true);
    }
}
