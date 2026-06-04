<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\User;
use App\Entity\UserBook;
use App\Event\BookAddedToWishlistEvent;
use App\Repository\UserBookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UserBookService
{
    public function __construct(
        private readonly UserBookRepository $userBookRepository,
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function toggleOwned(User $user, Book $book): array
    {
        $userBook = $this->userBookRepository->findByUserAndBook($user, $book);

        if ($userBook === null) {
            $userBook = new UserBook($user, $book);
            $userBook->setIsOwned(true);
            $this->em->persist($userBook);
            $this->em->flush();
            return ['newValue' => true, 'affected' => []];
        }

        $newValue = !$userBook->isOwned();
        $userBook->setIsOwned($newValue);

        $affected = [];
        if ($newValue && $userBook->isToBuy()) {
            $userBook->setIsToBuy(false);
            $affected[] = 'isToBuy';
        }

        if ($userBook->isAllInactive()) {
            $this->em->remove($userBook);
        }

        $this->em->flush();

        return ['newValue' => $newValue, 'affected' => $affected];
    }

    public function toggleToRead(User $user, Book $book): array
    {
        $userBook = $this->userBookRepository->findByUserAndBook($user, $book);

        if ($userBook === null) {
            $userBook = new UserBook($user, $book);
            $userBook->setIsToRead(true);
            $this->em->persist($userBook);
            $this->em->flush();
            return ['newValue' => true, 'affected' => []];
        }

        $newValue = !$userBook->isToRead();
        $userBook->setIsToRead($newValue);

        if ($userBook->isAllInactive()) {
            $this->em->remove($userBook);
        }

        $this->em->flush();

        return ['newValue' => $newValue, 'affected' => []];
    }

    public function toggleToBuy(User $user, Book $book): array
    {
        $userBook = $this->userBookRepository->findByUserAndBook($user, $book);

        if ($userBook === null) {
            $userBook = new UserBook($user, $book);
            $userBook->setIsToBuy(true);
            $this->em->persist($userBook);
            $this->em->flush();
            $this->dispatcher->dispatch(new BookAddedToWishlistEvent($user, $book));
            return ['newValue' => true, 'affected' => []];
        }

        $newValue = !$userBook->isToBuy();
        $userBook->setIsToBuy($newValue);

        $affected = [];
        if ($newValue && $userBook->isOwned()) {
            $userBook->setIsOwned(false);
            $affected[] = 'isOwned';
        }

        if ($userBook->isAllInactive()) {
            $this->em->remove($userBook);
        }

        $this->em->flush();

        if ($newValue) {
            $this->dispatcher->dispatch(new BookAddedToWishlistEvent($user, $book));
        }

        return ['newValue' => $newValue, 'affected' => $affected];
    }

    public function toggleFavorite(User $user, Book $book): array
    {
        $userBook = $this->userBookRepository->findByUserAndBook($user, $book);

        if ($userBook === null) {
            $userBook = new UserBook($user, $book);
            $userBook->setIsFavorite(true);
            $this->em->persist($userBook);
            $this->em->flush();
            return ['newValue' => true, 'affected' => []];
        }

        $newValue = !$userBook->isFavorite();
        $userBook->setIsFavorite($newValue);

        if ($userBook->isAllInactive()) {
            $this->em->remove($userBook);
        }

        $this->em->flush();

        return ['newValue' => $newValue, 'affected' => []];
    }
}
