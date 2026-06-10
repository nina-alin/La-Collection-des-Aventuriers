# Research: 024-createurs-page

## R-001 — Book → Collection join path for "top 2 collections per contributor"

**Decision**: Use `Contributor → contributions (Contribution) → book (Book) → collection (Collection)` join chain with GROUP BY.

**Rationale**: `Book` has `#[ORM\ManyToOne(targetEntity: CollectionEntity::class)]` (`collection_id` FK, `idx_book_collection_id` index already exists). The query is: `SELECT c, col, COUNT(DISTINCT b.id) AS cnt FROM Contributor c JOIN c.contributions contrib JOIN contrib.book b JOIN b.collection col GROUP BY c.id, col.id ORDER BY cnt DESC`. Top 2 per contributor resolved in PHP (slice after sort). Tie-breaking by `col.id` UUID v7 descending (highest UUID = most recent collection).

**Alternatives considered**: Denormalized `mainCollection` field on Contributor — rejected (spec says no new schema).

---

## R-002 — Available alphabet letters computation

**Decision**: `ContributeurService::getAvailableLetters(ContributorFilterState)` runs a single DB query: `SELECT DISTINCT UPPER(SUBSTRING(c.lastName, 1, 1)) AS letter FROM Contributor c [JOINs for active filters] WHERE [filter conditions] ORDER BY letter ASC`. Returns `string[]` included in the main controller payload (no separate AJAX endpoint).

**Rationale**: Spec FR-011 — "pas d'endpoint AJAX séparé, pas de dérivation client-side". Query re-uses same filter conditions as the paginated grid query. One extra DB round-trip per page load (acceptable; can be cached later).

**Alternatives considered**: Client-side derivation from current page results — rejected (only shows letters present on current page, not across all pages). Separate AJAX endpoint — rejected by spec.

---

## R-003 — Skeleton cards during Turbo Drive navigation

**Decision**: Render `N` skeleton card `<div>` elements (where N = count of cards on current page, defaulting to 12) inside the results grid as a Turbo cached snapshot. Turbo Drive displays the cached (skeleton) snapshot during navigation to filtered/sorted/paginated URLs. Skeleton cards: circle div + 2 text-line rects + footer band, animated with `@keyframes pulse` (opacity 0.5→1→0.5, 1.4s), using `var(--bg-sunken)` color.

**Rationale**: Spec FR-028 exactly describes this mechanism. Turbo Drive `data-turbo-action="replace"` on pagination/filter links (same as Catalogue). The skeleton count matches previous page card count, stored in a `<meta name="createurs-count">` tag updated each render.

**Alternatives considered**: LiveComponent re-render with loading state — rejected (spec says "même architecture que la page Catalogue", which uses controller redirect + Turbo Drive, not LiveComponent for the grid).

---

## R-004 — Autocomplete endpoint + concurrent request handling

**Decision**: `GET /createurs/search?q=` returns JSON array grouped by role (max 5 per role). Client-side: Stimulus controller `createurs_controller.js` uses `AbortController` — each new keystroke aborts the previous fetch before issuing the next. Debounce 250ms.

**Rationale**: Spec FR-005b — "seule la réponse de la dernière requête envoyée DOIT être appliquée". AbortController is the canonical browser API for this. Debounce 200–300ms → 250ms chosen as midpoint.

**Response shape per result**: `{slug, firstName, lastName, portraitImage: null|string, role: string, bookCount: int, mainCollection: null|string, averageScore: null|float}`.

**Alternatives considered**: Promise chaining with sequence counter — rejected (AbortController is cleaner, cancels network request outright).

---

## R-005 — ContributorFilterState DTO design

**Decision**: New `readonly` DTO `App\Dto\ContributorFilterState` mirroring `ActiveFilterState` pattern. Properties: `role: string ('tous'|'auteur'|'traducteur'|'illustrateur')`, `letter: ?string (A–Z)`, `collectionIds: int[]`, `periodMin: ?int`, `periodMax: ?int`, `nationality: ?string`, `bookCountRange: ?string ('1-5'|'6-15'|'16-30'|'30+')`, `onlyFollowed: bool`, `sort: string ('az'|'ouvrages'|'note')`, `page: int`. Static `fromRequest(Request)` factory + `toUrlParams(): array` method. `page` resets to 1 on any filter/sort change (enforced in `toUrlParams` when called from filter actions).

**Rationale**: URL-reflected state (FR-025). Same pattern as `ActiveFilterState` — proven in Catalogue. `onlyFollowed` silently ignored for unauthenticated users (filter is hidden in UI, FR-014).

---

## R-006 — ContributeurService responsibilities

**Decision**: Service handles: (1) `getPaginatedResults(ContributorFilterState, ?User): Paginator`, (2) `getAvailableLetters(ContributorFilterState): string[]`, (3) `getContributorCardData(int[] $ids): array` — maps contributor IDs to precomputed [bookCount, averageScore, topCollections[], roles[]].

**Rationale**: Constitution Principle II — business logic in services. Controller stays thin: parse request → call service → pass to template.

**Alternatives considered**: Repository doing all computation — rejected (would bloat repository with non-data-access logic).

---

## R-007 — View toggle (grille/liste) persistence

**Decision**: Stimulus controller reads/writes `localStorage` key `lca-createurs-view` (`'grid'`|`'list'`). On page load, applies the stored class to the grid container before first paint (to avoid flash). No URL param (spec FR-025 explicitly excludes view from URL).

**Rationale**: Spec assumption — "L'état de la vue sélectionnée (grille/liste) EST persisté dans localStorage (`lca-createurs-view`)".

---

## R-008 — No migration required

**Decision**: No new entities. All required entities exist: `Contributor`, `Contribution`, `ContributionRole` (enum), `Collection`, `Review`. No schema change needed.

**Rationale**: Spec Key Entities section confirms all entities are existing. `UserFollowing` is out of scope.
