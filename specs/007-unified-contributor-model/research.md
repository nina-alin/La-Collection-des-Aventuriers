# Research: Unified Contributor Model

## 1. Slug Generation Strategy

**Decision**: Custom `ContributorSlugger` service + `ContributorListener` EntityListener.

**Rationale**: Gedmo `#[Slug]` concatenates fixed fields unconditionally — it cannot express "use pseudo if set, else firstName+lastName". `SluggerInterface` from `symfony/string` (already used by `CollectionSlugger`) handles transliteration (é→e, ü→u), hyphenation, and lowercasing. The EntityListener fires on prePersist/preUpdate to set the slug. This exactly mirrors the existing `CollectionSlugger` + `CollectionListener` pattern.

**Slug source rule**: `pseudo !== null ? pseudo : firstName . ' ' . lastName`

**Uniqueness**: append `-2`, `-3`, … until no collision found in `contributor` table (same strategy as `CollectionSlugger::generateUnique`).

**Alternatives considered**:
- Gedmo `#[Slug]` on `['pseudo', 'firstName', 'lastName']`: produces bad slugs when pseudo is null (empty segment included). Rejected.
- Lifecycle callbacks in entity: requires service injection through constructor — not idiomatic Symfony, untestable in isolation. Rejected.

---

## 2. Entity ID Type

**Decision**: UUID v7 (`Symfony\Component\Uid\Uuid::v7()`) generated in entity constructor; Doctrine column type `uuid` (via `symfony/bridge/doctrine`).

**Rationale**: Spec explicitly requires UUID. `symfony/uid` is already a project dependency. UUID v7 is time-ordered, which produces better DB index performance than v4. The Symfony Doctrine bridge registers the `uuid` Doctrine type transparently.

**Alternatives considered**:
- Integer auto-increment (existing pattern): spec requires UUID. Rejected.
- UUID v4 (random): worse index locality than v7. Rejected.

---

## 3. Soft-Delete Infrastructure

**Decision**: Gedmo `SoftDeleteable` on `Contributor`, `Contribution`, and `Book`. Enable `softdeleteable: true` in `stof_doctrine_extensions.yaml`. Cascade soft-delete (Book → Contribution) via `BookSoftDeleteListener` EntityListener that detects when `Book.deletedAt` transitions from `null` to non-null.

**Rationale**: Gedmo SoftDeleteable is provided by `stof/doctrine-extensions-bundle ^1.12` (Gedmo 3.x, Doctrine ORM 3 compatible). It registers a Doctrine filter that automatically appends `WHERE deletedAt IS NULL` to all queries. Cascade soft-delete across relations is NOT automatic — a custom EntityListener is needed to propagate the soft-delete to `Contribution` rows when a Book is soft-deleted (FR-007).

**FR-008 note**: Soft-deleting a Contributor does NOT cascade to its Contributions — the Gedmo filter will hide the Contributor from queries, causing profile routes to return 404 naturally (repository query finds no Contributor → null → 404). Contributions are preserved for potential data recovery.

**Scope note**: No admin CRUD ships in this feature, so soft-delete cannot be triggered in this iteration. The infrastructure satisfies FR-007/FR-008 for future admin work.

**Alternatives considered**:
- Manual `deletedAt` without Gedmo filter (User entity pattern): every repository query would need explicit `WHERE deletedAt IS NULL`. More error-prone at scale. Rejected for Contributor/Contribution.

---

## 4. Translator Entity Fate

**Decision**: Remove `Translator` entity, its table, and `Book.translator` relation. All three roles (Author, Illustrator, Traductor) are unified under Contributor/Contribution.

**Rationale**: Keeping `Translator` alongside `Contribution.Traductor` creates two parallel representations of the same role. The `/traductors/{slug}` profile route (FR-011) operates on Contributor entities — Translator data would be invisible there. The spec assumptions confirm no production data exists, so no data migration is needed.

**Impact on Book entity**: Remove `$translator` (ManyToOne → Translator), remove `$translatorId` column. Add `$contributions` OneToMany → Contribution.

**Alternatives considered**:
- Keep Translator in parallel: dual representation of the same concept. Rejected.

---

## 5. Unique Constraint on (contributor, book, role)

**Decision**: DB-level `#[ORM\UniqueConstraint(columns: ['contributor_id', 'book_id', 'role'])]` on Contribution + `#[UniqueEntity]` Symfony Validator for application-level error.

**Rationale**: DB constraint prevents race-condition duplicates; app-level validation returns a user-readable message (important for future admin UI). This satisfies FR-021.

---

## 6. N+1-Free Profile Route Query

**Decision**: `ContributorRepository::findBySlugAndRole()` uses a single DQL query with INNER JOIN FETCH to load Contributor + filtered Contributions + Books in one round-trip.

**Query shape**:
```dql
SELECT c, contrib, b
FROM App\Entity\Contributor c
INNER JOIN c.contributions contrib
INNER JOIN contrib.book b
WHERE c.slug = :slug
  AND contrib.role = :role
ORDER BY COALESCE(b.frenchPublicationYear, 9999) ASC, b.title ASC
```

Using INNER JOIN (not LEFT JOIN) means the query returns `null` when: (a) slug does not exist, OR (b) contributor has no contributions of the requested role. Both cases map to 404 — same controller handling for FR-012.

`COALESCE(b.frenchPublicationYear, 9999)` pushes books without a year to the end (FR-019 says ascending, null last is the sensible default).

**Alternatives considered**:
- Two queries (one for contributor, one for books): acceptable per FR-013 (max 2 queries) but produces the wrong 404 behaviour for case (b) above without extra application logic. Single query is cleaner. Rejected in favour of single query.
