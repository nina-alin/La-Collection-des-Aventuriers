<?php

namespace App\Controller;

use App\Repository\BookRepository;
use App\Repository\ReviewRepository;
use App\Service\BookAccessChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class BookController extends AbstractController
{
    #[Route('/livre/{slug}', name: 'app_book_show', methods: ['GET'])]
    public function show(
        string $slug,
        BookRepository $bookRepository,
        BookAccessChecker $bookAccessChecker,
        ReviewRepository $reviewRepository,
    ): Response {
        $book = $bookRepository->findBySlugWithRelations($slug);

        if ($book === null) {
            throw new NotFoundHttpException();
        }

        $bookAccessChecker->assertViewable($book, $this->getUser());

        $reviewStats = $reviewRepository->getStatsForBook($book);

        /** @var \App\Entity\User|null $currentUser */
        $currentUser = $this->getUser();
        $userReview = $currentUser !== null
            ? $reviewRepository->findByUserAndBook($currentUser, $book)
            : null;

        return $this->render('livre/show.html.twig', [
            'book' => $book,
            'reviewStats' => $reviewStats,
            'userReview' => $userReview,
        ]);
    }
}
