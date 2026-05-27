---

description: "Task list for feature implementation"
---

# Tasks: Intégration du Design de la Page Auteur

**Input**: Design documents from `specs/008-author-page-design/`

**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/http-interface.md ✅, quickstart.md ✅

**Tests**: Required per Constitution Check — repository method, controller params, template rendering, exclusion assertions.

**Organization**: Tasks grouped by user story for independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story (US1=profil, US2=bibliographie, US3=exclusions)

---

## Phase 1: Setup

**Purpose**: Create new SCSS file and wire it into the Webpack Encore pipeline

- [X] T001 [P] Create `assets/styles/pages/_auteur.scss` with empty skeleton (file must exist before SCSS imports compile)
- [X] T00X [P] Add `@import "pages/auteur"` to `assets/styles/app.scss` (match syntax used by adjacent page imports)

**Checkpoint**: `npm run dev` compiles without errors

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Repository method + controller update that both US1 and US2 depend on

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T00X [P] Implement `findContributionsBySlug(string $slug, ?string $sagaFilter, string $sortOrder = 'chrono'): ?array` in `src/Repository/ContributorRepository.php` — DQL `SELECT c, contrib, b, e FROM Contributor c INNER JOIN c.contributions contrib INNER JOIN contrib.book b INNER JOIN b.editor e WHERE c.slug=:slug AND contrib.role=:role`; PHP `array_filter` by slugified saga; `usort` chrono (frenchPublicationYear ASC, NULL last via `PHP_INT_MAX`, title tiebreak) or alpha (title ASC); `sagaGroups` computed from full unfiltered list `[{slug, name, count}]`; unknown-saga fallback returns all contributions; returns `{contributor, filteredContributions, sagaGroups, totalCount}|null`
- [X] T00X [P] Update `ContributorController::authorShow()` in `src/Controller/ContributorController.php` — read `?saga=` and `?sort=` from `Request::query`; call `findContributionsBySlug($slug, $saga, $sort ?? 'chrono')`; throw 404 on null result; compute `$contributorAge = $birthDate?->diff(new \DateTimeImmutable())->y` and `$contributorAgeAtDeath = ($birthDate && $deathDate) ? $birthDate->diff($deathDate)->y : null`; render with variables: `contributor`, `contributions` (filteredContributions), `sagaGroups`, `activeSaga`, `activeSort`, `contributorAge`, `contributorAgeAtDeath`, `totalCount`

**Checkpoint**: `GET /authors/joe-dever` returns 200; `?saga=loup-solitaire` filters correctly; `?sort=alpha` returns alpha order

---

## Phase 3: User Story 1 — Consulter le profil d'un auteur (Priority: P1) 🎯 MVP

**Goal**: Display full author identity — portrait, nameplate, vitals, life dates, biography — in left column

**Independent Test**: Navigate to `/authors/joe-dever` and verify firstName, lastName, pseudo, nationality, birthDate, deathDate, biography, portraitImage all render in the two-column mockup layout

### Implementation for User Story 1

- [X] T00X [US1] Rewrite `templates/contributeur/author_show.html.twig` left column — page skeleton `{% extends 'base.html.twig' %}` + `.auteur > .auteur-grid`; `.portrait-card` with conditional `{% if contributor.portraitImage %}<img src="…" alt="Portrait de {{ contributor.firstName }} {{ contributor.lastName }}">{% else %}<div class="portrait-placeholder" role="img" aria-label=""></div>{% endif %}`; `.portrait-eyebrow` displays `contributor.slug` as text (e.g. "joe-dever") — no numeric AUT-XXXX ref exists in entity; `.nameplate` (firstName + lastName); `.vitals` table (prénom, nom, `{% if contributor.pseudo %}…{% endif %}` pseudo row, nationalité); `.life` block hidden via `{% if contributor.birthDate %}` (birth year + contributorAge; `{% if contributor.deathDate %}` death year + contributorAgeAtDeath); `.bio-card` with `{% if contributor.biography %}` lettrine on first char (`{{ contributor.biography|slice(0,1) }}` in `.bio-first-letter` + remaining text) + collapse toggle (`.bio-body.is-collapsed` div + inline JS button toggling `is-collapsed` class on `#bio-body` and text "Lire la suite"/"Replier") + `{% else %}<p>Biographie non disponible.</p>{% endif %}`
- [X] T00X [P] [US1] Add left-column SCSS to `assets/styles/pages/_auteur.scss` — `.auteur`, `.auteur-grid` (two-column `grid-template-columns: minmax(360px, 440px) 1fr` at `@media (min-width: 1100px)`; single column stacked below 1100px); `.portrait-card`, `.portrait-frame` (corner ornaments), `.nameplate`, `.vitals` table, `.life` dates block; `.bio-card` with `.bio-first-letter` (lettrine: large font, float left, line-height), `.bio-body` (overflow hidden, max-height 280px with fade when `.is-collapsed`), `.bio-toggle` (`display: none` at `@media (min-width: 1100px)`)
- [X] T00X [P] [US1] Write PHPUnit WebTestCase tests for US1 in `tests/Controller/ContributorControllerTest.php` — assert portrait `<img>` rendered when portraitImage set; assert placeholder div rendered when portraitImage null; assert pseudo row present when pseudo set; assert pseudo row absent when pseudo null; assert `.life` block absent when birthDate null; assert death year + ageAtDeath present when deathDate set; assert bio lettrine markup present when biography set; assert "Biographie non disponible." when biography null

**Checkpoint**: US1 fully functional — author profile renders correctly for all 9 acceptance scenarios from spec.md

---

## Phase 4: User Story 2 — Parcourir la bibliographie d'un auteur (Priority: P2)

**Goal**: Right column with filtered/sorted book bibliography — header with counts, saga pills, sort/view toolbar, book cards grid

**Independent Test**: For an author with works in multiple sagas, verify saga filter pills show correct counts, book cards show correct collection statuses for logged-in user, `?saga=` and `?sort=` params work correctly

### Implementation for User Story 2

- [X] T00X [US2] Add `.bibliography` right column to `templates/contributeur/author_show.html.twig` — `.biblio-head` with "SA BIBLIOGRAPHIE · {{ totalCount }} fiches" and `{% if is_granted('IS_AUTHENTICATED_FULLY') %}<span>0 dans ta collection</span>{% endif %}`; `.biblio-toolbar` with Trier control (links `?sort=chrono` and `?sort=alpha` preserving `?saga=`, `aria-label="Trier par"` on wrapper, active option highlighted via `activeSort` comparison) + Vue toggle `<button id="btn-view-toggle" aria-label="Basculer la vue" aria-pressed="false" onclick="var g=document.querySelector('.books-grid'),p=this;g.classList.toggle('is-list');p.setAttribute('aria-pressed',g.classList.contains('is-list'))">Vue · Liste</button>`; `.collection-filters role="group" aria-label="Filtrer par saga"` with TOUT pill `<a href="?" aria-pressed="{{ activeSaga is null ? 'true' : 'false' }}">TOUT · {{ totalCount }}</a>` + `{% for group in sagaGroups %}<a href="?saga={{ group.slug }}&sort={{ activeSort }}" aria-pressed="{{ activeSaga == group.slug ? 'true' : 'false' }}">{{ group.name|upper }} · {{ group.count }}</a>{% endfor %}`; `{% if contributions is empty %}<p>Aucune œuvre répertoriée.</p>{% else %}<ul class="books-grid" role="list">{% for contribution in contributions %}` book card `<li class="book-card" data-bg="{{ sagaColors[contribution.book.saga] ?? '' }}">` with `{% set abbr = sagaAbbreviations[contribution.book.saga] ?? ... %}` short ref, editionInfo, frenchPublicationYear, title, editor name, collection name, `<span class="bc-status">{{ contribution.book.status.value }}</span>` BookStatus badge, "NON POSSÉDÉ" footer (no rating/score widgets); static Twig mappings `sagaColors` and `sagaAbbreviations` defined at template top per plan.md
- [X] T00X [P] [US2] Add bibliography SCSS to `assets/styles/pages/_auteur.scss` — `.bibliography`, `.biblio-head`, `.biblio-toolbar`, `.collection-filters`, `.coll-pill` (with `[aria-pressed="true"]` active style), `.books-grid` (CSS grid), `.book-card` (with `[data-bg="mousse|encre|sang|or|parchemin"]` color variants), `.bc-cover`, `.bc-body`, `.bc-status` (BookStatus badge — small uppercase label, neutral pill style), `.bc-footer` (NON POSSÉDÉ grey / DANS MA COLLECTION green / LISTE D'ACHATS orange); `.books-grid.is-list .book-card` compact horizontal row (`flex-direction: row`)
- [X] T0XX [P] [US2] Write PHPUnit WebTestCase tests for US2 in `tests/Controller/ContributorControllerTest.php` — assert total count in biblio-head; assert saga pill count matches contributions per saga; assert `?saga=loup-solitaire` returns only LS cards; assert unknown `?saga=invalid` returns all cards with TOUT `aria-pressed="true"`; assert `?sort=alpha` returns cards in title ASC order; assert default sort is chrono (frenchPublicationYear ASC); assert empty-state message when author has no contributions; assert `?saga=X&sort=alpha` combination applied correctly; assert no rating/score markup in any card (`bc-score`, `notation`); assert `BookStatus` badge (`.bc-status`) present on each card; assert authenticated user sees "0 dans ta collection" in `.biblio-head` (login as `ROLE_USER` via `$client->loginUser()`); assert Trier links have `aria-label` wrapper; assert Vue toggle button has `aria-label="Basculer la vue"` and `aria-pressed` attribute

**Checkpoint**: US1 + US2 both functional — bibliography filters, sorts, and renders correctly for all 14 acceptance scenarios from spec.md

---

## Phase 5: User Story 3 — Vérification des exclusions (Priority: P3)

**Goal**: Confirm no forbidden UI elements appear in the rendered author page

**Independent Test**: Inspect rendered HTML and confirm absence of `.seal-row`, `.seal-btn`, `.also-strip`, "Contemporains", "Mes Favoris"

### Implementation for User Story 3

- [X] T0XX [P] [US3] Add exclusion PHPUnit assertions to `tests/Controller/ContributorControllerTest.php` — `assertStringNotContainsString('seal-row', $html)`, `assertStringNotContainsString('seal-btn', $html)`, `assertStringNotContainsString('also-strip', $html)`, `assertStringNotContainsString('Contemporains', $html)`, `assertStringNotContainsString('Mes Favoris', $html)`, `assertStringNotContainsString('bc-score', $html)`, `assertStringNotContainsString('notation', $html)` (FR-011, FR-012, FR-013)

**Checkpoint**: All three user stories independently functional and tested

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Validate compilation, full test run, and quickstart verification

- [X] T0XX [P] Run `npm run dev` and verify `_auteur.scss` compiles without errors; open `/authors/joe-dever` in browser and visually compare against `design/pages/auteur.html` mockup; manually verify Vue toggle (grille ↔ liste) works client-side (JS toggle not covered by PHPUnit — visual verification only)
- [X] T0XX [P] Run `php bin/phpunit tests/Controller/ContributorControllerTest.php` and confirm all tests pass; use Symfony Profiler or `assertSame(1, $this->getQueryCount())` pattern to verify single SQL query per page load (SC-005 architectural guarantee — no load test needed at ≤50 contributions)
- [X] T0XX [P] Run quickstart.md exclusion curl verification — `curl -s http://127.0.0.1:8000/authors/joe-dever | grep -c "seal-row\|seal-btn\|also-strip\|Contemporains\|Mes Favoris"` (expected: 0) and `curl -s ... | grep -c "bc-score\|bouclier\|notation"` (expected: 0)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately, T001 and T002 parallel
- **Foundational (Phase 2)**: Depends on Setup completion — T003 and T004 parallel (T004 uses interface from data-model.md, not T003 implementation)
- **US1 (Phase 3)**: Depends on Foundational — T006 and T007 parallel after T005
- **US2 (Phase 4)**: Depends on Foundational — T009 and T010 parallel after T008
- **US3 (Phase 5)**: Depends on US1 + US2 phases (tests reference full rendered page)
- **Polish (Phase 6)**: Depends on all story phases complete; T012/T013/T014 parallel

### User Story Dependencies

- **US1 (P1)**: After Foundational — no dependency on US2 or US3
- **US2 (P2)**: After Foundational — builds on same template/controller, no hard US1 dep
- **US3 (P3)**: After US1 + US2 (tests verify rendered page with full content)

### Parallel Opportunities

```bash
# Phase 1 — run together:
T001  # Create _auteur.scss
T002  # Import in app.scss

# Phase 2 — run together:
T003  # Repository method
T004  # Controller update

# Phase 3 — T006, T007 after T005:
T005  # Template left column (sequential — must complete first)
T006  # Left column SCSS (parallel with T007)
T007  # US1 tests (parallel with T006)

# Phase 4 — T009, T010 after T008:
T008  # Template right column (sequential — must complete first)
T009  # Bibliography SCSS (parallel with T010)
T010  # US2 tests (parallel with T009)

# Phase 6 — run together:
T012  # Visual verification
T013  # PHPUnit run
T014  # Curl exclusion check
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (T001, T002)
2. Complete Phase 2: Foundational (T003, T004) — **BLOCKS everything**
3. Complete Phase 3: US1 (T005 → T006 + T007)
4. **STOP and VALIDATE**: Author profile page functional with portrait, vitals, bio
5. Demo if ready

### Incremental Delivery

1. Setup + Foundational → controller + repo wired
2. US1 → Profile column functional → demo profile page
3. US2 → Bibliography column functional → demo full page with filtering/sorting
4. US3 → Exclusions verified → acceptance complete
5. Polish → Final visual QA + test suite green

---

## Notes

- No migrations — read-only view over existing schema
- No new JS dependencies — inline JS only (biography collapse, view toggle)
- `_auteur.scss` extracts only author-specific CSS from `design/pages/auteur.html` `<style>` block (not tokens.css / components.css classes)
- `sagaColors` and `sagaAbbreviations` are static Twig variable mappings defined at template top
- `CollectionEntry` is a placeholder — all book cards show "NON POSSÉDÉ" until feature 009+
- T003 injects `SluggerInterface` (AsciiSlugger) into `ContributorRepository` for consistent saga slug comparison
