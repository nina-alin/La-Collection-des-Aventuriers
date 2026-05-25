# Quickstart: Entité Collection et Vue Détail

**Feature**: `006-collection-entity` | **Date**: 2026-05-25

## Prerequisites

- Branch `005-book-detail-view` merged into `master` (blocking dependency — `Book` entity must exist)
- `docker compose up -d` (or local Symfony server running)
- `composer install` done

Verify: `git log --oneline master | grep 005` → should show merge commit.

## Implementation Order

Follow strictly — each step depends on the previous.

### Step 1 — New Enums

Create `src/Entity/Enum/GenreCollection.php` and `src/Entity/Enum/StatutCollection.php` as backed string enums (see `data-model.md`).

### Step 2 — Collection Entity

Create `src/Entity/Collection.php` with:
- UUID id (same pattern as `CorrectionProposal`)
- All fields from data-model
- `#[ORM\PrePersist]` / `#[ORM\PreUpdate]` hooks OR inject `CollectionSlugger` into a Doctrine listener
- `OneToMany` to `Book`
- Symfony validator constraints

### Step 3 — CollectionSlugger Service

Create `src/Service/CollectionSlugger.php` injecting `SluggerInterface` and `CollectionRepository`. Implements collision-suffix logic (see research.md Decision 1).

### Step 4 — CollectionRepository

Create `src/Repository/CollectionRepository.php` with:
- `findBySlug(string $slug): ?Collection`
- `paginateBooksForCollection(Collection $c, int $page, int $perPage = 20): Paginator` — uses Doctrine Paginator, ORDER BY `CASE WHEN volumeNumber IS NULL THEN 1 ELSE 0 END ASC, volumeNumber ASC, title ASC`

### Step 5 — Update Book Entity

Add `ManyToOne` relation to `Collection` (nullable, `ON DELETE SET NULL`), add index annotation on `collection_id`.
Update `BookRepository::findBySlugWithRelations` to `leftJoin` the collection.

### Step 6 — Migration

```bash
php bin/console doctrine:migrations:diff
```
Review generated migration. Ensure it contains:
- `CREATE TABLE collection` with all columns + indexes
- `ALTER TABLE book ADD COLUMN collection_id UUID DEFAULT NULL`
- FK constraint with `ON DELETE SET NULL`
- `CREATE INDEX idx_book_collection_id`
- Correct `down()` method

```bash
php bin/console doctrine:migrations:migrate
```

### Step 7 — CollectionController

Create `src/Controller/CollectionController.php`:
```php
#[Route('/collections/{slug}', name: 'app_collection_show', methods: ['GET'])]
public function show(string $slug, Request $request, CollectionRepository $repo): Response
{
    $collection = $repo->findBySlug($slug);
    if ($collection === null) { throw new NotFoundHttpException(); }

    $page = max(1, (int) $request->query->get('page', 1));
    // Validate page is a positive integer
    $rawPage = $request->query->get('page', '1');
    if (!ctype_digit((string) ltrim($rawPage, '0')) || (int) $rawPage < 1) {
        throw new NotFoundHttpException();
    }

    $books = $repo->paginateBooksForCollection($collection, $page);
    $totalBooks = count($books);
    $totalPages = $totalBooks > 0 ? (int) ceil($totalBooks / 20) : 1;

    if ($page > $totalPages) { throw new NotFoundHttpException(); }

    return $this->render('collection/show.html.twig', [
        'collection' => $collection,
        'books'      => $books,
        'currentPage' => $page,
        'totalPages'  => $totalPages,
        'totalBooks'  => $totalBooks,
    ]);
}
```

### Step 8 — Update security.yaml

Add `- { path: ^/collections/, roles: PUBLIC_ACCESS }` before the catch-all rule.

### Step 9 — Twig Templates

Create `templates/collection/show.html.twig` (see contracts/routes.md for variable list).
Update `templates/livre/show.html.twig`:
- Breadcrumb: add collection segment if `book.collection` not null
- Saga/Volume eyebrow: make collection name a link if `book.collection` not null
- Meta row "Saga & Volume": wrap collection name in `<a href="/collections/{slug}">`

### Step 10 — CSS Badges

Add to `assets/styles/components/_badges.scss`:
```scss
// genre
.badge-genre-medieval-fantastique { ... }
.badge-genre-science-fiction      { ... }
.badge-genre-horreur              { ... }
.badge-genre-espionnage           { ... }
.badge-genre-aventure             { ... }
.badge-genre-contemporain         { ... }
// statut
.badge-statut-en-cours  { ... }
.badge-statut-terminee  { ... }
.badge-statut-reeditee  { ... }
```
Use existing semantic color tokens (`--warning-*`, `--success-*`, etc.) — see `_colors.scss`.

Create `assets/styles/pages/_collection.scss` and import it in `app.scss`.

### Step 11 — CollectionFactory + Fixtures

Create `src/DataFixtures/Factory/CollectionFactory.php` (Foundry). Defaults: all nullable fields set to `null`, `createurs: []`, `statut: StatutCollection::EN_COURS`.

### Step 12 — Tests

Create `tests/Functional/CollectionControllerTest.php` covering:
- GET `/collections/{existing-slug}` → 200
- GET `/collections/{missing-slug}` → 404
- GET `/collections/{slug}?page=2` with 25 books → 200, 5 books on page 2
- GET `/collections/{slug}?page=99` → 404
- GET `/collections/{slug}?page=abc` → 404
- GET `/collections/{slug}?page=0` → 404
- Collection with 0 books → "Aucun livre disponible"
- Logo present vs absent (placeholder check)

Create `tests/Functional/BookCollectionBreadcrumbTest.php` covering:
- Book with collection → breadcrumb has collection link
- Book without collection → breadcrumb `Catalogue / {titre}`
- Saga/Volume row with collection → link to `/collections/{slug}`

## Verify

```bash
php bin/console doctrine:schema:validate
php bin/phpunit
php bin/console cache:clear
symfony server:start
# Navigate to /collections/{slug} in browser
```

## Rollback

```bash
php bin/console doctrine:migrations:execute --down 'App\Migrations\VersionXXXXXX'
```
