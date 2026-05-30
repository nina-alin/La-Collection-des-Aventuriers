<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validation;

class UsernameValidationTest extends TestCase
{
    private function validate(string $value): int
    {
        $validator = Validation::createValidator();
        $constraints = [
            new Length(['min' => 3, 'max' => 30]),
            new Regex(['pattern' => '/^[a-zA-Z0-9_-]+$/']),
        ];

        return count($validator->validate($value, $constraints));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validUsernamesProvider')]
    public function testValidUsernamesPass(string $username): void
    {
        $this->assertSame(0, $this->validate($username));
    }

    public static function validUsernamesProvider(): array
    {
        return [
            'min length' => ['abc'],
            'max length' => [str_repeat('a', 30)],
            'with underscore' => ['hello_world'],
            'with dash' => ['hello-world'],
            'with numbers' => ['user123'],
            'mixed case' => ['UserName'],
            'all valid chars' => ['aB3_-x'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidUsernamesProvider')]
    public function testInvalidUsernamesAreRejected(string $username): void
    {
        $this->assertGreaterThan(0, $this->validate($username));
    }

    public static function invalidUsernamesProvider(): array
    {
        return [
            'too short' => ['ab'],
            'too long' => [str_repeat('a', 31)],
            'with space' => ['hello world'],
            'with dot' => ['hello.world'],
            'with accented char' => ['héros'],
            'empty string' => [''],
            'two chars' => ['ab'],
        ];
    }
}
