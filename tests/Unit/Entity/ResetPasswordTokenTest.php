<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\ResetPasswordToken;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ResetPasswordTokenTest extends TestCase
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
        $token = new ResetPasswordToken($this->makeUser());
        $this->assertSame(64, strlen($token->getToken()));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token->getToken());
    }

    public function testExpiresAtIs30MinutesFromNow(): void
    {
        $before = new \DateTimeImmutable('+29 minutes');
        $token = new ResetPasswordToken($this->makeUser());
        $after = new \DateTimeImmutable('+31 minutes');

        $this->assertGreaterThan($before, $token->getExpiresAt());
        $this->assertLessThan($after, $token->getExpiresAt());
    }

    public function testUsedDefaultsFalse(): void
    {
        $token = new ResetPasswordToken($this->makeUser());
        $this->assertFalse($token->isUsed());
    }

    public function testIsValidWhenNotExpiredAndNotUsed(): void
    {
        $token = new ResetPasswordToken($this->makeUser());
        $this->assertTrue($token->isValid());
    }

    public function testIsNotValidWhenUsed(): void
    {
        $token = new ResetPasswordToken($this->makeUser());
        $token->setUsed(true);
        $this->assertFalse($token->isValid());
    }

    public function testIsNotValidWhenExpired(): void
    {
        $token = new ResetPasswordToken($this->makeUser());

        $reflection = new \ReflectionClass($token);
        $prop = $reflection->getProperty('expiresAt');
        $prop->setAccessible(true);
        $prop->setValue($token, new \DateTimeImmutable('-1 second'));

        $this->assertFalse($token->isValid());
    }
}
