# Research: Refonte Page Collection

All unknowns resolved from spec clarifications (2026-06-01) and codebase inspection.

---

## 1. Hue Palette for Tome Cards

**Decision**: Cycle through 6 named hues `['forest','storm','ember','amber','ink','gold']` using `(volumeNumber ?? 0) % 6` as index.

**Rationale**: The design defines exactly 6 CSS hue variants on `.tome[data-hue="…"]`. The spec says colors are deterministic based on `volumeNumber`; the design shows tomes 1–6 cycling through forest→storm→ember→amber→ink→gold in order. Cycle modulo 6 is the simplest deterministic mapping that matches the design.

**Alternatives considered**: HSL formula `hsl((n * goldenAngle) % 360)` — rejected per spec clarification: design file is authoritative.

---

## 2. Contributors Aggregation Query

**Decision**: Single DQL query on `Contribution` joined to `Contributor` and `Book`, filtered by `book.collection = :collection` and `contribution.deletedAt IS NULL` and `book.deletedAt IS NULL`. Groups by `(contributor.id, role)` to produce one row per (person, role) pair with count. Runs once per page load, entirely outside the paginated book fetch.

**Rationale**: Spec requires "requête dédiée SQL/DQL portant sur l'intégralité des tomes de la collection" (FR-018). Pagination for books is preserved unchanged — contributors query must bypass it.

**Implementation**:
```php
// ContributionRepository::findRecurringByCollection(Collection $collection): array
// Returns array of ['contributor' => Contributor, 'role' => ContributionRole, 'count' => int]
// Ordered by count DESC
SELECT co.contributor, co.role, COUNT(co.id) AS tomeCount
FROM Contribution co
JOIN co.book b
WHERE b.collection = :collection
  AND co.deletedAt IS NULL
  AND b.deletedAt IS NULL
GROUP BY co.contributor, co.role
ORDER BY tomeCount DESC
```

**Unique person count**: Derived in PHP from the result by collecting unique contributor IDs (a contributor with 2 roles counts as 1).

---

## 3. Average Rating (Hero Meta)

**Decision**: PHP-side computation in `CollectionService`. For each book on the current paginated list, compute `AVG(review.score)` per book (already available via `book.getReviews()`). Then average those per-book averages across all books that have at least one review. Display "–" if no books have any reviews.

**Correction**: Spec FR-002 says "moyenne des notes moyennes de chaque tome" and the calculation must cover all tomes (not just the current page) for the hero meta. The contributors query already fetches all tomes — for the hero rating, a dedicated aggregate DQL is cleaner.

**Revised decision**: Add `CollectionRepository::computeAverageRating(Collection): ?float` — one `AVG(AVG(r.score))` subquery or two-step AVG grouped by book. Returns null if no reviews.

**Rationale**: Avoids loading all review entities into memory; single DB round trip.

---

## 4. Publication Year Range (Hero Meta)

**Decision**: Computed via `CollectionRepository::getPublicationYearRange(Collection): array{min: ?int, max: ?int}` — a DQL query selecting MIN and MAX of `b.frenchPublicationYear` where value IS NOT NULL. Returns `['min' => null, 'max' => null]` if all tomes have no year. Display logic: if both null → "–"; if min === max → single year; otherwise "min–max".

---

## 5. Stimulus Controller: Sort & Filter

**Decision**: New controller `assets/controllers/collection-sort_controller.js`. Reads `data-volume` and `data-rating` attributes from each `.tome` element. On sort button click, stable-sorts the `.tomes-grid` children and re-appends them in new order. Filter pills ("Possédés", "Manquants") are rendered `disabled` and do nothing.

**Rationale**: NFR-003 mandates Symfony UX / Stimulus. The design's vanilla JS (visual-only demo) is replaced with a real Stimulus controller in the Twig template.

**Data attributes on each `.tome`**:
- `data-volume="<int>"` — volumeNumber (0 if null)
- `data-rating="<float|>"` — average score or empty string if no reviews

**Sort stability**: `Array.prototype.sort` is stable in modern browsers (V8/SpiderMonkey/WebKit); spec confirms stable sort required.

---

## 6. CollectionPublishingHistory Entity

**Decision**: New Doctrine entity with:
- `id`: UUID v4 (Symfony UID)
- `collection`: ManyToOne → Collection, `nullable: false`, `onDelete: 'CASCADE'`
- `editor`: ManyToOne → Editor, `nullable: true`, `onDelete: 'SET NULL'` (allows "éditeur inconnu" display when editor is deleted)
- `startYear`: `smallint`, NOT NULL
- `endYear`: `smallint`, nullable
- `editionName`: `varchar(255)`, nullable
- `details`: `text`, nullable

**Sort order**: `ORDER BY startYear ASC, id ASC` (spec FR-016).

**Conditional display**: Section rendered only when `count(publishingHistory) > 1` (spec FR-015).

**Rationale**: `editor` FK uses `onDelete: 'SET NULL'` not `CASCADE` so a deleted editor produces null (display as "(éditeur inconnu)") without losing the history row.

---

## 7. Initials Computation (Contributor Pills)

**Decision**: PHP Twig filter or inline logic:
- If `lastName` is non-empty: `strtoupper(mb_substr(firstName, 0, 1) . mb_substr(lastName, 0, 1))`
- If `lastName` is empty: `strtoupper(mb_substr(firstName, 0, 2))`

**Rationale**: Spec FR-020 and clarification: "JD" for Joe Dever; "Jo" for single-name "Joe".

---

## 8. Completion Section

**Decision**: Static hardcoded values in Twig: 42.8%, 12/28, 28 ticks (12 colored + 16 empty), text with `{{ collection.nom }}`.

**Rationale**: Spec FR-005 explicitly states values are intentionally static; full dynamization is a future ticket (debt).

---

## 9. Total Book Count

**Decision**: `CollectionRepository` already has `paginateBooksForCollection` which returns a `Paginator`. The paginator's `count()` gives total across all pages. This count is already passed to the template as `totalBooks`. No change needed.

---

## 10. Missing Edge Cases Confirmed

| Edge case | Handling |
|-----------|----------|
| `averageRating` null | Display "–" in hero meta and in each card |
| All `frenchPublicationYear` null | Hero meta: "–" |
| Some `frenchPublicationYear` null | min–max of known years |
| `volumeNumber` null | Use `0` as fallback for hue and sort |
| Empty collection | "LES TOMES — 0 VOLUMES", empty grid |
| Editor soft-deleted | `editor` FK is null → display "(éditeur inconnu)" |
| Two publishing history entries with same startYear | Sort by `id` ASC |
| Sort tie on rating | Stable sort preserves current order |
| Sort tie on volumeNumber | Stable sort preserves current order |
