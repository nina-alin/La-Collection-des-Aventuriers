<?php

namespace App\Controller;

use App\Entity\Collection;
use App\Entity\Contributor;
use App\Entity\Editor;
use App\Entity\Enum\SuggestionEntityType;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\CollectionRepository;
use App\Repository\ContributorRepository;
use App\Repository\SuggestionRepository;
use App\Service\ContributorLevelService;
use App\Service\ContributorSlugger;
use App\Service\CollectionSlugger;
use App\Service\SuggestionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class SuggestionController extends AbstractController
{
    #[Route('/suggestions', name: 'suggestions_index', methods: ['GET'])]
    public function index(
        SuggestionService $suggestionService,
        ContributorLevelService $contributorLevelService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $metrics = $contributorLevelService->getMetrics($user);

        return $this->render('suggestion/index.html.twig', [
            'metrics' => $metrics,
        ]);
    }

    #[Route('/api/suggestions/feed', name: 'app_suggestions_feed', methods: ['GET'])]
    public function feed(SuggestionRepository $suggestionRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $suggestions = $suggestionRepository->findRecentByUser($user);

        $data = [];
        $counts = ['total' => 0, 'pending' => 0, 'validated' => 0, 'refused' => 0];

        foreach ($suggestions as $suggestion) {
            $formData   = $suggestion->getFormData();
            $entityName = $formData['title'] ?? $formData['name'] ?? '';

            $refusal = $suggestion->getRefusal();
            $refusalData = null;
            if ($refusal !== null) {
                $knownActions = array_map(
                    fn ($a) => $a->value,
                    \App\Entity\Enum\SuggestionRefusalAction::cases()
                );
                $actions = array_values(array_filter(
                    $refusal->getActions(),
                    fn ($a) => in_array($a, $knownActions, true)
                ));

                $refusalData = [
                    'moderatorName' => $refusal->getModerator()?->getDisplayName() ?? 'Modérateur',
                    'reason'        => $refusal->getReason(),
                    'actions'       => $actions,
                ];
            }

            $statusValue = $suggestion->getStatus()->value;
            $counts['total']++;
            match ($statusValue) {
                'PENDING'   => $counts['pending']++,
                'VALIDATED' => $counts['validated']++,
                'REFUSED'   => $counts['refused']++,
                default     => null,
            };

            $data[] = [
                'id'          => $suggestion->getId()->toRfc4122(),
                'entityType'  => $suggestion->getEntityType()->value,
                'mode'        => $suggestion->getMode()->value,
                'entityName'  => $entityName,
                'status'      => $statusValue,
                'submittedAt' => $suggestion->getSubmittedAt()->format(\DateTimeInterface::ATOM),
                'refusal'     => $refusalData,
            ];
        }

        return $this->json([
            'suggestions'  => $data,
            'counts'       => $counts,
            'pendingCount' => $counts['pending'],
        ]);
    }

    #[Route('/api/suggestions/autocomplete/{type}', name: 'app_suggestions_autocomplete', methods: ['GET'])]
    public function autocomplete(
        string $type,
        Request $request,
        EntityManagerInterface $em,
        BookRepository $bookRepository,
        ContributorRepository $contributorRepository,
        CollectionRepository $collectionRepository,
    ): JsonResponse {
        $validTypes = ['book', 'author', 'illustrator', 'traductor', 'editor', 'collection'];
        $q = (string) $request->query->get('q', '');

        if (!in_array($type, $validTypes, true) || strlen($q) < 2) {
            return $this->json(['error' => 'Invalid type or query too short'], 400);
        }

        $results = match ($type) {
            'book'       => $this->searchBooks($bookRepository, $q),
            'editor'     => $this->searchEditors($em, $q),
            'collection' => $this->searchCollections($collectionRepository, $q),
            default      => $this->searchContributors($contributorRepository, $q),
        };

        return $this->json(['results' => $results]);
    }

    #[Route('/api/suggestions/check-unique', name: 'app_suggestions_check_unique', methods: ['GET'])]
    public function checkUnique(
        Request $request,
        BookRepository $bookRepository,
    ): JsonResponse {
        $field      = $request->query->get('field', '');
        $value      = $request->query->get('value', '');
        $entityType = $request->query->get('entityType', '');

        if (!in_array($field, ['title', 'subtitle'], true) || $entityType !== 'book' || $value === '') {
            return $this->json(['error' => 'Invalid parameters'], 400);
        }

        $book = $bookRepository->findOneByTitleCaseInsensitive($value, $field === 'subtitle');

        if ($book === null) {
            return $this->json(['unique' => true, 'existing' => null]);
        }

        return $this->json([
            'unique'   => false,
            'existing' => [
                'id'    => $book->getId(),
                'label' => $book->getTitle(),
                'url'   => $this->generateUrl('app_book_show', ['slug' => $book->getSlug()]),
            ],
        ]);
    }

    #[Route('/api/suggestions/entities/{type}', name: 'app_suggestions_create_entity', methods: ['POST'])]
    public function createEntity(
        string $type,
        Request $request,
        EntityManagerInterface $em,
        ContributorSlugger $contributorSlugger,
        CollectionSlugger $collectionSlugger,
        #[Autowire(service: 'limiter.suggestion_entity_creation_limiter')]
        RateLimiterFactory $suggestionEntityCreationLimiter,
    ): JsonResponse {
        /** @var User $user */
        $user    = $this->getUser();
        $limiter = $suggestionEntityCreationLimiter->create($user->getId()->toRfc4122());
        $limit   = $limiter->consume(1);
        if (!$limit->isAccepted()) {
            return $this->json(['error' => 'Trop de créations. Réessayez dans une heure.'], 429);
        }
        $allowedTypes = ['author', 'illustrator', 'traductor', 'editor', 'collection'];
        if (!in_array($type, $allowedTypes, true)) {
            return $this->json(['error' => 'Type not allowed for on-the-fly creation'], 400);
        }

        $csrfToken = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('suggestion_entity_create', $csrfToken)) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $body = json_decode($request->getContent(), true) ?? [];
        $name = trim((string) ($body['name'] ?? ''));

        if ($name === '') {
            return $this->json(['error' => 'Name cannot be blank'], 400);
        }

        if ($type === 'editor') {
            $entity = new Editor();
            $entity->setName($name);
        } elseif ($type === 'collection') {
            $entity = new Collection();
            $entity->setNom($name);
            $entity->setDescription('');
            $slug = $collectionSlugger->generateUnique($name);
            $entity->setSlug($slug);
        } else {
            $parts = explode(' ', $name, 2);
            $contributor = new Contributor();
            $contributor->setFirstName($parts[0]);
            $contributor->setLastName($parts[1] ?? '');
            $slug = $contributorSlugger->generateUnique($contributor);
            $contributor->setSlug($slug);
            $entity = $contributor;
        }

        $em->persist($entity);
        $em->flush();

        return $this->json([
            'id'    => method_exists($entity, 'getId') ? $entity->getId() : null,
            'label' => $name,
        ], 201);
    }

    private function searchBooks(BookRepository $bookRepository, string $q): array
    {
        $books = $bookRepository->findByTitleLike($q, 10);
        return array_map(fn ($b) => ['id' => $b->getId(), 'label' => $b->getTitle()], $books);
    }

    private function searchEditors(EntityManagerInterface $em, string $q): array
    {
        $editors = $em->getRepository(Editor::class)
            ->createQueryBuilder('e')
            ->where('LOWER(e.name) LIKE LOWER(:q)')
            ->setParameter('q', '%' . $q . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return array_map(fn ($e) => ['id' => $e->getId(), 'label' => $e->getName()], $editors);
    }

    private function searchCollections(CollectionRepository $collectionRepository, string $q): array
    {
        $collections = $collectionRepository->createQueryBuilder('c')
            ->where('LOWER(c.nom) LIKE LOWER(:q)')
            ->setParameter('q', '%' . $q . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return array_map(fn ($c) => ['id' => $c->getId(), 'label' => $c->getNom()], $collections);
    }

    private function searchContributors(ContributorRepository $contributorRepository, string $q): array
    {
        $contributors = $contributorRepository->createQueryBuilder('c')
            ->where("LOWER(CONCAT(c.firstName, ' ', c.lastName)) LIKE LOWER(:q)")
            ->setParameter('q', '%' . $q . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return array_map(fn ($c) => [
            'id'    => $c->getId(),
            'label' => $c->getFirstName() . ' ' . $c->getLastName(),
        ], $contributors);
    }
}
