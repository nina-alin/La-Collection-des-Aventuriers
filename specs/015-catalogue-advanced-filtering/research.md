# Research: Catalogue Page & Advanced Filtering

**Branch**: `015-catalogue-advanced-filtering` | **Date**: 2026-06-01

All unknowns resolved from spec clarifications, codebase inspection, and existing feature patterns.

---

## Decision: Live Component architecture — filter panel only, not full page

**Decision**: Single `CatalogueFilterPanelComponent` (Live Component) handles draft state, live COUNT query, and editor search. The results grid is rendered by `CatalogueController` via standard Twig (not a Live Component). "Appliquer" fires a `#[LiveAction]` that returns a `RedirectResponse` to the URL with serialized filter params; Turbo Drive handles navigation transparently.

**Rationale**:
- Spec confirms "the results grid does NOT re-render until 'Appliquer' is clicked" — the Live Component only re-renders the filter panel + COUNT button, not the full grid.
- Chip removal, sort change, pagination → standard GET links/forms that update URL params → fast Turbo Drive navigation → no Live Component overhead.
- A `#[LiveAction]` returning `RedirectResponse` is the established Symfony UX pattern for commit-and-navigate.
- Avoids a god component managing the entire page; re-renders are cheap (panel only, ~0.3 s debounce).

**Alternatives considered**:
- Single `CatalogueComponent` wrapping full page: rejected — every editor-search keystroke re-renders entire page (expensive).
- Two coordinated Live Components (`FilterPanel` + `CatalogueResults`) with `emit()`: rejected as overly complex; Turbo Drive navigation gives URL-based deep linking for free without inter-component wiring.

---

## Decision: Doctrine `Paginator` (not KnpPaginator)

**Decision**: Use `Doctrine\ORM\Tools\Pagination\Paginator` (already used at `CollectionRepository::paginateBooksForCollection` and `ReviewRepository::findPaginatedByBook`).

**Rationale**: Already a project pattern; no new dependency. KnpPaginatorBundle is not installed.

---

## Decision: `UserBook` entity introduced in this feature

**Decision**: Create `UserBook` entity + `UserBookStatus` enum as part of this feature. The spec assumption "filter data available from prior features (006, 009)" is aspirational; no user-book status tracking entity exists in the codebase today.

**Rationale**:
- Without `UserBook`, the "STATUT DANS MA COLLECTION" filter (FR-021) and card marks (FR-026: owned/favourite/wishlist) cannot function.
- The entity is simple (status enum + isFavorite bool + FK to User + FK to Book).
- No infrastructure changes required (pure Doctrine ORM → single migration).
- Write operations (user marking a book) are OUT OF SCOPE for this feature; the entity is created with schema and fixtures for dev display only.

**Alternatives considered**: Defer `UserBook` to a separate feature — rejected because FR-021 (P1) and FR-026 card marks are explicitly in this feature's spec.

---

## Decision: `ActiveFilterState` as an immutable readonly DTO

**Decision**: `src/Dto/ActiveFilterState.php` — PHP readonly class hydrated from URL query params by `CatalogueController`, passed to Twig template and used to mount the Live Component.

**Rationale**: Clean separation between HTTP layer (URL params) and domain (filter queries). The DTO is the single source of truth for applied filters. Serializes back to URL params deterministically via `toUrlParams(): array`.

**Live Component draft props**: The `CatalogueFilterPanelComponent` holds draft state as individual `#[LiveProp(writable: true)]` scalar fields — not a nested DTO — because Symfony UX Live Component 2.36 does not natively support nested writable DTO LiveProps.

---

## Decision: Debounce via Twig `data-debounce` attribute

**Decision**: 300 ms debounce applied via `data-action="live#update" data-debounce="300"` on the editor search input. The same 300 ms debounce on any LiveProp mutation that drives the COUNT re-render (Symfony UX Live Component 2.36 standard mechanism).

---

## Decision: URL query param schema

**Decision**: Filter state serializes to URL params as follows (see `contracts/url-filter-params.md` for full spec):
- `sort` — string slug: `note-desc` | `alpha` | `parution-fr` | `parution-orig` | `recent`
- `editors[]` — array of integer editor IDs
- `paragraphMin` / `paragraphMax` — integers
- `collectionStatus` — string slug (ignored for guests)
- `onlyFavorites` — `1` when active
- `hideModeration` — `1` when active
- `q` — search query string
- `page` — integer, 1-indexed

**Rationale**: Standard HTTP query string conventions; compatible with `$request->query->all()` Symfony parsing; readable and shareable URLs.

---

## Decision: Sort toolbar — `replaceState`, not `pushState`

**Decision**: Sort-only changes from the toolbar replace the current browser history entry (no new entry); all other filter changes (Appliquer, chip removal, TOUT EFFACER, pagination) push a new entry. Implemented via Turbo Drive `data-turbo-action="replace"` on sort form submit.

**Rationale**: A sort change is a refinement of the current view, not navigation to a new context. Back button skipping sort changes is the expected UX.

---

## Decision: Paragraph bounds — DQL MIN/MAX at page load

**Decision**: `BookRepository::findParagraphBounds()` runs one DQL query at page load: `SELECT MIN(b.paragraphs), MAX(b.paragraphs) FROM book b WHERE b.status = :published`. Fallback on failure: `[0, 999]` with non-blocking error toast. Preset pills outside the dynamic range are hidden at render time (spec FR-020).

---

## Decision: `hideModeration` filter implementation

**Decision**: The catalogue default query already restricts to `book.status = PUBLISHED`. The "Cacher les fiches en modération" toggle maps to an additional `AND b.status = PUBLISHED` which is a no-op in normal conditions. It is implemented as a boolean prop in `ActiveFilterState` and `FilterDraftState` for spec compliance; the DQL builder always applies `status = PUBLISHED` so the toggle has no additional SQL effect.

**Rationale**: Spec FR-021 requires the toggle to exist in the UI. For `ROLE_MODERATOR` users who might otherwise see PENDING books in a future feature, this toggle would then be meaningful. Safe to include as a no-op now.

---

## Decision: Editor search — `EditorRepository` (may be new file)

**Decision**: Check for `src/Repository/EditorRepository.php`. The `Editor` entity uses `#[ORM\Entity]` without `repositoryClass` — no custom repository exists. Create `EditorRepository` as part of this feature, add `repositoryClass: EditorRepository::class` to `Editor` entity attribute, add `findByNameSearch()` and `findWithBookCount()` methods.

---

## Decision: In-page autocomplete endpoint

**Decision**: New route `GET /catalogue/search-suggestions?q={query}` returns JSON `{books: [{id, title, author}], authors: [{id, name}]}`. Handled by `CatalogueController::searchSuggestions()`. Uses existing `BookRepository::findForGlobalSearch()` pattern scoped to `status = PUBLISHED`. Separate from global omnibox (feature 012) per FR-006.

---

## Codebase findings

- `/catalogue` route exists in `DefaultController::catalogue()` — returns `home/index.html.twig` (stub). Will be replaced by new `CatalogueController`.
- `DefaultController` route will be removed.
- Existing `Book/Card` Twig component at `src/Twig/Components/Book/Card.php` lacks `isFavorite`, `isOwned`, `isWishlist` props — needs modification.
- Existing Stimulus controllers for patterns: `search_controller.js` (global search), `modal_controller.js` (generic modal), `toast_controller.js` / `toast-container_controller.js` (toasts already available for error feedback).
- `Doctrine\ORM\Tools\Pagination\Paginator` import confirmed in `ReviewRepository` and `CollectionRepository`.
- `symfony/ux-live-component: ^2.36` confirmed in `composer.json`.
- Only one existing Live Component: `src/Twig/Components/Suggestion/WizardComponent.php` — used as the implementation pattern.
