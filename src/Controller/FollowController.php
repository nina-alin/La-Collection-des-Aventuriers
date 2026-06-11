<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Collection;
use App\Entity\Contributor;
use App\Entity\User;
use App\Entity\UserCollectionSubscription;
use App\Entity\UserFollowedContributor;
use App\Repository\CollectionRepository;
use App\Repository\ContributorRepository;
use App\Repository\UserCollectionSubscriptionRepository;
use App\Repository\UserFollowedContributorRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/follow')]
class FollowController extends AbstractController
{
    public function __construct(private readonly CsrfTokenManagerInterface $csrfTokenManager) {}

    #[Route('/contributor/{id}', name: 'follow_contributor_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleContributor(
        string $id,
        Request $request,
        ContributorRepository $contributorRepository,
        UserFollowedContributorRepository $followRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('follow_contributor_' . $id, $request->request->get('_token'))) {
            return $this->json(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        try {
            $contributor = $contributorRepository->find(Uuid::fromString($id));
        } catch (\InvalidArgumentException) {
            $contributor = null;
        }

        if ($contributor === null) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();

        $existing = $followRepository->findOneBy(['user' => $user, 'contributor' => $contributor]);

        if ($existing !== null) {
            $em->remove($existing);
            $em->flush();
            $followed = false;
        } else {
            try {
                $follow = new UserFollowedContributor($user, $contributor);
                $em->persist($follow);
                $em->flush();
                $followed = true;
            } catch (UniqueConstraintViolationException) {
                $followed = true;
            }
        }

        $newToken = $this->csrfTokenManager->getToken('follow_contributor_' . $id)->getValue();

        return $this->json(['followed' => $followed, 'token' => $newToken]);
    }

    #[Route('/collection/{id}', name: 'follow_collection_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleCollection(
        string $id,
        Request $request,
        CollectionRepository $collectionRepository,
        UserCollectionSubscriptionRepository $subscriptionRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('follow_collection_' . $id, $request->request->get('_token'))) {
            return $this->json(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        try {
            $collection = $collectionRepository->find(Uuid::fromString($id));
        } catch (\InvalidArgumentException) {
            $collection = null;
        }

        if ($collection === null) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();

        $existing = $subscriptionRepository->findOneBy(['user' => $user, 'collection' => $collection]);

        if ($existing !== null) {
            $em->remove($existing);
            $em->flush();
            $followed = false;
        } else {
            try {
                $subscription = new UserCollectionSubscription($user, $collection);
                $em->persist($subscription);
                $em->flush();
                $followed = true;
            } catch (UniqueConstraintViolationException) {
                $followed = true;
            }
        }

        $newToken = $this->csrfTokenManager->getToken('follow_collection_' . $id)->getValue();

        return $this->json(['followed' => $followed, 'token' => $newToken]);
    }
}
