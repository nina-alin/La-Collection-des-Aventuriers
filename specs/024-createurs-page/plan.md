# Implementation Plan: Page "Créateurs" — Galerie des Bâtisseurs

**Branch**: `024-createurs-page` | **Date**: 2026-06-09 | **Spec**: [spec.md](spec.md)

**Input**: `specs/024-createurs-page/spec.md`

## Summary

Build the `/createurs` gallery page: paginated grid (12/page) of contributors with role bar, A–Z index, and side filter panel — all using AND logic with URL-reflected state. Autocomplete endpoint at `GET /createurs/search?q=` with Stimulus AbortController debounce. View toggle (grid/list) persisted in localStorage. Skeleton card loading states via Turbo Drive snapshot. Architecture mirrors Catalogue (controller redirect + Turbo Drive + LiveComponent for filter panel; no LiveComponent for grid itself).

## Technical Context

**Language/Version**: PHP 8.2+

**Primary Dependencies**: Symfony 7.2, Doctrine ORM 3.6, UX LiveComponent 2.36, UX Turbo 2.36, UX TwigComponent 2.35, Stimulus 2.35, Webpack Encore, Bootstrap

**Storage**: PostgreSQL (Doctrine ORM — existing schema, no migrations)

**Testing**: PHPUnit (tests/Controller/, tests/Service/, tests/Repository/)

**Target Platform**: Platform.sh (web service)

**Project Type**: Symfony web application

**Performance Goals**: < 300ms p95 for gallery page; autocomplete < 150ms p95

**Constraints**: No new CSS frameworks; no new JS frameworks; no new entities; Bootstrap + project SCSS only; Twig engine only

**Scale/Scope**: Up to ~642 contributors (design shows "642"), paginated 12/page

## Constitution Check

| Principle | Status | Notes |
|-----------|--------|-------|
| I — Complémentarité | ✅ Pass | Gallery/filter page, not forum/news |
| II — Architecture Symfony LTS | ✅ Pass | Thin controller; `ContributeurService` handles all business logic; Doctrine ORM only |
| III — Workflow validation | ✅ Pass | Read-only page; no content submission; `Review` data only read via AVG |
| IV — RBAC | ✅ Pass | `/createurs` and `/createurs/search` are public GET routes; `onlyFollowed` filter silently inactive for anon users (UI hidden); no data mutation → no CSRF/IsGranted needed |
| V — Tests | ✅ Pass | Tests required for: `ContributorFilterState`, `ContributeurService`, new `ContributorRepository` methods, `CreateursController` |

## Project Structure

### Documentation (this feature)

```text
specs/024-createurs-page/
├── plan.md              ← this file
├── research.md          ← Phase 0 output
├── data-model.md        ← Phase 1 output
└── tasks.md             ← Phase 2 output (/speckit-tasks)
```

### Source Code

```text
src/
├── Controller/
│   └── CreateursController.php              # new
├── Dto/
│   └── ContributorFilterState.php           # new
├── Service/
│   └── ContributeurService.php              # new
├── Repository/
│   └── ContributorRepository.php            # extend (new methods)
└── Twig/Components/Contributeur/
    └── FilterPanelComponent.php             # new LiveComponent

assets/
├── controllers/
│   └── createurs_controller.js              # new Stimulus controller
└── styles/pages/
    └── _createurs.scss                      # new page styles

templates/
├── createurs/
│   └── index.html.twig                      # new gallery template
└── components/Contributor/
    └── FilterPanelComponent.html.twig       # new LiveComponent template
```

No migrations. No new entities. No `.platform` changes.

## Implementation Phases

### Phase A — DTO + Service + Repository

**A1 — `ContributorFilterState` DTO**
- File: `src/Dto/ContributorFilterState.php`
- `readonly` class, properties per data-model.md
- `static fromRequest(Request $request): self` — validates/sanitizes all params
- `toUrlParams(): array` — omits defaults (role=tous, sort=az, page=1 if not relevant)
- Valid sort values: `'az'|'ouvrages'|'note'`; valid roles: `'tous'|'auteur'|'traducteur'|'illustrateur'`; letter: A–Z or null; page ≥ 1

**A2 — `ContributorRepository` new methods**
- `findPaginatedFiltered(ContributorFilterState $state): Paginator` — Doctrine Paginator, 12/page, all filters, ORDER BY per sort; DISTINCT to avoid duplicates from JOIN
- `findAvailableLetters(ContributorFilterState $state): string[]` — same filter JOINs, returns sorted unique first-letter array
- `findCardDataBatch(array $ids): array` — bookCount + avgScore + roles[] per contributor ID
- `findTopCollectionsBatch(array $ids): array` — top 2 collections per contributor ID (post-process in PHP: group by contributor ID, sort by count DESC then col.id DESC, take 2)
- `findRoleCounts(): array<string, int>` — global count per role regardless of active filters; `SELECT cr.role, COUNT(DISTINCT c.id) FROM contribution cr JOIN contributor c ON … GROUP BY cr.role`; returns `['auteur' => N, 'traducteur' => N, 'illustrateur' => N, 'tous' => N]` where `tous` = COUNT(DISTINCT contributor.id); contributors with multiple roles counted in each applicable group
- `findForAutocomplete(string $q, int $maxPerRole = 5): array` — returns grouped result `['auteur' => [...], 'illustrateur' => [...], 'traducteur' => [...]]`, each entry has `slug, firstName, lastName, portraitImage, role, bookCount, mainCollection, averageScore`; contributors with multiple roles appear in each applicable group with the group's role in `role` field; max 5 entries per group

**A3 — `ContributeurService`**
- File: `src/Service/ContributeurService.php`
- `getPaginatedResults(ContributorFilterState $state): Paginator` — delegates to repository
- `getAvailableLetters(ContributorFilterState $state): string[]` — delegates to repository
- `getCardDataBatch(array $contributorIds): array` — calls `findCardDataBatch` + `findTopCollectionsBatch`, merges into card-ready map `[id => ContributorCardData]`
- `countFiltered(ContributorFilterState $state): int` — count of paginated result; used by `FilterPanelComponent::getExpectedCount()`
- `getRoleCounts(): array` — delegates to `findRoleCounts()`; returns `['auteur' => N, 'traducteur' => N, 'illustrateur' => N, 'tous' => N]` for role bar display (FR-009)
- `getAutocompleteResults(string $q): array` — delegates to `findForAutocomplete`, returns JSON-ready structure

### Phase B — Controller

**B1 — `CreateursController`**
- File: `src/Controller/CreateursController.php`
- `#[Route('/createurs', name: 'app_createurs')]` — main gallery
  1. `$state = ContributorFilterState::fromRequest($request)`
  2. `$paginator = $this->service->getPaginatedResults($state)` — totalItems, totalPages
  3. Redirect to page=totalPages if $state->page > totalPages
  4. `$ids` from paginated results
  5. `$cardData = $this->service->getCardDataBatch($ids)`
  6. `$availableLetters = $this->service->getAvailableLetters($state)`
  7. `$roleCounts = $this->service->getRoleCounts()` (FR-009)
  8. Render `createurs/index.html.twig` with all vars (including `roleCounts`)
- `#[Route('/createurs/search', name: 'app_createurs_search')]` — autocomplete
  1. `$q = trim($request->query->get('q', ''))` — empty → return `[]`
  2. `return new JsonResponse($this->service->getAutocompleteResults($q))`

### Phase C — LiveComponent Filter Panel

**C1 — `FilterPanelComponent`**
- File: `src/Twig/Components/Contributeur/FilterPanelComponent.php`
- `#[AsLiveComponent]` with `DefaultActionTrait`
- LiveProps (writable): `selectedCollectionIds[]`, `collectionSearch` (string, default ''), `periodMin`, `periodMax`, `nationalitySearch`, `bookCountRange`, `onlyFollowed`
- Applied props (non-writable, from mount): `appliedRole`, `appliedLetter`, `appliedSort`, `appliedPage` — preserved on apply
- `mount(ContributorFilterState $state, CollectionRepository $collectionRepo)`: hydrate draft from applied state
- `getExpectedCount(): int` — `$this->service->countFiltered($draftState)`
- `getVisibleCollections(): array` — filter by `collectionSearch` (not `nationalitySearch`), limit 5 (show-more pattern)
- `#[LiveAction] applyFilters(): RedirectResponse` — redirect to `/createurs?` + params (page=1 forced)
- `#[LiveAction] clearPanel(): void` — reset draft to applied values
- Template: `templates/components/Contributor/FilterPanelComponent.html.twig`

### Phase D — Templates

**D1 — `templates/createurs/index.html.twig`**
- Extends base layout; active nav link "Créateurs"
- Based on `design/pages/createurs.html` (reference, do not modify design file)
- Sections:
  - Page header with search bar (`data-controller="createurs"`)
  - Role bar (Tous / Auteurs / Traducteurs / Illustrateurs) — links with `?role=` + `data-turbo-action="replace"`
  - Active filter chips (role, letter, panel filters) — each chip is a link removing that param
  - Sort bar + result count + view toggle buttons
  - Alpha index A–Z — letters not in `availableLetters` get `disabled` class + aria-disabled
  - Results grid/list (`data-createurs-target="grid"`) — `{{ cardCount }}` skeleton cards rendered in Turbo cache version
  - Pagination: pages 1–4 + ellipsis + last page; `data-turbo-action="replace"` on all links
  - `<twig:Contributeur:FilterPanel :state="filterState" />` — off-canvas panel

**D2 — Skeleton cards**
- In `index.html.twig`: `<meta name="createurs-count" content="{{ contributors|length }}">`
- Skeleton markup: `<div class="creator-card creator-card--skeleton">` containing circle + 2 lines + footer band
- Turbo Drive shows cached version during navigation (skeletons visible during load)

**D3 — Navigation update**
- `templates/base.html.twig` (or nav partial): add "Créateurs" link between "Catalogue" and "Suggestions"
- Active state: `{% if app.request.pathInfo starts with '/createurs' %} active {% endif %}`

### Phase E — Frontend (Stimulus + SCSS)

**E1 — `assets/controllers/createurs_controller.js`**
Stimulus controller with targets: `grid`, `searchInput`, `searchDropdown`, `viewToggle`.

Actions:
- `connect()`: restore view from localStorage (`lca-createurs-view`), apply class to grid
- `search(event)`: debounce 250ms, AbortController pattern, fetch `/createurs/search?q=`, render dropdown
- `clearSearch()`: close dropdown, clear input
- `toggleView(event)`: switch grid/list class, save to localStorage
- Highlight helper: wrap search term occurrences in `<mark>` in result names

**E2 — `assets/styles/pages/_createurs.scss`**
- `.creator-card` grid and list variants
- `.creator-card--skeleton` with pulse animation (`@keyframes pulse`, opacity 0.5→1→0.5, 1.4s)
- Avatar circle with initials fallback
- `line-clamp: 3` on biography block
- Role badge styles
- Alpha index: `.alpha-index__letter--disabled` (greyed, cursor default)
- Search dropdown: `.creator-search-dropdown` with grouped results
- Import in `assets/styles/app.scss` or `pages.scss`

### Phase F — Tests

**F1 — `tests/Controller/CreateursControllerTest.php`**
- `GET /createurs` → 200
- `GET /createurs?role=auteur` → 200, filtered
- `GET /createurs?letter=A` → 200, filtered
- `GET /createurs?sort=note` → 200
- `GET /createurs?page=999` → redirect to last page
- `GET /createurs/search?q=` → 200, JSON `[]`
- `GET /createurs/search?q=jack` → 200, JSON with results
- `GET /createurs/search?q=xxxxnotfound` → 200, JSON `[]`

**F2 — `tests/Service/ContributeurServiceTest.php`**
- `getPaginatedResults` with role/letter/collection/period filters
- `getAvailableLetters` respects active filters
- `getCardDataBatch` computes bookCount, avgScore, roles, topCollections correctly
- Top collections tie-breaking (equal count → UUID v7 DESC)

**F3 — `tests/Repository/ContributorRepositoryTest.php`** (extend)
- `findPaginatedFiltered` with each filter type
- `findAvailableLetters` returns correct subset
- `findForAutocomplete` highlights / groups by role / max 5 per role

**F4 — `tests/Dto/ContributorFilterStateTest.php`**
- `fromRequest` sanitizes invalid sort/role/letter values
- `toUrlParams` omits default values
- `page` minimum 1

## Complexity Tracking

> No Constitution violations.
