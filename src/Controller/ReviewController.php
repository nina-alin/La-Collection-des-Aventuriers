<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Review;
use App\Repository\BookRepository;
use App\Repository\ReviewRepository;
use App\Security\Voter\ReviewVoter;
use App\Service\ContributorLevelService;
use App\Service\ReviewService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Turbo\TurboBundle;

class ReviewController extends AbstractController
{
    #[Route('/livre/{slug}/avis', name: 'app_book_review_submit', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function submit(
        string $slug,
        Request $request,
        BookRepository $bookRepository,
        ReviewService $reviewService,
        ReviewRepository $reviewRepository,
    ): Response {
        $book = $bookRepository->findBySlugWithRelations($slug);
        if ($book === null) {
            throw new NotFoundHttpException();
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('review_submit', $submittedToken)) {
            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return new Response('CSRF token invalide.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            return new Response('CSRF token invalide.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $score = $request->request->get('score');
        if ($score === null || $score === '') {
            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return new Response('La note est obligatoire.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            return new Response('La note est obligatoire.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $score = (int) $score;
        if ($score < 1 || $score > 10) {
            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return new Response('La note doit être entre 1 et 10.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            return new Response('La note doit être entre 1 et 10.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $comment = $request->request->get('comment');

        try {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $review = $reviewService->submit($user, $book, $score, $comment);
        } catch (\RuntimeException $e) {
            if ($e->getCode() === 409) {
                return new Response('Un avis est déjà en cours de soumission.', Response::HTTP_CONFLICT);
            }
            throw $e;
        }

        $userReview = $review;
        $reviewStats = $reviewRepository->getStatsForBook($book);

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('livre/_review_stream.html.twig', [
                'book' => $book,
                'userReview' => $userReview,
                'reviewStats' => $reviewStats,
            ]);
        }

        return $this->redirectToRoute('app_book_show', ['slug' => $book->getSlug()]);
    }

    #[Route('/livre/{slug}/avis/{id}', name: 'app_book_review_delete', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(
        string $slug,
        Review $review,
        Request $request,
        BookRepository $bookRepository,
        ReviewService $reviewService,
        ReviewRepository $reviewRepository,
    ): Response {
        $book = $bookRepository->findBySlugWithRelations($slug);
        if ($book === null) {
            throw new NotFoundHttpException();
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('review_submit', $submittedToken)) {
            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return new Response('CSRF token invalide.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            return new Response('CSRF token invalide.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->denyAccessUnlessGranted(ReviewVoter::CAN_DELETE, $review);

        $reviewService->delete($review);

        $userReview = null;
        $reviewStats = $reviewRepository->getStatsForBook($book);

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('livre/_review_stream.html.twig', [
                'book' => $book,
                'userReview' => $userReview,
                'reviewStats' => $reviewStats,
            ]);
        }

        return $this->redirectToRoute('app_book_show', ['slug' => $book->getSlug()]);
    }

    #[Route('/livre/{slug}/avis', name: 'app_book_reviews', methods: ['GET'])]
    public function list(
        string $slug,
        Request $request,
        BookRepository $bookRepository,
        ReviewRepository $reviewRepository,
        ContributorLevelService $contributorLevelService,
    ): Response {
        $book = $bookRepository->findBySlugWithRelations($slug);
        if ($book === null) {
            throw new NotFoundHttpException();
        }

        $filter = $request->query->get('filter', 'recentes');
        $page = max(1, (int) $request->query->get('page', 1));

        $paginator = $reviewRepository->findPaginatedByBook($book, $filter, $page);
        $totalPages = (int) ceil(count($paginator) / 10);

        $reviewAuthors = [];
        foreach ($paginator as $review) {
            if ($review->getUser() !== null) {
                $reviewAuthors[] = $review->getUser();
            }
        }
        $ranksByUserId = $contributorLevelService->computeRankBatch($reviewAuthors);

        return $this->render('livre/_reviews_list.html.twig', [
            'book' => $book,
            'paginator' => $paginator,
            'filter' => $filter,
            'page' => $page,
            'totalPages' => $totalPages,
            'ranksByUserId' => $ranksByUserId,
        ]);
    }
}
