<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserCollectionSubscription;
use App\Repository\CollectionRepository;
use App\Repository\UserBookRepository;
use App\Repository\UserCollectionSubscriptionRepository;
use App\Service\CollectionService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

class CollectionController extends AbstractController
{
    #[Route('/collections/{slug}', name: 'app_collection_show', methods: ['GET'])]
    public function show(
        string $slug,
        Request $request,
        CollectionRepository $repo,
        CollectionService $collectionService,
        UserBookRepository $userBookRepository,
        UserCollectionSubscriptionRepository $subscriptionRepository,
    ): Response {
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

        $heroMeta = $collectionService->getHeroMeta($collection);
        $recurringContributors = $collectionService->getRecurringContributors($collection);
        $publishingHistory = $collectionService->getPublishingHistory($collection);

        $user = $this->getUser();
        $ownedCount = null;
        $ownedBookIds = null;
        $isSubscribed = false;

        if ($user !== null) {
            $ownedCount = $userBookRepository->countOwnedByUserForCollection($user, $collection);

            $pageBookIds = [];
            foreach ($books as $b) {
                $pageBookIds[] = $b->getId();
            }
            $ownedBookIds = [];
            $toBuyBookIds = [];
            foreach ($userBookRepository->findByUserAndBookIds($user, $pageBookIds) as $ub) {
                if ($ub->isOwned()) {
                    $ownedBookIds[] = $ub->getBook()->getId();
                }
                if ($ub->isToBuy()) {
                    $toBuyBookIds[] = $ub->getBook()->getId();
                }
            }

            $isSubscribed = (bool) $subscriptionRepository->findOneBy(['user' => $user, 'collection' => $collection]);
        }

        return $this->render('collection/show.html.twig', [
            'collection'            => $collection,
            'books'                 => $books,
            'currentPage'           => $page,
            'totalPages'            => $totalPages,
            'totalBooks'            => $totalBooks,
            'heroMeta'              => $heroMeta,
            'recurringContributors' => $recurringContributors,
            'publishingHistory'     => $publishingHistory,
            'ownedCount'            => $ownedCount,
            'ownedBookIds'          => $ownedBookIds,
            'toBuyBookIds'          => $toBuyBookIds ?? null,
            'isSubscribed'          => $isSubscribed,
        ]);
    }

    #[Route('/collections/{id}/subscribe', name: 'collection_subscribe', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function subscribe(
        string $id,
        Request $request,
        CollectionRepository $collectionRepository,
        UserCollectionSubscriptionRepository $subscriptionRepository,
        EntityManagerInterface $em,
    ): Response {
        $collection = $collectionRepository->find(Uuid::fromString($id));
        if ($collection === null) {
            throw new NotFoundHttpException();
        }

        $this->isCsrfTokenValid('collection_subscribe_' . $id, $request->request->get('_token'));

        /** @var User $user */
        $user = $this->getUser();

        try {
            $subscription = new UserCollectionSubscription($user, $collection);
            $em->persist($subscription);
            $em->flush();
        } catch (UniqueConstraintViolationException) {
            // Already subscribed — silent
        }

        return $this->redirectToRoute('app_collection_show', ['slug' => $collection->getSlug()]);
    }

    #[Route('/collections/{id}/unsubscribe', name: 'collection_unsubscribe', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function unsubscribe(
        string $id,
        Request $request,
        CollectionRepository $collectionRepository,
        UserCollectionSubscriptionRepository $subscriptionRepository,
        EntityManagerInterface $em,
    ): Response {
        $collection = $collectionRepository->find(Uuid::fromString($id));
        if ($collection === null) {
            throw new NotFoundHttpException();
        }

        $this->isCsrfTokenValid('collection_subscribe_' . $id, $request->request->get('_token'));

        /** @var User $user */
        $user = $this->getUser();

        $subscription = $subscriptionRepository->findOneBy(['user' => $user, 'collection' => $collection]);
        if ($subscription !== null) {
            $em->remove($subscription);
            $em->flush();
        }

        return $this->redirectToRoute('app_collection_show', ['slug' => $collection->getSlug()]);
    }
}
