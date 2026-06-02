<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Book;
use App\Service\UserBookService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserBookController extends AbstractController
{
    #[Route('/user-book/{id}/toggle-favorite', name: 'app_userbook_toggle_favorite', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleFavorite(Book $book, Request $request, UserBookService $userBookService): Response
    {
        if (!$this->isCsrfTokenValid('toggle_favorite_' . $book->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $userBookService->toggleFavorite($this->getUser(), $book);

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_catalogue'));
    }

    #[Route('/user-book/{id}/toggle-owned', name: 'app_userbook_toggle_owned', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleOwned(Book $book, Request $request, UserBookService $userBookService): Response
    {
        if (!$this->isCsrfTokenValid('toggle_owned_' . $book->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $userBookService->toggleOwned($this->getUser(), $book);

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_catalogue'));
    }

    #[Route('/user-book/{id}/remove-wishlist', name: 'app_userbook_remove_wishlist', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function removeFromWishlist(Book $book, Request $request, UserBookService $userBookService): Response
    {
        if (!$this->isCsrfTokenValid('remove_wishlist_' . $book->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $userBookService->toggleToBuy($this->getUser(), $book);

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_catalogue'));
    }
}
