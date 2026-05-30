<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\EmailVerificationToken;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class EmailVerificationTokenTest extends TestCase
{
    private function makeUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPseudo('testuser');

        return $user;
    }

    public function testTokenIs64CharHex(): void
    {
        $token = new EmailVerificationToken($this->makeUser());
        $this->assertSame(64, strlen($token->getToken()));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token->getToken());
    }

    public function testExpiresAtIs24HoursFromNow(): void
    {
        $before = new \DateTimeImmutable('+23 hours 55 minutes');
        $token = new EmailVerificationToken($this->makeUser());
        $after = new \DateTimeImmutable('+24 hours 5 minutes');

        $this->assertGreaterThan($before, $token->getExpiresAt());
        $this->assertLessThan($after, $token->getExpiresAt());
    }

    public function testCreatedAtIsSet(): void
    {
        $before = new \DateTimeImmutable('-5 seconds');
        $token = new EmailVerificationToken($this->makeUser());
        $after = new \DateTimeImmutable('+5 seconds');

        $this->assertGreaterThan($before, $token->getCreatedAt());
        $this->assertLessThan($after, $token->getCreatedAt());
    }

    public function testIsValidWhenNotExpired(): void
    {
        $token = new EmailVerificationToken($this->makeUser());
        $this->assertTrue($token->isValid());
    }

    public function testIsNotValidWhenExpired(): void
    {
        $user = $this->makeUser();
        $token = new EmailVerificationToken($user);

        $reflection = new \ReflectionClass($token);
        $prop = $reflection->getProperty('expiresAt');
        $prop->setAccessible(true);
        $prop->setValue($token, new \DateTimeImmutable('-1 second'));

        $this->assertFalse($token->isValid());
    }
}
