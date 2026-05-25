<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CollectionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class CollectionController extends AbstractController
{
    #[Route('/collections/{slug}', name: 'app_collection_show', methods: ['GET'])]
    public function show(string $slug, Request $request, CollectionRepository $repo): Response
    {
        $collection = $repo->findBySlug($slug);
        if ($collection === null) {
            throw new NotFoundHttpException();
        }

        $rawPage = $request->query->get('page', '1');
        if (!ctype_digit($rawPage) || (int) $rawPage < 1) {
            throw new NotFoundHttpException();
        }
        $page = (int) $rawPage;

        $books = $repo->paginateBooksForCollection($collection, $page);
        $totalBooks = count($books);
        $totalPages = $totalBooks > 0 ? (int) ceil($totalBooks / 20) : 1;

        if ($page > $totalPages) {
            throw new NotFoundHttpException();
        }

        return $this->render('collection/show.html.twig', [
            'collection'  => $collection,
            'books'       => $books,
            'currentPage' => $page,
            'totalPages'  => $totalPages,
            'totalBooks'  => $totalBooks,
        ]);
    }
}
