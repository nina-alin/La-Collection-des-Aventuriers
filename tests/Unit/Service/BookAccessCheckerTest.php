<?php

namespace App\Tests\Unit\Service;

use App\Entity\Book;
use App\Entity\Enum\BookStatus;
use App\Service\BookAccessChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

class BookAccessCheckerTest extends TestCase
{
    private BookAccessChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new BookAccessChecker();
    }

    private function makeBook(BookStatus $status): Book
    {
        $book = new Book();
        $book->setTitle('Test');
        $book->setStatus($status);
        return $book;
    }

    private function makeUser(array $roles): UserInterface
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getRoles')->willReturn($roles);
        return $user;
    }

    public function testPublishedBookAnonymousNoException(): void
    {
        $this->checker->assertViewable($this->makeBook(BookStatus::PUBLISHED), null);
        $this->assertTrue(true);
    }

    public function testPublishedBookRoleUserNoException(): void
    {
        $this->checker->assertViewable(
            $this->makeBook(BookStatus::PUBLISHED),
            $this->makeUser(['ROLE_USER'])
        );
        $this->assertTrue(true);
    }

    public function testPendingBookAnonymousThrows(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->checker->assertViewable($this->makeBook(BookStatus::PENDING), null);
    }

    public function testPendingBookRoleUserThrows(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->checker->assertViewable(
            $this->makeBook(BookStatus::PENDING),
            $this->makeUser(['ROLE_USER'])
        );
    }

    public function testPendingBookModeratorNoException(): void
    {
        $this->checker->assertViewable(
            $this->makeBook(BookStatus::PENDING),
            $this->makeUser(['ROLE_MODERATOR'])
        );
        $this->assertTrue(true);
    }

    public function testRejectedBookAnonymousThrows(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->checker->assertViewable($this->makeBook(BookStatus::REJECTED), null);
    }

    public function testRejectedBookModeratorNoException(): void
    {
        $this->checker->assertViewable(
            $this->makeBook(BookStatus::REJECTED),
            $this->makeUser(['ROLE_MODERATOR'])
        );
        $this->assertTrue(true);
    }
}
