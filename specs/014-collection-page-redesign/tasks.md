---

description: "Task list for 014 — Refonte Page Collection"
---

# Tasks: Refonte Page Collection

**Input**: Design documents from `/specs/014-collection-page-redesign/`

**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/twig-template-contract.md ✅, quickstart.md ✅

**Tests**: Included — constitution principle V requires PHPUnit coverage for `CollectionPublishingHistory` entity, `ContributionRepository` DQL, and `CollectionService` aggregation.

**Organization**: Tasks grouped by user story for independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: User story this task belongs to (US1–US5)

---

## Phase 1: Setup (Entity & Migration)

**Purpose**: New entity + schema migration. Must complete before any story can run against the DB.

- [X] T001 [P] Create value objects `HeroMeta`, `ContributorPill`, `RecurringContributorsResult` as readonly classes in `src/ValueObject/CollectionValueObjects.php`
- [X] T002 [P] Create `CollectionPublishingHistory` entity with UUID id, collection FK (CASCADE), editor FK (SET NULL), startYear, endYear, editionName, details + validation constraints in `src/Entity/CollectionPublishingHistory.php`
- [X] T003 Create `CollectionPublishingHistoryRepository` stub (no methods yet) in `src/Repository/CollectionPublishingHistoryRepository.php`
- [X] T004 Add `OneToMany $publishingHistory` inverse association (ordered by startYear ASC, id ASC) to `src/Entity/Collection.php`
- [X] T005 Generate Doctrine migration: `php bin/console doctrine:migrations:diff` → review output → save to `migrations/`
- [X] T006 Apply migration: `php bin/console doctrine:migrations:migrate`

**Checkpoint**: DB schema includes `collection_publishing_history` table — app boots without error.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Repository methods, service, and controller wiring. All user story templates depend on data passed by the controller.

**⚠️ CRITICAL**: No user story template work can begin until T011 (controller) is complete — the template contract variables must all be available.

- [X] T007 [P] Add `getPublicationYearRange(Collection $collection): array` and `computeAverageRating(Collection $collection): ?float` DQL methods to `src/Repository/CollectionRepository.php`
- [X] T008 [P] Add `findRecurringByCollection(Collection $collection): array` DQL method (groups by contributor+role, returns contributor objects via second JOIN hydration, ordered by count DESC) to `src/Repository/ContributionRepository.php`
- [X] T009 [P] Add `findByCollection(Collection $collection): array` method (ORDER BY startYear ASC, id ASC) to `src/Repository/CollectionPublishingHistoryRepository.php`
- [X] T010 [P] Write PHPUnit entity test for `CollectionPublishingHistory` (field types, nullable constraints, startYear/endYear validation) in `tests/Entity/CollectionPublishingHistoryTest.php`
- [X] T011 Implement `CollectionService` with `getHeroMeta()`, `getRecurringContributors()`, and `getPublishingHistory()` using value objects from T001 in `src/Service/CollectionService.php`
- [X] T012 Update `CollectionController::show()` to inject `CollectionService`, compute all aggregates, and pass `heroMeta`, `recurringContributors`, `publishingHistory` to the template in `src/Controller/CollectionController.php`

**Checkpoint**: Page `/collections/loup-solitaire` loads without error (template still renders old layout, all new variables available).

---

## Phase 3: User Story 1 — Hero Section (Priority: P1) 🎯 MVP

**Goal**: Deliver the hero with circular logo macaron, title + V.O., 4 meta pills (tomes count, year range, avg rating, status), and the 2 action buttons.

**Independent Test**: Navigate to `/collections/loup-solitaire` → macaron visible top-left, title "Loup Solitaire", "Lone Wolf" as V.O., 4 meta pills show real data, 2 action buttons present and inert.

- [X] T013 [P] [US1] Write PHPUnit test for `CollectionRepository::getPublicationYearRange` (edge cases: all null, some null, none null) and `computeAverageRating` (null when no reviews, float when reviews exist) in `tests/Repository/CollectionRepositoryTest.php`
- [X] T014 [US1] Rewrite `templates/collection/show.html.twig`: full skeleton (extends, blocks) + `.coll-hero` section (macaron with logo/placeholder, `collection.nom`, conditional `collection.nomOriginal` with "V.O." label, 4 meta pills from `heroMeta` + `totalBooks`, "Ajouter aux favoris" button `aria-pressed="false"`, "+ Suggérer un tome manquant" `<a href="#">`)

**Checkpoint**: US1 independent test passes — hero section fully renders with real data.

---

## Phase 4: User Story 2 — Books Grid (Priority: P1)

**Goal**: Deliver the fully redesigned tome card grid with deterministic hue colors, diagonal watermark, sort controls (Numéro/Note working client-side via Stimulus), disabled Possédés/Manquants filters.

**Independent Test**: On any collection page, all tome cards display new design. "Tri par Numéro" and "Tri par Note" reorganize grid client-side without reload. "Possédés" and "Manquants" are visually greyed and inert.

- [X] T015 [P] [US2] Create Stimulus controller with `sortByVolume()` (stable sort by `data-volume` ASC) and `sortByRating()` (stable sort by `data-rating` DESC, empty string = -Infinity) in `assets/controllers/collection-sort_controller.js`
- [X] T016 [P] [US2] Register `collection-sort` controller in `assets/controllers.json`
- [X] T017 [US2] Add `.tomes-section` to `templates/collection/show.html.twig`: "LES TOMES — N VOLUMES" header, filter pills ("Tous" active, "Possédés"/"Manquants" disabled), sort segment control, `.tomes-grid` with `data-controller="collection-sort"` and `data-collection-sort-target="grid"`, each `.tome` element with `data-hue` mapped to named palette value (`['forest','storm','ember','amber','ink','gold'][(book.volumeNumber ?? 0) % 6]` — 6 CSS classes defined in `design/pages/collection.html` lines 673–678; diagonal watermark is CSS-only via `repeating-linear-gradient` on `.tome-cover`, no extra HTML element needed), `data-volume`, `data-rating`, static `?` ownership indicator, card body meta (volumeNumber, frenchPublicationYear, paragraphs, title, avg rating or "–", author names)

**Checkpoint**: US2 independent test passes — all sort controls functional via Stimulus, no console errors.

---

## Phase 5: User Story 3 — Completion Section (Priority: P2)

**Goal**: Deliver the static "Complétion" panel with hardcoded values and dynamic collection name.

**Independent Test**: Completion panel visible on every collection page — shows 42,8 %, 12/28, 28-segment progress bar (12 colored + 16 empty), text with real `collection.nom`.

- [X] T018 [US3] Add `.progress-panel` section to `templates/collection/show.html.twig`: static 42,8 % display, "12 / 28 tomes possédés", 28-segment `role="progressbar"` bar (12 colored + 16 empty, `aria-valuemin="0" aria-valuemax="28" aria-valuenow="12"`), "Il vous manque 16 tomes pour boucler la saga {{ collection.nom }}.", "Mis à jour il y a 2 jours"

**Checkpoint**: US3 independent test passes — panel visible with real collection name, static numbers correct.

---

## Phase 6: User Story 4 — Publishing History (Priority: P2)

**Goal**: Deliver the conditional "ÉDITEURS SUCCESSIFS" timeline backed by the new `CollectionPublishingHistory` entity, with fixtures covering the Loup Solitaire collection.

**Independent Test**: Create collection with 2 history entries → "ÉDITEURS SUCCESSIFS" section appears with 2-node timeline. Collection with 1 entry → section absent from DOM.

- [X] T019 [P] [US4] Add `CollectionPublishingHistory` fixtures to `src/DataFixtures/AppFixtures.php` (≥ 2 entries for Loup Solitaire: different editors, startYear, one with endYear null, one with editionName)
- [X] T020 [US4] Add `.panel` publishing history section (conditional `{% if publishingHistory|length > 1 %}`) to `templates/collection/show.html.twig`: timeline nodes with period ("XXXX – YYYY" or "XXXX – présent" when endYear null), editor badge (`editor.nom` or "(éditeur inconnu)" when editor null), `editionName` if set, `details` if set

**Checkpoint**: US4 independent test passes — section present/absent per rule, all node fields render correctly, soft-deleted editor shows "(éditeur inconnu)".

---

## Phase 7: User Story 5 — Recurring Contributors (Priority: P3)

**Goal**: Deliver the "AUTEURS & ILLUSTRATEURS RÉCURRENTS" section with DQL-backed unique count and sorted contributor pills.

**Independent Test**: On Loup Solitaire page, section shows "7 CONTRIBUTEURS", pill "Joe Dever" has badge "28" and role "AUTEUR PRINCIPAL", pills sorted by badge count descending.

- [X] T021 [P] [US5] Write PHPUnit test for `ContributionRepository::findRecurringByCollection` (verifies grouping by contributor+role, COUNT, ORDER BY count DESC, respects deletedAt filter) in `tests/Repository/ContributionRepositoryTest.php`
- [X] T022 [P] [US5] Write PHPUnit test for `CollectionService::getRecurringContributors` (verifies uniqueCount deduplication, initials computation for "JD" and "Jo" cases, pill ordering) in `tests/Service/CollectionServiceTest.php`
- [X] T023 [US5] Add contributors `.panel` section to `templates/collection/show.html.twig`: `recurringContributors.uniqueCount ~ " CONTRIBUTEURS"` header, full unsorted pills list (sorted server-side via DQL), each pill showing initials, full name, role label in uppercase, count badge

**Checkpoint**: US5 independent test passes — contributor count and pill order correct on Loup Solitaire.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Accessibility, responsive validation, test suite green.

- [X] T024 [P] Audit and add missing ARIA attributes across `templates/collection/show.html.twig`: `aria-pressed` on sort/filter buttons, `role="progressbar"` values, `aria-label` on icon-only buttons — all per WCAG 2.1 AA; then run browser accessibility checker (axe DevTools or equivalent) on the rendered page and fix any color contrast violations (WCAG 2.1 AA minimum)
- [X] T025 [P] Verify responsive layout of all 5 sections against `design/pages/collection.html` mobile breakpoints (visual spot-check on ≤ 640px viewport)
- [X] T026 Run full PHPUnit suite and fix failures: `php bin/phpunit`
- [X] T027 Run quickstart.md verify step: `symfony server:start`, navigate to `/collections/loup-solitaire`, validate all 5 sections render correctly with real data; open DevTools Network tab, hard-reload, assert total page load < 2 s (SC-001); validate sort controls functional in current browser (SC-003 — cross-browser validation is manual post-merge QA, out of automated scope)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies — start immediately
- **Phase 2 (Foundational)**: Depends on Phase 1 (T001, T003, T006) — **blocks all story phases**
- **Phase 3 (US1)**: Depends on Phase 2 completion
- **Phase 4 (US2)**: Depends on Phase 2 completion — can run in parallel with US1 once phase 2 done
- **Phase 5 (US3)**: Depends on Phase 2 completion — purely Twig, no new backend
- **Phase 6 (US4)**: Depends on Phase 2 completion (T009 needed for fixtures load)
- **Phase 7 (US5)**: Depends on Phase 2 completion (T008 needed for DQL)
- **Phase 8 (Polish)**: Depends on all story phases

### User Story Dependencies

- **US1 (P1)**: Start after Phase 2 — no dependency on other stories
- **US2 (P1)**: Start after Phase 2 — extends same template file as US1, implement sequentially
- **US3 (P2)**: Start after Phase 2 — purely static Twig section, no backend dependency
- **US4 (P2)**: Start after Phase 2 — requires T009 (CollectionPublishingHistoryRepository) and T019 (fixtures)
- **US5 (P3)**: Start after Phase 2 — requires T008 (ContributionRepository DQL)

### Within Each Phase

- In Phase 1: T001 and T002 start in parallel; T003 and T004 run after T002 (can be parallel); T005 after T004; T006 after T005
- In Phase 2: T007, T008, T009, T010 run in parallel; T011 after T007+T008+T009; T012 after T011

### Parallel Opportunities

```bash
# Phase 1 parallel start:
Task T001: Create value objects in src/ValueObject/CollectionValueObjects.php
Task T002: Create CollectionPublishingHistory entity in src/Entity/

# Phase 2 parallel block:
Task T007: CollectionRepository methods
Task T008: ContributionRepository DQL
Task T009: CollectionPublishingHistoryRepository findByCollection
Task T010: PHPUnit entity test

# Phase 3+4 parallel (different files):
Task T013 (US1 test) + T015 (US2 Stimulus controller) + T016 (controllers.json)

# Phase 7 parallel tests:
Task T021: ContributionRepository test
Task T022: CollectionService test
```

---

## Implementation Strategy

### MVP First (US1 + US2 only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational — **critical blocker**
3. Complete Phase 3: US1 Hero
4. **STOP and VALIDATE**: `/collections/loup-solitaire` hero renders correctly
5. Complete Phase 4: US2 Books Grid + Stimulus
6. **STOP and VALIDATE**: sort controls work, all 30-tome cards display

### Full Incremental Delivery

1. Phase 1 + 2 → foundation ready
2. Phase 3 (US1 Hero) → validate → MVP candidate
3. Phase 4 (US2 Grid) → validate → full grid working
4. Phase 5 (US3 Completion) → validate → static panel visible
5. Phase 6 (US4 Publishers) → validate → timeline appears for collections with history
6. Phase 7 (US5 Contributors) → validate → pills correct
7. Phase 8 (Polish) → accessibility + tests green → ready for review

---

## Summary

| Metric | Value |
|--------|-------|
| Total tasks | 27 |
| Phase 1 (Setup) | 6 tasks |
| Phase 2 (Foundational) | 6 tasks |
| US1 Hero (P1) | 2 tasks |
| US2 Grid (P1) | 3 tasks |
| US3 Completion (P2) | 1 task |
| US4 History (P2) | 2 tasks |
| US5 Contributors (P3) | 3 tasks |
| Polish | 4 tasks |
| Parallel opportunities | T001+T002, T007+T008+T009+T010, T013+T015+T016, T021+T022 |
| Mandatory tests (constitution) | T010, T013, T021, T022 |
| MVP scope | Phase 1 + 2 + US1 (Hero) |
