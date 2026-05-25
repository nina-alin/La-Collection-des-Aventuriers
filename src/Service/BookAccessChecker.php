<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\Enum\BookStatus;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

class BookAccessChecker
{
    public function assertViewable(Book $book, ?UserInterface $user): void
    {
        if ($book->getStatus() === BookStatus::PUBLISHED) {
            return;
        }

        if ($user !== null && in_array('ROLE_MODERATOR', $user->getRoles(), true)) {
            return;
        }

        throw new NotFoundHttpException();
    }
}
