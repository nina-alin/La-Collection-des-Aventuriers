<?php

namespace App\Tests\Unit\Service;

use App\Entity\Book;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\ReviewRepository;
use App\Service\ReviewService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReviewServiceTest extends TestCase
{
    private ReviewRepository&MockObject $reviewRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private EventDispatcherInterface&MockObject $dispatcher;
    private ReviewService $service;

    protected function setUp(): void
    {
        $this->reviewRepository = $this->createMock(ReviewRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->service = new ReviewService($this->reviewRepository, $this->entityManager, $this->dispatcher);
    }

    public function testSubmitCreatesNewReviewWhenNoneExists(): void
    {
        $user = new User();
        $book = $this->createBookMock();

        $this->reviewRepository
            ->method('findByUserAndBook')
            ->willReturn(null);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $review = $this->service->submit($user, $book, 8, 'Great book!');

        $this->assertInstanceOf(Review::class, $review);
        $this->assertSame(8, $review->getScore());
        $this->assertSame('Great book!', $review->getComment());
        $this->assertSame($user, $review->getUser());
        $this->assertSame($book, $review->getBook());
    }

    public function testSubmitUpdatesExistingReview(): void
    {
        $user = new User();
        $book = $this->createBookMock();

        $existing = new Review();
        $existing->setScore(5);
        $existing->setComment('Average');
        $existing->setUser($user);
        $existing->setBook($book);

        $this->reviewRepository
            ->method('findByUserAndBook')
            ->willReturn($existing);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $review = $this->service->submit($user, $book, 9, 'Actually great!');

        $this->assertSame($existing, $review);
        $this->assertSame(9, $review->getScore());
        $this->assertSame('Actually great!', $review->getComment());
    }

    public function testSubmitNormalizesEmptyCommentToNull(): void
    {
        $user = new User();
        $book = $this->createBookMock();

        $this->reviewRepository->method('findByUserAndBook')->willReturn(null);
        $this->entityManager->method('persist');
        $this->entityManager->method('flush');

        $review = $this->service->submit($user, $book, 7, '');

        $this->assertNull($review->getComment());
    }

    public function testSubmitThrowsRuntimeExceptionOnUniqueConstraintViolation(): void
    {
        $user = new User();
        $book = $this->createBookMock();

        $this->reviewRepository->method('findByUserAndBook')->willReturn(null);

        $driverException = $this->createMock(\Doctrine\DBAL\Driver\Exception::class);
        $this->entityManager->method('flush')->willThrowException(
            new UniqueConstraintViolationException($driverException, null)
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(409);

        $this->service->submit($user, $book, 5, null);
    }

    public function testDeleteRemovesReviewAndFlushes(): void
    {
        $review = new Review();

        $this->entityManager->expects($this->once())->method('remove')->with($review);
        $this->entityManager->expects($this->once())->method('flush');

        $this->service->delete($review);
    }

    private function createBookMock(): Book
    {
        $book = $this->createMock(Book::class);
        $book->method('getSlug')->willReturn('test-book');
        return $book;
    }
}
