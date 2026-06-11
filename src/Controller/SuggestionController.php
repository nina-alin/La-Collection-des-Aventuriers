<?php

namespace App\Controller;

use App\Entity\Collection;
use App\Entity\Contributor;
use App\Entity\Editor;
use App\Entity\Enum\ContributionRole;
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
        Request $request,
        SuggestionService $suggestionService,
        ContributorLevelService $contributorLevelService,
        BookRepository $bookRepository,
        ContributorRepository $contributorRepository,
        CollectionRepository $collectionRepository,
        EntityManagerInterface $em,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $metrics = $contributorLevelService->getMetrics($user);

        $prefill = null;
        $entityTypeParam = $request->query->get('entityType');
        $entityIdParam   = $request->query->get('entityId');

        if ($entityTypeParam !== null && $entityIdParam !== null) {
            $prefill = $this->buildPrefill($entityTypeParam, $entityIdParam, $bookRepository, $contributorRepository, $collectionRepository, $em);
        }

        return $this->render('suggestion/index.html.twig', [
            'metrics' => $metrics,
            'prefill' => $prefill,
        ]);
    }

    private function buildPrefill(
        string $entityType,
        string $entityId,
        BookRepository $bookRepository,
        ContributorRepository $contributorRepository,
        CollectionRepository $collectionRepository,
        EntityManagerInterface $em,
    ): ?array {
        return match ($entityType) {
            'BOOK'                          => $this->buildBookPrefill($entityId, $bookRepository),
            'AUTHOR', 'ILLUSTRATOR', 'TRADUCTOR' => $this->buildContributorPrefill($entityType, $entityId, $contributorRepository),
            'EDITOR'                        => $this->buildEditorPrefill($entityId, $em),
            'COLLECTION'                    => $this->buildCollectionPrefill($entityId, $collectionRepository),
            default                         => null,
        };
    }

    private function buildBookPrefill(string $entityId, BookRepository $bookRepository): ?array
    {
        $book = $bookRepository->find((int) $entityId);
        if ($book === null) {
            return null;
        }

        $contributions = $book->getContributions()->toArray();
        $authors       = array_values(array_filter($contributions, fn ($c) => $c->getRole() === ContributionRole::Author));
        $illustrators  = array_values(array_filter($contributions, fn ($c) => $c->getRole() === ContributionRole::Illustrator));
        $traductors    = array_values(array_filter($contributions, fn ($c) => $c->getRole() === ContributionRole::Traductor));

        $name = static fn (?object $c): string => $c !== null
            ? trim($c->getContributor()->getFirstName() . ' ' . $c->getContributor()->getLastName())
            : '';

        return [
            'entityType' => 'BOOK',
            'entityId'   => null,
            'formData'   => [
                'title'           => $book->getTitle() ?? '',
                'subtitle'        => '',
                'author'          => $name($authors[0] ?? null),
                'illustrator'     => $name($illustrators[0] ?? null),
                'traductor'       => $name($traductors[0] ?? null),
                'editor'          => $book->getEditor()?->getName() ?? '',
                'collection'      => $book->getCollection()?->getNom() ?? '',
                'isbn'            => $book->getIsbn() ?? '',
                'publicationFr'   => (string) ($book->getFrenchPublicationYear() ?? ''),
                'originalEdition' => (string) ($book->getOriginalPublicationYear() ?? ''),
                'paragraphs'      => (string) ($book->getParagraphs() ?? ''),
                'backCoverText'   => $book->getSummary() ?? '',
            ],
        ];
    }

    private function buildContributorPrefill(string $entityType, string $entityId, ContributorRepository $contributorRepository): ?array
    {
        $contributor = $contributorRepository->find($entityId);
        if ($contributor === null) {
            return null;
        }

        return [
            'entityType' => $entityType,
            'entityId'   => (string) $contributor->getId(),
            'formData'   => [
                'firstName'   => $contributor->getFirstName() ?? '',
                'lastName'    => $contributor->getLastName() ?? '',
                'pseudo'      => $contributor->getPseudo() ?? '',
                'biography'   => $contributor->getBiography() ?? '',
                'nationality' => $contributor->getNationality() ?? '',
                'birthDate'   => $contributor->getBirthDate()?->format('Y-m-d') ?? '',
                'deathDate'   => $contributor->getDeathDate()?->format('Y-m-d') ?? '',
            ],
        ];
    }

    private function buildEditorPrefill(string $entityId, EntityManagerInterface $em): ?array
    {
        $editor = $em->getRepository(Editor::class)->find((int) $entityId);
        if ($editor === null) {
            return null;
        }

        return [
            'entityType' => 'EDITOR',
            'entityId'   => null,
            'formData'   => [
                'name' => $editor->getName() ?? '',
            ],
        ];
    }

    private function buildCollectionPrefill(string $entityId, CollectionRepository $collectionRepository): ?array
    {
        $collection = $collectionRepository->find($entityId);
        if ($collection === null) {
            return null;
        }

        return [
            'entityType' => 'COLLECTION',
            'entityId'   => (string) $collection->getId(),
            'formData'   => [
                'name' => $collection->getNom() ?? '',
            ],
        ];
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

    #[Route('/api/suggestions/source-search', name: 'app_suggestions_source_search', methods: ['GET'])]
    public function sourceSearch(
        Request $request,
        BookRepository $bookRepository,
        ContributorRepository $contributorRepository,
        CollectionRepository $collectionRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $q     = trim((string) $request->query->get('q', ''));
        $scope = strtoupper((string) $request->query->get('scope', 'ALL'));

        $validScopes = ['ALL', 'BOOK', 'AUTHOR', 'ILLUSTRATOR', 'TRADUCTOR', 'COLLECTION', 'EDITOR'];
        if (!in_array($scope, $validScopes, true)) {
            $scope = 'ALL';
        }

        if (mb_strlen($q) > 100) {
            return $this->json(['results' => []]);
        }

        $results = [];

        if ($q === '') {
            if ($scope === 'ALL' || $scope === 'BOOK') {
                foreach ($bookRepository->findMostPopular($scope === 'ALL' ? 6 : 10) as $book) {
                    $contributions = $book->getContributions()->toArray();
                    $authors = array_values(array_filter($contributions, fn ($c) => $c->getRole() === \App\Entity\Enum\ContributionRole::Author));
                    $authorName = !empty($authors) ? trim($authors[0]->getContributor()->getFirstName() . ' ' . $authors[0]->getContributor()->getLastName()) : '';
                    $results[] = [
                        'id'       => (string) $book->getId(),
                        'type'     => 'BOOK',
                        'label'    => $book->getTitle(),
                        'subtitle' => implode(' · ', array_filter([$authorName, $book->getFrenchPublicationYear()])),
                        'thumb'    => $book->getCoverImage(),
                    ];
                }
            }

            if ($scope === 'ALL' || $scope === 'AUTHOR') {
                $contributors = $contributorRepository->createQueryBuilder('c')
                    ->join('c.contributions', 'contrib')
                    ->where('contrib.role = :role')
                    ->setParameter('role', \App\Entity\Enum\ContributionRole::Author->value)
                    ->groupBy('c.id')
                    ->orderBy('c.id', 'ASC')
                    ->setMaxResults($scope === 'ALL' ? 3 : 10)
                    ->getQuery()->getResult();
                foreach ($contributors as $c) {
                    $results[] = ['id' => (string) $c->getId(), 'type' => 'AUTHOR', 'label' => $c->getFirstName() . ' ' . $c->getLastName(), 'subtitle' => '', 'thumb' => null];
                }
            }

            if ($scope === 'ALL' || $scope === 'ILLUSTRATOR') {
                $contributors = $contributorRepository->createQueryBuilder('c')
                    ->join('c.contributions', 'contrib')
                    ->where('contrib.role = :role')
                    ->setParameter('role', \App\Entity\Enum\ContributionRole::Illustrator->value)
                    ->groupBy('c.id')
                    ->orderBy('c.id', 'ASC')
                    ->setMaxResults($scope === 'ALL' ? 3 : 10)
                    ->getQuery()->getResult();
                foreach ($contributors as $c) {
                    $results[] = ['id' => (string) $c->getId(), 'type' => 'ILLUSTRATOR', 'label' => $c->getFirstName() . ' ' . $c->getLastName(), 'subtitle' => '', 'thumb' => null];
                }
            }

            if ($scope === 'ALL' || $scope === 'TRADUCTOR') {
                $contributors = $contributorRepository->createQueryBuilder('c')
                    ->join('c.contributions', 'contrib')
                    ->where('contrib.role = :role')
                    ->setParameter('role', \App\Entity\Enum\ContributionRole::Traductor->value)
                    ->groupBy('c.id')
                    ->orderBy('c.id', 'ASC')
                    ->setMaxResults($scope === 'ALL' ? 3 : 10)
                    ->getQuery()->getResult();
                foreach ($contributors as $c) {
                    $results[] = ['id' => (string) $c->getId(), 'type' => 'TRADUCTOR', 'label' => $c->getFirstName() . ' ' . $c->getLastName(), 'subtitle' => '', 'thumb' => null];
                }
            }

            if ($scope === 'ALL' || $scope === 'COLLECTION') {
                $collections = $collectionRepository->createQueryBuilder('c')
                    ->orderBy('c.id', 'ASC')
                    ->setMaxResults($scope === 'ALL' ? 3 : 10)
                    ->getQuery()->getResult();
                foreach ($collections as $col) {
                    $results[] = ['id' => (string) $col->getId(), 'type' => 'COLLECTION', 'label' => $col->getNom(), 'subtitle' => '', 'thumb' => null];
                }
            }

            if ($scope === 'ALL' || $scope === 'EDITOR') {
                $editors = $em->getRepository(\App\Entity\Editor::class)
                    ->createQueryBuilder('e')
                    ->orderBy('e.id', 'ASC')
                    ->setMaxResults($scope === 'ALL' ? 3 : 10)
                    ->getQuery()->getResult();
                foreach ($editors as $editor) {
                    $results[] = ['id' => (string) $editor->getId(), 'type' => 'EDITOR', 'label' => $editor->getName(), 'subtitle' => '', 'thumb' => null];
                }
            }

            return $this->json(['results' => $results, 'default' => true]);
        }

        if ($scope === 'ALL' || $scope === 'BOOK') {
            foreach ($bookRepository->findByTitleLike($q, 8) as $book) {
                $contributions = $book->getContributions()->toArray();
                $authors = array_values(array_filter($contributions, fn ($c) => $c->getRole() === \App\Entity\Enum\ContributionRole::Author));
                $authorName = !empty($authors) ? trim($authors[0]->getContributor()->getFirstName() . ' ' . $authors[0]->getContributor()->getLastName()) : '';
                $results[] = [
                    'id'       => (string) $book->getId(),
                    'type'     => 'BOOK',
                    'label'    => $book->getTitle(),
                    'subtitle' => implode(' · ', array_filter([$authorName, $book->getFrenchPublicationYear(), $book->getIsbn()])),
                    'thumb'    => $book->getCoverImage(),
                ];
            }
        }

        $nameSearch = "LOWER(CONCAT(c.firstName, ' ', c.lastName)) LIKE LOWER(:q)";

        if ($scope === 'AUTHOR') {
            $contributors = $contributorRepository->createQueryBuilder('c')
                ->join('c.contributions', 'contrib')
                ->where($nameSearch)
                ->andWhere('contrib.role = :role')
                ->setParameter('q', '%' . $q . '%')
                ->setParameter('role', \App\Entity\Enum\ContributionRole::Author->value)
                ->groupBy('c.id')
                ->setMaxResults(8)->getQuery()->getResult();
            foreach ($contributors as $c) {
                $results[] = ['id' => (string) $c->getId(), 'type' => 'AUTHOR', 'label' => $c->getFirstName() . ' ' . $c->getLastName(), 'subtitle' => '', 'thumb' => null];
            }
        } elseif ($scope === 'ILLUSTRATOR') {
            $contributors = $contributorRepository->createQueryBuilder('c')
                ->join('c.contributions', 'contrib')
                ->where($nameSearch)
                ->andWhere('contrib.role = :role')
                ->setParameter('q', '%' . $q . '%')
                ->setParameter('role', \App\Entity\Enum\ContributionRole::Illustrator->value)
                ->groupBy('c.id')
                ->setMaxResults(8)->getQuery()->getResult();
            foreach ($contributors as $c) {
                $results[] = ['id' => (string) $c->getId(), 'type' => 'ILLUSTRATOR', 'label' => $c->getFirstName() . ' ' . $c->getLastName(), 'subtitle' => '', 'thumb' => null];
            }
        } elseif ($scope === 'TRADUCTOR') {
            $contributors = $contributorRepository->createQueryBuilder('c')
                ->join('c.contributions', 'contrib')
                ->where($nameSearch)
                ->andWhere('contrib.role = :role')
                ->setParameter('q', '%' . $q . '%')
                ->setParameter('role', \App\Entity\Enum\ContributionRole::Traductor->value)
                ->groupBy('c.id')
                ->setMaxResults(8)->getQuery()->getResult();
            foreach ($contributors as $c) {
                $results[] = ['id' => (string) $c->getId(), 'type' => 'TRADUCTOR', 'label' => $c->getFirstName() . ' ' . $c->getLastName(), 'subtitle' => '', 'thumb' => null];
            }
        } elseif ($scope === 'ALL') {
            $contributors = $contributorRepository->createQueryBuilder('c')
                ->where($nameSearch)
                ->setParameter('q', '%' . $q . '%')
                ->setMaxResults(6)->getQuery()->getResult();
            foreach ($contributors as $c) {
                $results[] = ['id' => (string) $c->getId(), 'type' => 'AUTHOR', 'label' => $c->getFirstName() . ' ' . $c->getLastName(), 'subtitle' => '', 'thumb' => null];
            }
        }

        if ($scope === 'ALL' || $scope === 'COLLECTION') {
            $collections = $collectionRepository->createQueryBuilder('c')
                ->where('LOWER(c.nom) LIKE LOWER(:q)')
                ->setParameter('q', '%' . $q . '%')
                ->setMaxResults(6)->getQuery()->getResult();
            foreach ($collections as $col) {
                $results[] = ['id' => (string) $col->getId(), 'type' => 'COLLECTION', 'label' => $col->getNom(), 'subtitle' => '', 'thumb' => null];
            }
        }

        if ($scope === 'ALL' || $scope === 'EDITOR') {
            $editors = $em->getRepository(\App\Entity\Editor::class)
                ->createQueryBuilder('e')
                ->where('LOWER(e.name) LIKE LOWER(:q)')
                ->setParameter('q', '%' . $q . '%')
                ->setMaxResults(6)->getQuery()->getResult();
            foreach ($editors as $editor) {
                $results[] = ['id' => (string) $editor->getId(), 'type' => 'EDITOR', 'label' => $editor->getName(), 'subtitle' => '', 'thumb' => null];
            }
        }

        return $this->json(['results' => $results]);
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
