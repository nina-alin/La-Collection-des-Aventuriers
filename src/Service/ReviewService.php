<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\Review;
use App\Entity\User;
use App\Event\ReviewSubmittedEvent;
use App\Repository\ReviewRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReviewService
{
    public function __construct(
        private readonly ReviewRepository $reviewRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function submit(User $user, Book $book, int $score, ?string $comment): Review
    {
        try {
            $review = $this->reviewRepository->findByUserAndBook($user, $book) ?? new Review();
            $review->setUser($user);
            $review->setBook($book);
            $review->setScore($score);
            $review->setComment($comment);

            $this->entityManager->persist($review);
            $this->entityManager->flush();

            $this->dispatcher->dispatch(new ReviewSubmittedEvent($user, $book));

            return $review;
        } catch (UniqueConstraintViolationException $e) {
            throw new \RuntimeException('Duplicate review — race condition detected.', 409, $e);
        }
    }

    public function delete(Review $review): void
    {
        $this->entityManager->remove($review);
        $this->entityManager->flush();
    }
}
