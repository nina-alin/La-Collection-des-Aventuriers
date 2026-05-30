---
description: "Task list for 011 — Page Contributeur Suggestions"
---

# Tasks: Page Contributeur — Suggestions (011)

**Input**: Design documents from `/specs/011-contributor-page/`

**Prerequisites**: plan.md ✅, spec.md ✅, data-model.md ✅, contracts/endpoints.md ✅, research.md ✅, quickstart.md ✅

**Tests**: Included — explicitly required by Constitution Check (plan.md §Constitution Check V).

**Organization**: Tasks grouped by user story to enable independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no shared dependencies)
- **[Story]**: User story label — US1 through US5
- Exact file paths included in every task

---

## Phase 1: Setup

**Purpose**: Install missing dependency and prepare upload infrastructure.

- [X] T001 Install `symfony/ux-live-component` via `composer require symfony/ux-live-component` and verify Flex auto-registers the bundle (check `config/bundles.php` and `assets/controllers.json`)
- [X] T002 Create `public/uploads/covers/` and `public/uploads/covers/tmp/` directories, add `.gitkeep` files, and add `/public/uploads/covers/*` (keeping `.gitkeep`) to `.gitignore`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Entities, enums, repositories, migration, and fixtures that all user stories depend on.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [X] T003 [P] Create `SuggestionStatus` backed enum (PENDING, VALIDATED, REFUSED) in `src/Entity/Enum/SuggestionStatus.php`
- [X] T004 [P] Create `SuggestionEntityType` backed enum (BOOK, AUTHOR, ILLUSTRATOR, TRADUCTOR, EDITOR, COLLECTION) in `src/Entity/Enum/SuggestionEntityType.php`
- [X] T005 [P] Create `SuggestionMode` backed enum (NEW_ENTRY, CORRECTION) in `src/Entity/Enum/SuggestionMode.php`
- [X] T006 [P] Create `SuggestionRefusalAction` backed enum (VOIR_FICHE, MASQUER) in `src/Entity/Enum/SuggestionRefusalAction.php`
- [X] T007 [P] Create `ContributorLevel` Doctrine entity (id auto-int, name string(100), rankNumber smallint unique, threshold int) with `#[ORM\Entity]` attribute mapping in `src/Entity/ContributorLevel.php`
- [X] T008 Create `Suggestion` Doctrine entity with all fields from data-model.md (Uuid v7 PK, ManyToOne User, entityType enum, mode enum, sourceEntityId nullable Uuid, sourceEntityType nullable string, formData json, status default PENDING, coverImagePath nullable string(255), submittedAt DateTimeImmutable, OneToOne refusal mappedBy) and composite index `idx_suggestion_user_status(user_id, status)` in `src/Entity/Suggestion.php`
- [X] T009 Create `SuggestionRefusal` Doctrine entity (Uuid v7 PK, OneToOne Suggestion inversedBy, ManyToOne moderator nullable, reason text, actions json array default [], refusedAt DateTimeImmutable) in `src/Entity/SuggestionRefusal.php`
- [X] T010 [P] Create `SuggestionRepository` extending `ServiceEntityRepository<Suggestion>` with methods: `findPendingCountByUser(User $user): int`, `findRecentByUser(User $user, int $limit = 50): array` ordered by submittedAt DESC in `src/Repository/SuggestionRepository.php`
- [X] T011 [P] Create `ContributorLevelRepository` extending `ServiceEntityRepository<ContributorLevel>` with method `findRankForCount(int $validatedCount): ?ContributorLevel` (ORDER BY threshold DESC, first record ≤ count) in `src/Repository/ContributorLevelRepository.php`
- [X] T012 Generate Doctrine migration via `php bin/console doctrine:migrations:diff` (creates `suggestion`, `suggestion_refusal`, `contributor_level` tables with all FK constraints and the composite index per data-model.md) — **review the generated SQL before running** (verify `CASCADE` constraints, `idx_suggestion_user_status` index, and `'PENDING'` default are present) — then run via `php bin/console doctrine:migrations:migrate --no-interaction` in `migrations/`
- [X] T013 Create `ContributorLevelFixture` seeding the 6 rank levels (Novice/0, Apprenti/5, Chroniqueur confirmé/15, Archiviste/30, Érudit/60, Grand Sage/100) tagged with group `contributor_level` in `src/DataFixtures/ContributorLevelFixture.php`
- [X] T051 [P] Write unit test for `SuggestionRefusal` entity: default `actions` = `[]`, `refusedAt` auto-set on persist, `reason` not blank, `suggestion` and `moderator` associations typed correctly (moderator nullable per data-model.md) in `tests/Unit/Entity/SuggestionRefusalTest.php`

**Checkpoint**: Foundation ready — migration applied, fixtures seedable, entities and repositories available for all user stories.

---

## Phase 3: User Story 1 — Soumettre une nouvelle fiche (Priority: P1) 🎯 MVP

**Goal**: Authenticated user completes the 4-step wizard (type selection → Book fields → cover upload → preview + submit) for a new Book entry; suggestion persists as PENDING and appears in tracking panel.

**Independent Test**: (1) Log in as ROLE_USER, (2) navigate to `/mes-suggestions`, (3) select "Nouvelle fiche" + "Livre", (4) fill all required fields with valid values, (5) upload a valid JPG cover, (6) verify step 4 preview reflects inputs, (7) click "Soumettre" → toast confirms, suggestion card appears in side panel with status "En attente".

### Implementation for User Story 1

- [X] T014 [P] [US1] Create `SuggestionService` with `submit(User $user, array $formData, SuggestionEntityType $type, SuggestionMode $mode, ?string $sourceEntityId, ?string $coverImageTempPath): Suggestion` (validates quota < 20, persists Suggestion with PENDING status) and `getPendingCount(User $user): int` in `src/Service/SuggestionService.php`
- [X] T015 [P] [US1] Create `CoverImageProcessor` with `process(UploadedFile $file): string` — validates MIME (jpeg/png/webp via finfo), validates size ≤ 4MB, center-crops to 3:4 ratio using GD, resizes to max 600×800px, saves as JPEG to `public/uploads/covers/{uuid}.jpg`, returns relative path — in `src/Service/CoverImageProcessor.php`
- [X] T052 [P] [US1] Write unit test for `CoverImageProcessor`: valid JPG ≤ 4MB → returns relative path string; file > 4MB → throws; unsupported MIME (PDF) → throws; output image aspect ratio ≈ 0.75 (3:4, within ±0.01) in `tests/Unit/Service/CoverImageProcessorTest.php`
- [X] T016 [P] [US1] Write unit test for `Suggestion` entity: constructor defaults (status=PENDING, submittedAt set), enum field types, nullable fields, in `tests/Unit/Entity/SuggestionTest.php`
- [X] T017 [US1] Write unit test for `SuggestionService`: `submit()` persists entity, `submit()` throws when pending count = 20, `getPendingCount()` returns correct int — mock `SuggestionRepository` in `tests/Unit/Service/SuggestionServiceTest.php`
- [X] T018 [US1] Create `WizardComponent` PHP class with `#[AsLiveComponent]` attribute, state properties ($step 1–4, $mode, $entityType, $formData array, $coverImageTempPath, $errors array, $isSubmitting bool, $pendingCount int), LiveComponent actions `goToStep(int $step)`, `nextStep()`, `uploadCover(UploadedFile $file)` (calls `CoverImageProcessor`, sets $coverImageTempPath or $errors['cover']), and `submitSuggestion()` (calls `SuggestionService::submit()`, resets state on success, emits flash) in `src/Twig/Components/Suggestion/WizardComponent.php`
- [X] T019 [P] [US1] Create page template shell with `role="region" aria-label="Tableau de bord contributeur"` dashboard banner placeholder and split-panel layout (left: wizard, right: side panel) matching `design/pages/suggestions.html` token usage in `templates/suggestion/index.html.twig`
- [X] T020 [US1] Create `WizardComponent` Twig template with `<ol aria-label="Étapes de création">` stepper (4 steps, aria-current="step" on active, aria-disabled="true" on locked, checkmark on done), step content container routing to the correct step partial (StepType/StepForm/StepCover/StepPreview) in `templates/components/Suggestion/WizardComponent.html.twig`
- [X] T021 [P] [US1] Create `StepType` template with two visual radio-button cards ("Nouvelle fiche" / "Corriger une fiche") and a required `<select>` for entity type (Livre, Auteur, Illustrateur, Traducteur, Éditeur, Collection) — all fields have explicit ARIA labels in `templates/components/Suggestion/StepType.html.twig`
- [X] T022 [P] [US1] Create `StepForm` template for new-entry Book mode with all required fields (Titre, Sous-titre, Auteur, Illustrateur, Traducteur, Éditeur, Collection, ISBN, Parution France, Édition originale, Paragraphes) — each field has label, placeholder, and `aria-describedby` for help/error text in `templates/components/Suggestion/StepForm.html.twig`
- [X] T023 [P] [US1] Create `StepCover` template with upload zone showing 5 visual states (idle/drag-over/invalid/loading/success), a visible `<input type="file" accept="image/jpeg,image/png,image/webp">` button, error message slot with `aria-live="assertive"`, and file size/format constraints display in `templates/components/Suggestion/StepCover.html.twig`
- [X] T024 [US1] Create `StepPreview` template for new-entry mode: displays all non-empty Book fields (Titre, Sous-titre, Auteur(s), Illustrateur, Traducteur, Éditeur, Collection, ISBN, Parution France, Édition originale, Paragraphes, cover thumbnail), quota warning (FR-018) when $pendingCount ≥ 20, submit button with spinner state when $isSubmitting, `aria-live="polite"` toast area in `templates/components/Suggestion/StepPreview.html.twig`
- [X] T025 [US1] Create `SuggestionController` with `#[Route('/mes-suggestions')]` `#[IsGranted('ROLE_USER')]` action rendering `suggestion/index.html.twig`, and `#[Route('/api/suggestions/entities/{type}')]` POST action for on-the-fly entity creation (validates type ∉ book, creates Contributor/Editor/Collection from `{"name": "..."}`, returns `{id, label}` JSON, rate-limited at 10/hour via Symfony Rate Limiter, protected by CSRF header) in `src/Controller/SuggestionController.php`
- [X] T026 [US1] Create `suggestion-upload_controller.js` Stimulus controller: handles `dragover`/`drop`/`change` events, applies 5 CSS state classes (idle/drag-over/invalid/loading/success), client-side validates MIME + size ≤ 4MB before dispatching LiveComponent upload event, resets on error (FR-036) in `assets/controllers/suggestion-upload_controller.js`
- [X] T027 [US1] Create `suggestion-abandon_controller.js` Stimulus controller: listens to `beforeunload` and intercepts external link clicks on the page, shows custom modal "Vous avez des modifications non sauvegardées. Quitter la page ?" with "Rester" / "Quitter" actions; focus moves to first modal button on open (FR-030, FR-059); also listens to `live:connect-error` and `live:render-error` LiveComponent events — on error dispatches toast "Connexion perdue — vos données sont préservées, réessayez." via `toast_controller.js` (T053) and disables nav/submit buttons; on reconnect (`live:connect`) re-enables buttons (FR-052) in `assets/controllers/suggestion-abandon_controller.js`

**Checkpoint**: User Story 1 fully functional — user can submit a new Book entry end-to-end.

---

## Phase 4: User Story 2 — Corriger une fiche existante (Priority: P1)

**Goal**: User selects correction mode, chooses a source entity (autocomplete), fields pre-fill, user modifies N fields, step 4 shows diff "N champs modifiés", submit succeeds.

**Independent Test**: (1) Select "Corriger une fiche", (2) choose source via autocomplete, (3) verify pre-fill banner and fields, (4) change 2 fields, (5) reach step 4 → "2 champs modifiés" shown with modified values highlighted, (6) submit → PENDING suggestion in panel.

### Implementation for User Story 2

- [X] T028 [US2] Add correction-mode state to `WizardComponent`: `$sourceEntityId`, `$originalData` (array snapshot on selection), `computeDiff(): array` (pure `array_diff_assoc($this->originalData, $this->formData)` returning count + changed keys per FR-038), `clearCoverOnModeChange()` LiveComponent action (resets $coverImageTempPath when mode switches at step 3/4 per FR-028), and source entity `selectSource(string $id)` action that fetches entity data and sets $originalData + $formData in `src/Twig/Components/Suggestion/WizardComponent.php`
- [X] T029 [P] [US2] Add `GET /api/suggestions/autocomplete/{type}` endpoint to `SuggestionController`: validates type (book/author/illustrator/traductor/editor/collection), requires `q` ≥ 2 chars, performs case-insensitive LIKE query returning max 10 `{id, label}` results, returns 400 on invalid params in `src/Controller/SuggestionController.php`
- [X] T030 [P] [US2] Add `GET /api/suggestions/check-unique` endpoint to `SuggestionController`: validates `field` (title/subtitle), `entityType` (book only), performs case-insensitive exact match against `book.title`/`book.original_title`, returns `{unique: bool, existing: null|{id, label, url}}` in `src/Controller/SuggestionController.php`
- [X] T031 [US2] Create `suggestion-autocomplete_controller.js` Stimulus controller: debounces input (300ms), fetches `/api/suggestions/autocomplete/{type}?q=...` with `AbortController` 3s timeout, renders ARIA Listbox dropdown (role="listbox", role="option", keyboard nav Arrows/Enter/Esc per FR-032), on timeout/5xx replaces combobox with plain `<input>` + "Saisie libre — service de recherche indisponible" label (FR-031), "Créer "[text]"" option triggers POST to entity creation endpoint (FR-031), supports multi-value via "+" button (FR-033) in `assets/controllers/suggestion-autocomplete_controller.js`
- [X] T032 [US2] Update `StepForm` template: add source entity autocomplete selector (correction mode only), pre-fill banner with `role="region"` + collapsible on mobile (FR-051), multi-contributor rows with "+" add and individual delete buttons (FR-033), maintain all existing new-entry fields in `templates/components/Suggestion/StepForm.html.twig`
- [X] T033 [US2] Update `StepPreview` template for correction mode: show "N champs modifiés" diff legend, highlight modified values vs original with distinct CSS class, "0 champs modifiés" still shows active submit button (FR-038) in `templates/components/Suggestion/StepPreview.html.twig`

**Checkpoint**: User Stories 1 and 2 both independently functional.

---

## Phase 5: User Story 3 — Consulter et gérer son suivi (Priority: P2)

**Goal**: Side panel displays up to 50 suggestion cards with status filters; polling refreshes every 30s; refusal detail expands with moderator name + actions.

**Independent Test**: (1) Open `/mes-suggestions` with contributions in multiple statuses, (2) verify cards show type/mode/name/status/relative timestamp, (3) click "Refusées" filter → only refused cards shown, (4) click info button → disclosure panel shows moderator, reason, contextual action buttons.

### Implementation for User Story 3

- [X] T034 [P] [US3] Add `GET /api/suggestions/feed` endpoint to `SuggestionController`: calls `SuggestionRepository::findRecentByUser()` (50-cap, DESC), maps to JSON with `{id, entityType, mode, entityName (from formData), status, submittedAt (ISO-8601), refusal: null|{moderatorName, reason, actions (only recognized SuggestionRefusalAction keys)}}`, adds `counts` and `pendingCount` to response (contracts/endpoints.md §2) in `src/Controller/SuggestionController.php`
- [X] T035 [P] [US3] Create `suggestion-polling_controller.js` Stimulus controller: polls `GET /api/suggestions/feed` every 30s (`setInterval`), updates suggestion card list and status badge counts in DOM, tracks `failCount` (increment on error, reset on success), adds `data-suspended="true"` to panel at `failCount >= 3` revealing "Mise à jour suspendue" indicator (FR-043), continues polling regardless of active tab (FR-049) in `assets/controllers/suggestion-polling_controller.js`
- [X] T036 [P] [US3] Create `suggestion-tabs_controller.js` Stimulus controller: implements ARIA tablist pattern (`role="tablist"`, `aria-selected`, `aria-controls`), defaults to "Action" tab on mobile (FR-046), updates pending-count badge on tab element from polling data (FR-044), preserves form state across tab switches (LiveComponent server-side state survives) in `assets/controllers/suggestion-tabs_controller.js`
- [X] T037 [US3] Update `templates/suggestion/index.html.twig` side panel: suggestion card list (entityType, mode, entityName, status badge, relative timestamp), status filter buttons with live counters (Toutes/En attente/Validées/Refusées), empty state message "Vous n'avez pas encore soumis de suggestion" (FR-042), refusal detail disclosure per card (ARIA Disclosure pattern: `aria-expanded`, `aria-controls`, panel `role="region" aria-label="Motif de refus"` per FR-062) with contextual action buttons for recognized `SuggestionRefusalAction` values only (FR-045) in `templates/suggestion/index.html.twig`
- [X] T038 [US3] Write integration test for `SuggestionController`: (1) unauthenticated access to `/mes-suggestions` → 302 to `/connexion`; (2) ROLE_USER access → 200; (3) submit with missing CSRF → 403/422; (4) submit when pending count = 20 → 400 with quota message; (5) feed endpoint returns correct JSON shape; (6) `GET /api/suggestions/autocomplete/{type}` unauthenticated → 302; (7) `GET /api/suggestions/check-unique` unauthenticated → 302; (8) `POST /api/suggestions/entities/{type}` unauthenticated → 302; (9) `POST /api/suggestions/entities/{type}` without CSRF header → 403/422; (10) `POST /api/suggestions/entities/{type}` exceeding rate limit → 429 in `tests/Integration/Controller/SuggestionControllerTest.php`

**Checkpoint**: User Stories 1, 2, and 3 all independently functional.

---

## Phase 6: User Story 4 — Visualiser le tableau de bord (Priority: P2)

**Goal**: Dashboard banner shows current rank name+number, delta to next rank, validated count, pending count, acceptance rate.

**Independent Test**: Visit `/mes-suggestions` as user with seeded contributions; verify banner shows correct rank label, "à N fiches du rang [next]", validated count, pending count, acceptance rate % (or "—" if no settled suggestions).

### Implementation for User Story 4

- [X] T039 [P] [US4] Create `ContributorLevelService` with `computeRank(User $user): ?ContributorLevel` (uses `ContributorLevelRepository::findRankForCount()`), `getDeltaToNextRank(User $user): ?int` (threshold of next level − validated count), `getAcceptanceRate(User $user): ?float` (validated / (validated + refused), null if denominator = 0), `getMetrics(User $user): array` (validatedCount, pendingCount, acceptanceRate, currentLevel, deltaToNext) in `src/Service/ContributorLevelService.php`
- [X] T040 [P] [US4] Write unit test for `ContributorLevel` entity: threshold ordering, rank number uniqueness constraint, seed data values in `tests/Unit/Entity/ContributorLevelTest.php`
- [X] T041 [P] [US4] Write unit test for `ContributorLevelService`: acceptance rate formula (excludes PENDING), null rate when no settled suggestions, delta = 0 at highest rank — mock repositories in `tests/Unit/Service/ContributorLevelServiceTest.php`
- [X] T042 [US4] Inject `ContributorLevelService` into `SuggestionController` page action, pass metrics to template, and populate the `role="region" aria-label="Tableau de bord contributeur"` banner (FR-064) in `templates/suggestion/index.html.twig` with rank label, delta message, validated/pending counts, acceptance rate (display "—" when null) in `src/Controller/SuggestionController.php`

**Checkpoint**: User Stories 1–4 all independently functional.

---

## Phase 7: User Story 5 — Valider les champs en temps réel (Priority: P3)

**Goal**: Every relevant field in step 2 shows is-valid/is-invalid/is-checking feedback after blur or 300ms debounce; ISBN check digit runs client-side; paragraphs > 800 blocks submission.

**Independent Test**: (1) Type invalid ISBN → is-invalid + error message; (2) type valid ISBN → is-valid; (3) enter 801 in Paragraphes → blur → is-invalid + "Soumettre" disabled; (4) type Titre → is-checking while uniqueness check in flight → is-valid or is-invalid after response.

### Implementation for User Story 5

- [X] T043 [P] [US5] Add live validation logic to `WizardComponent`: after `blur` LiveComponent action or 300ms post-input, set `$errors[field]` for blocking errors (empty required field, paragraphs > 800, invalid date format/range), set non-blocking warnings for duplicate title (FR-034); expose `$errors` to template so "Suivant" / "Soumettre" buttons compute disabled state from $errors + required-field check in `src/Twig/Components/Suggestion/WizardComponent.php`
- [X] T044 [P] [US5] Add ISBN-10 and ISBN-13 check digit validation to `suggestion-autocomplete_controller.js`: triggers after field value reaches 10 or 13 digits (stripping hyphens), applies `is-valid`/`is-invalid` class + message, field stays neutral below threshold (FR-034); also add Year field validation (4-digit format, ≥ 1800, ≤ current year + 2) triggering on blur (FR-011, SC-003 ≤ 100ms) in `assets/controllers/suggestion-autocomplete_controller.js`
- [X] T045 [US5] Update `StepForm` template: add `aria-live="assertive"` error containers under each field (FR-058), `aria-live="polite"` is-checking spinner with `role="status" aria-label="Vérification en cours..."` (FR-061), apply `is-valid`/`is-invalid`/`is-checking` CSS classes from WizardComponent `$errors` state in `templates/components/Suggestion/StepForm.html.twig`

**Checkpoint**: All 5 user stories independently functional.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: WCAG 2.1 AA hardening, focus management, mobile layout, and final validation.

- [X] T046 [P] Audit and complete ARIA markup across all wizard templates: stepper `<ol>` with `aria-current="step"` / `aria-disabled="true"` (FR-060), dashboard banner `role="region" aria-label="Tableau de bord contributeur"` (FR-064), refusal detail ARIA Disclosure (FR-062), spinner `role="status"` (FR-061), upload button keyboard accessible (FR-061) in `templates/components/Suggestion/` and `templates/suggestion/index.html.twig`
- [X] T047 [P] Implement explicit focus management in `WizardComponent`: on step change (forward/back) → `$focusTarget = '#step-{n}-heading'`; on blocking error → `$focusTarget = '#field-{first-errored}'`; on upload error → focus returns to file input; emit `live:focus` LiveComponent event consumed by Stimulus for actual DOM focus (FR-059) in `src/Twig/Components/Suggestion/WizardComponent.php`
- [X] T048 [P] Apply mobile-responsive layout in wizard templates: `@media (max-width: 1079px)` — compact stepper (numbers only, current step label below FR-047), inline autocomplete dropdown max-5-visible scroll (FR-048), collapsible pre-fill banner default-collapsed (FR-051), tab layout from `suggestion-tabs_controller.js`, upload via standard `<input type="file">` (FR-050) using `design/pages/suggestions.html` tokens in `templates/components/Suggestion/` and `templates/suggestion/index.html.twig`
- [X] T049 Write `SuggestionPageA11yTest` functional test: loads `/mes-suggestions` as authenticated ROLE_USER, runs axe-core WCAG 2.1 AA audit (critical+serious violations only), asserts zero violations; covers: step navigation, error announcement, modal focus, polling indicator; additionally loads the page with JS disabled (`$client->disableJavascript()`) and asserts the form renders as functional plain HTML (no blank page, no broken layout, form action present) per SC-006 in `tests/Functional/Accessibility/SuggestionPageA11yTest.php`
- [X] T050 Run quickstart.md validation: install LiveComponent (T001), migrate (T012), seed fixtures (T013), build assets (`npm run dev`), start server, smoke-test `/mes-suggestions` — confirm page loads, wizard steps navigate, side panel polling fires at 30s interval
- [X] T053 [P] Create `toast_controller.js` Stimulus controller wrapping Bootstrap Toast API: accepts `message` (string), `type` (success/error/warning) and `position` (top-right desktop default, switches to top-center on `< 1080px`) data values; auto-dismisses after 4s; dismissible on click before timeout; triggered by `CustomEvent` dispatch from `suggestion-abandon_controller.js` (FR-052), `suggestion-polling_controller.js` (FR-043), and `WizardComponent` template (FR-040/FR-020/FR-054) — replaces any inline toast implementation in those controllers (FR-041) in `assets/controllers/toast_controller.js`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Phase 1 — BLOCKS all user stories
- **User Stories (Phases 3–7)**: Depend on Phase 2 completion; can proceed in priority order or in parallel
  - US1 (P1) and US2 (P1): Both P1 — implement sequentially (US2 extends US1's WizardComponent)
  - US3 (P2) and US4 (P2): Both P2, independent of US1/US2 at implementation level
  - US5 (P3): Enhances US1+US2 WizardComponent
- **Polish (Phase 8)**: Depends on all desired user stories being complete

### User Story Dependencies

- **US1 (P1)**: Starts after Phase 2 — no dependency on other stories
- **US2 (P1)**: Extends US1's `WizardComponent` — implement after US1 checkpoint
- **US3 (P2)**: Adds endpoints + Stimulus controllers — independent of US1/US2 (different files)
- **US4 (P2)**: New service + banner template — independent of US1/US2/US3
- **US5 (P3)**: Modifies `WizardComponent` and `StepForm` — implement after US1/US2

### Within Each User Story

- Models/services before controllers/components
- PHPUnit tests written alongside their subject (same phase)
- Templates after PHP classes they render

---

## Parallel Execution Examples

### Phase 2 Parallel Batch 1 (start immediately)
```
T003: Create SuggestionStatus enum
T004: Create SuggestionEntityType enum
T005: Create SuggestionMode enum
T006: Create SuggestionRefusalAction enum
T007: Create ContributorLevel entity
T051: Write SuggestionRefusalTest
```

### Phase 3 US1 Parallel Batch
```
T014: Create SuggestionService
T015: Create CoverImageProcessor
T052: Write CoverImageProcessorTest
T016: Write SuggestionTest
T019: Create page template shell
T021: Create StepType template
T022: Create StepForm template
T023: Create StepCover template
```

### Phase 5 US3 Parallel Batch
```
T034: Add /api/suggestions/feed endpoint
T035: Create suggestion-polling_controller.js
T036: Create suggestion-tabs_controller.js
```

### Phase 6 US4 Parallel Batch
```
T039: Create ContributorLevelService
T040: Write ContributorLevelTest
T041: Write ContributorLevelServiceTest
```

---

## Implementation Strategy

### MVP First (US1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL)
3. Complete Phase 3: US1 — new Book entry submission
4. **STOP and VALIDATE**: Submit a new Book entry end-to-end
5. Deploy/demo the golden path

### Incremental Delivery

1. Setup + Foundational → Foundation ready
2. US1 → new entry submission works
3. US2 → correction mode with diff works
4. US3 → tracking panel with polling works
5. US4 → dashboard banner with rank/metrics works
6. US5 → real-time validation layer works
7. Polish → WCAG AA, focus, mobile, a11y test

---

## Notes

- `[P]` tasks touch different files — safe to parallelize within same phase
- Tests are mandatory per Constitution Check — written in the same phase as their subject
- US2 extends `WizardComponent.php` (T028) — implement after US1's T018 is complete
- No WebSockets, no draft persistence, no external validation APIs — all confirmed in research.md
- `suggestion-autocomplete_controller.js` serves both US2 (entity search) and US5 (ISBN/year client validation) — built in US2, extended in US5
- `SuggestionVoter.php` (marked optional in plan.md) — omitted; `#[IsGranted('ROLE_USER')]` on controller routes is sufficient per current spec
- `toast_controller.js` (T053) is a shared utility — implement before or alongside T018/T035/T027 if inline toast appears in those tasks; T053 marked [P] in Phase 8 but can be pulled earlier if needed
- Commit after each checkpoint (end of each user story phase) for clean rollback points
