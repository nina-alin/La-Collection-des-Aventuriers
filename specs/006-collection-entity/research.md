# Research: Entité Collection et Vue Détail

**Feature**: `006-collection-entity` | **Date**: 2026-05-25

## Decision 1: Slug Generation Strategy

**Decision**: Use `Symfony\Component\String\Slugger\SluggerInterface` (injected service) with a manual collision-suffix loop in a dedicated `CollectionSlugger` service. NOT Gedmo Sluggable.

**Rationale**: `Book` already uses Gedmo for its slug; the spec explicitly calls for `SluggerInterface` for `Collection` to remain independent of Gedmo lifecycle events. The collision suffix (`-2`, `-3`, …) must be checked against the database — Gedmo does not support this out of the box without custom listeners. A service is the cleanest boundary.

**Implementation pattern**:
```php
public function generateUnique(string $nom, ?string $currentSlug = null): string
{
    $base = $this->slugger->slug($nom)->lower()->toString();
    if ($currentSlug === $base || $this->repo->findOneBy(['slug' => $base]) === null) {
        return $base;
    }
    $i = 2;
    do {
        $candidate = $base . '-' . $i++;
    } while ($this->repo->findOneBy(['slug' => $candidate]) !== null);
    return $candidate;
}
```

**Alternatives considered**: Gedmo Sluggable (rejected — no built-in collision suffix, ties slug logic to ORM lifecycle); custom Doctrine listener (rejected — over-engineering for a single entity).

---

## Decision 2: Doctrine Paginator Pattern

**Decision**: Use `Doctrine\ORM\Tools\Pagination\Paginator` with a single DQL query that includes `ORDER BY` and `setFirstResult` / `setMaxResults`. Wrap in `CollectionRepository::paginateBooksForCollection()`.

**Rationale**: Paginator handles `COUNT` + `LIMIT/OFFSET` without N+1 for joined collections. SC-003 requires validation via Symfony Profiler. Query sorts by `volumeNumber ASC NULLS LAST, title ASC`.

**Implementation pattern**:
```php
public function paginateBooksForCollection(Collection $collection, int $page, int $perPage = 20): Paginator
{
    $qb = $this->em->createQueryBuilder()
        ->select('b')
        ->from(Book::class, 'b')
        ->where('b.collection = :collection')
        ->setParameter('collection', $collection)
        ->orderBy('b.volumeNumber', 'ASC')   // NULLS LAST via CASE in DQL or Paginator post-sort
        ->addOrderBy('b.title', 'ASC')
        ->setFirstResult(($page - 1) * $perPage)
        ->setMaxResults($perPage);

    return new Paginator($qb, fetchJoinCollection: false);
}
```

**Note on NULLS LAST**: PostgreSQL-specific `NULLS LAST` is not native DQL. Solution: use `CASE WHEN b.volumeNumber IS NULL THEN 1 ELSE 0 END ASC` as first ORDER BY, then `b.volumeNumber ASC`, then `b.title ASC`. This is portable DQL.

**Alternatives considered**: Manual `COUNT` query (rejected — two queries, error-prone); KnpPaginatorBundle (rejected — adds dependency, overkill for one endpoint).

---

## Decision 3: PHP 8.1 Backed Enums + Symfony Validator

**Decision**: `GenreCollection: string` and `StatutCollection: string` backed enums. Validation via `#[Assert\Choice(callback: [GenreCollection::class, 'cases'])]` (returns `UnitEnum[]`). For string comparison, use `choices` with the `value` map.

**Rationale**: `BookStatus` already uses this pattern in the project (`:string` backed enum, stored as VARCHAR). `#[Assert\Choice(callback: 'cases')]` works with backed enums in Symfony 6.2+.

**Correct validator pattern** (backed enum values, not enum instances):
```php
#[Assert\Choice(choices: ['medieval-fantastique', 'science-fiction', 'horreur', 'espionnage', 'aventure', 'contemporain'])]
```
Or using `callback` with a custom static method returning string values. The `cases()` method returns `UnitEnum[]` which the `Choice` constraint accepts directly in Symfony 6.2+.

**Alternatives considered**: Plain string columns (rejected — no type safety); Gedmo EnumType (rejected — adds dependency).

---

## Decision 4: Migration Strategy

**Decision**: Two-step migration in a single `up()`:
1. `CREATE TABLE collection (...)` with indexes on `slug` and `nom`.
2. `ALTER TABLE book ADD COLUMN collection_id UUID DEFAULT NULL` + FK constraint with `ON DELETE SET NULL` + index `idx_book_collection_id`.

`down()` reverses: drop FK → drop index → drop column → drop table.

**Rationale**: `SET NULL` required by FR-002. Index on `collection_id` required by FR-001 for join performance.

**Alternatives considered**: Cascade delete (rejected by spec); separate migrations for table vs column (rejected — atomic is simpler here).

---

## Decision 5: Pagination Out-of-Bounds → HTTP 404

**Decision**: In `CollectionController`, after Paginator query, if `$page < 1` or `$page > $totalPages` (and `$totalPages > 0`) → throw `NotFoundHttpException`. If `$page` param is not castable to a positive integer → throw `NotFoundHttpException`.

**Rationale**: Spec clarifications are explicit: `?page=abc` → 404, `?page=N > max` → 404, `?page=0` or `?page=-1` → 404.

**Implementation**: Route parameter typed as `int` via `#[MapQueryParameter]` or manual `(int) $request->query->get('page', 1)`. If `intval` of a non-numeric string is 0, treat as out-of-bounds → 404.

---

## Decision 6: SEO Canonical + Title Suffix (FR-012)

**Decision**: Pass `$page` to Twig template. In `<head>`:
- `<link rel="canonical" href="{{ path('app_collection_show', {slug: collection.slug}) }}">` on all pages (including page 1)
- `<title>` block: page 1 → `{nom}`, page N≥2 → `{nom} (page N)`

**Rationale**: Standard canonical pattern prevents duplicate content for `?page=1` and `?page=2+`. Spec says page 1 with or without `?page=1` doesn't get suffix.

---

## Resolved: No Outstanding NEEDS CLARIFICATION

All design questions resolved via spec clarifications (session 2026-05-25). No external research required beyond pattern decisions above.
