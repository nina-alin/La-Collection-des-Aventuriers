# Tasks: Système de Notation et Commentaires

**Input**: Design documents from `specs/009-book-review-rating/`

**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/routes.md ✅, quickstart.md ✅

**Tests**: Included — test files explicitly required by plan.md (Constitution Principle V) and listed in project structure.

**Organization**: Tasks grouped by user story for independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Maps task to user story (US1, US2, US3)
- Exact file paths included in every description

---

## Phase 1: Setup (New Dependency)

**Purpose**: Install `symfony/ux-turbo` and wire it into the asset pipeline.

- [X] T001 Install symfony/ux-turbo via `composer require symfony/ux-turbo`
- [X] T002 Add `import '@symfony/ux-turbo';` to `assets/app.js`
- [X] T003 Run `npm install --force` to resolve `@hotwired/turbo` peer-dep conflict

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Create the `Review` entity, update related entities, generate and apply the Doctrine migration. No user story can begin until this phase is complete.

**⚠️ CRITICAL**: This phase BLOCKS all user stories.

- [X] T004 Create `src/Entity/Review.php` with all fields (`id`, `score`, `comment`, `createdAt`, `updatedAt`, `book`, `user`), ORM attributes (`#[UniqueConstraint]`, `#[HasLifecycleCallbacks]`), validation constraints (`NotBlank`, `Range(1-10)`, `Length(max:1000)`), lifecycle callbacks (`PrePersist`, `PreUpdate`), and `setComment()` normalization (empty string → null)
- [X] T005 [P] Modify `src/Entity/Book.php` to add reverse side of OneToMany: `$reviews` collection with `#[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'book', cascade: ['remove'], orphanRemoval: true)]`
- [X] T006 [P] Modify `src/Entity/User.php` to add reverse side of OneToMany: `$reviews` collection with `#[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'user')]` (no cascade — SET NULL handled at DB level)
- [X] T007 Generate Doctrine migration via `php bin/console doctrine:migrations:diff` — verify SQL creates `review` table with FK to `book.id` (CASCADE), FK to `"user".id` (SET NULL), unique constraint `(user_id, book_id)`, indexes `idx_review_book_id` and `idx_review_book_updated_at (book_id, updated_at DESC)`
- [X] T008 Apply migration via `php bin/console doctrine:migrations:migrate`

**Checkpoint**: Foundation ready — `review` table exists, entities mapped. User story implementation can begin.

---

## Phase 3: User Story 1 — Soumission d'un avis (Priority: P1) 🎯 MVP

**Goal**: Authenticated user selects score 1–10, optionally adds comment, submits review. Upserts if review exists. Turbo Stream updates 4 targets on success. Delete own review also supported.

**Independent Test**: Submit a review via POST `/livre/{slug}/avis` and verify DB persistence; delete via DELETE `/livre/{slug}/avis/{id}` and verify removal. Works with fixture data only, no stats/list UI required.

### Tests for User Story 1

- [X] T009 [P] [US1] Write unit test `tests/Unit/Entity/ReviewTest.php`: score range validation (1 ok, 10 ok, 0 rejected, 11 rejected), `setComment('')` normalizes to null, `setComment(null)` stays null, `createdAt`/`updatedAt` set on construction
- [X] T010 [P] [US1] Write unit test `tests/Unit/Service/ReviewServiceTest.php`: upsert creates new Review when none exists, upsert updates existing Review (score + comment replaced, `updatedAt` refreshed), delete removes entity, comment normalization delegated to entity, `UniqueConstraintViolationException` results in 409
- [X] T011 [P] [US1] Write functional test `tests/Functional/Controller/ReviewControllerTest.php` — submit scenarios: unauthenticated → 302 to login, missing score → 422 with error, valid submit creates Review → 302 (non-Turbo) or Turbo Stream response, duplicate submit updates existing → no duplicate row, CSRF missing → 422, race-condition `UniqueConstraintViolationException` → HTTP 409 with error message; delete scenarios: unauthenticated DELETE → 302 to login, author DELETE own review → success (Turbo Stream or redirect), `ROLE_MODERATOR` DELETE another user's review → success, `ROLE_USER` DELETE another user's review → 403, CSRF missing on DELETE → 422

### Implementation for User Story 1

- [X] T012 [P] [US1] Create `src/Repository/ReviewRepository.php` with `findByUserAndBook(User $user, Book $book): ?Review` method (DQL `WHERE r.user = :user AND r.book = :book`)
- [X] T013 [P] [US1] Create `src/Twig/Extension/UserInitialsExtension.php` implementing `user_initials(?User $user): ?string` Twig filter — split `displayName` on first space, take first char of each part, `mb_strtoupper`; return `null` if user null, displayName null, or either part empty
- [X] T014 [US1] Create `src/Service/ReviewService.php` with `submit(User $user, Book $book, int $score, ?string $comment): Review` (find-or-create upsert via `ReviewRepository::findByUserAndBook`, catch `UniqueConstraintViolationException` → throw typed exception for 409) and `delete(Review $review): void` (remove + flush)
- [X] T015 [P] [US1] Create `src/Security/Voter/ReviewVoter.php` supporting attribute `CAN_DELETE`: GRANT if `$user === $review->getUser()`, GRANT if user has `ROLE_MODERATOR` or `ROLE_ADMIN`, otherwise DENY
- [X] T016 [P] [US1] Create `assets/controllers/shield-selector_controller.js` Stimulus controller: render 10 rating pips, wrap in element with `role="radiogroup"`, each pip has `aria-label="Note X sur 10"`, click → sets selected pip class + hidden input value, keyboard: ArrowLeft/ArrowRight to move selection, Enter/Space to confirm
- [X] T017 [P] [US1] Create `assets/controllers/char-counter_controller.js` Stimulus controller: connect to textarea `data-char-counter-target="input"`, display live count `X / 1000`, add `is-over-limit` class and disable submit when count > 1000
- [X] T018 [US1] Create `src/Controller/ReviewController.php` with two routes: `POST /livre/{slug}/avis` (`app_book_review_submit`, `#[IsGranted('IS_AUTHENTICATED_FULLY')]`) — bind form, call `ReviewService::submit`, return Turbo Stream via `_review_stream.html.twig` if `TurboBundle::STREAM_FORMAT`, else redirect to `app_book_show`; `DELETE /livre/{slug}/avis/{id}` (`app_book_review_delete`, `#[IsGranted('IS_AUTHENTICATED_FULLY')]`) — check `ReviewVoter::CAN_DELETE`, call `ReviewService::delete`, return Turbo Stream or redirect
- [X] T019 [P] [US1] Create `templates/livre/_review_form.html.twig`: shield-selector Stimulus wrapper (10 pips, hidden score input), char-counter textarea (optional comment, max 1000), CSRF token field `review_token`, conditional submit button ("Publier mon avis" when no existing review, "Modifier mon avis" + delete button when existing review pre-filled)
- [X] T020 [P] [US1] Create `templates/livre/_review_stream.html.twig`: 4 `<turbo-stream action="replace">` targets — `stats-header` (include `_stats_header.html.twig`), `histogram` (include `_histogram.html.twig`), `reviews-list` (include `_reviews_list.html.twig`), `review-form` (include `_review_form.html.twig`)
- [X] T021 [US1] Modify `templates/livre/show.html.twig`: add `<div id="stats-header">`, `<div id="histogram">`, `<div id="review-form">` Turbo Stream target wrappers; add `<!-- reviews-list: T034 inserts <turbo-frame id="reviews-list"> here -->` comment at the reviews list position; embed `_review_form.html.twig` partial (note: `<div id="reviews-list">` is intentionally omitted — T034 inserts the `<turbo-frame>` directly, which also serves as the Turbo Stream replace target)

**Checkpoint**: User Story 1 fully functional — authenticated users can rate, upsert, and delete their review; Turbo Stream updates 4 targets on success.

---

## Phase 4: User Story 2 — Consultation des statistiques de notation (Priority: P2)

**Goal**: Any visitor sees community stats in the page header: average score (rounded to 1 decimal), total count, last 4 evaluator initials, and distribution histogram (10 bars, linear height).

**Independent Test**: Load book page with fixture reviews and verify stats header shows correct average, count, last 4 evaluators; histogram bars have heights proportional to distribution. Works without US3 filter/pagination.

### Tests for User Story 2

- [X] T022 [P] [US2] Write unit test `tests/Unit/Repository/ReviewRepositoryTest.php` (or unit test for stats calculation): `getStatsForBook` with 10 reviews returns correct `averageScore` (rounded, `PHP_ROUND_HALF_UP`), `totalCount = 10`, `distribution[10]` per-score counts, `histogramHeights[10]` (max bar = 100.0), `lastEvaluators` max 4 ordered by `updatedAt` DESC; with 0 reviews returns zeros and empty arrays

### Implementation for User Story 2

- [X] T023 [P] [US2] Create `src/Dto/ReviewStats.php` readonly class with constructor properties: `float $averageScore`, `int $totalCount`, `array $distribution` (int[10], index 0=score 1), `array $histogramHeights` (float[10], 0.0–100.0), `array $lastEvaluators` (?User[], max 4)
- [X] T024 [US2] Add `getStatsForBook(Book $book): ReviewStats` to `src/Repository/ReviewRepository.php`: DQL `SELECT r.score, COUNT(r) as cnt FROM Review r WHERE r.book = :book GROUP BY r.score` for distribution; `AVG(r.score)` for average; separate query `ORDER BY r.updatedAt DESC LIMIT 4` for lastEvaluators; compute `histogramHeights` as `count / max_count * 100.0` (0.0 when no reviews)
- [X] T025 [P] [US2] Create `templates/livre/_stats_header.html.twig`: display `reviewStats.averageScore` (or "Aucune évaluation pour l'instant" when `totalCount == 0`), "X aventuriers ont noté ce livre", last 4 evaluator avatars using `user_initials(user)` filter (show `<img src="/images/avatar-placeholder.svg">` when null)
- [X] T026 [P] [US2] Create `templates/livre/_histogram.html.twig`: 10 bars (score 1–10), each `<div class="histo-bar" style="height: {{ reviewStats.histogramHeights[i] }}%">`, hidden/empty when `totalCount == 0`
- [X] T027 [US2] Update the existing show action in `src/Controller/LivreController.php` to: (1) call `ReviewRepository::getStatsForBook($book)` and pass `reviewStats` to `templates/livre/show.html.twig`; (2) call `ReviewRepository::findByUserAndBook($currentUser, $book)` — null-safe when unauthenticated (`$currentUser = $this->getUser()`, pass `null` to repository which returns `null`) — and pass result as `userReview` to the show template (consumed by `_review_form.html.twig` for pre-fill and conditional button); also pass both `reviewStats` and `userReview` to the `_review_stream.html.twig` response context

**Checkpoint**: User Stories 1 and 2 functional — stats and histogram render correctly on page load and update via Turbo Stream after review submit/delete.

---

## Phase 5: User Story 3 — Consultation et filtrage de la liste des avis (Priority: P3)

**Goal**: Visitor sees paginated review list (10/page) inside a Turbo Frame. Two filter buttons (RÉCENTES default, AVEC COMMENTAIRE). Both sort by `updatedAt` DESC. Pagination links preserve active filter. Each review card shows avatar, name, role badge (mod/admin), relative date (browser timezone), comment, score.

**Independent Test**: Load `GET /livre/{slug}/avis?filter=avec_commentaire` with fixtures (mixed reviews with/without comments) and verify: only non-null comments shown; pagination hides when ≤ 10 results; empty state message when 0 results; filter param preserved in pagination links.

### Tests for User Story 3

- [X] T028 [P] [US3] Add functional tests to `tests/Functional/Controller/ReviewControllerTest.php` for `GET /livre/{slug}/avis`: default filter=recentes returns all reviews sorted by `updatedAt` DESC, filter=avec_commentaire excludes null/empty comments, pagination shows page 2 with correct offset, pagination controls hidden when ≤ 10 results, pagination links include `?filter=avec_commentaire&page=N`, empty result renders "Aucune évaluation pour l'instant"

### Implementation for User Story 3

- [X] T029 [US3] Add `findPaginatedByBook(Book $book, string $filter, int $page, int $perPage = 10): Paginator` to `src/Repository/ReviewRepository.php`: `WHERE r.book = :book`, `ORDER BY r.updatedAt DESC`, `setFirstResult(($page-1)*$perPage)`, `setMaxResults($perPage)`; if `$filter === 'avec_commentaire'` add `AND r.comment IS NOT NULL`; return `new Paginator($qb, fetchJoinCollection: false)`
- [X] T030 [US3] Add `GET /livre/{slug}/avis` route (`app_book_reviews`, public, no `#[IsGranted]`) to `src/Controller/ReviewController.php`: read `filter` (default `recentes`) and `page` (default `1`) from query params, call `ReviewRepository::findPaginatedByBook`, compute `totalPages = ceil(count($paginator) / 10)`, render `templates/livre/_reviews_list.html.twig`
- [X] T031 [P] [US3] Create `assets/controllers/relative-date_controller.js` Stimulus controller: reads `data-relative-date-timestamp-value` (ISO 8601 string), computes diff from `Date.now()`, formats with `new Intl.RelativeTimeFormat(document.documentElement.lang || 'fr', { numeric: 'auto' })`, picks unit (second/minute/hour/day/month/year) by magnitude, sets `this.element.textContent`
- [X] T032 [P] [US3] Create `templates/livre/_review_item.html.twig`: avatar div using `user_initials(review.user)` (if null → `<img src="/images/avatar-placeholder.svg">`), author `displayName`, role badge `<span class="role-pip role-pip.admin">` if user has ROLE_MODERATOR or ROLE_ADMIN, `<span data-controller="relative-date" data-relative-date-timestamp-value="{{ review.updatedAt|date('c') }}">{{ review.updatedAt|date('d/m/Y') }}</span>`, comment block (only if non-null), mini-shield showing score
- [X] T033 [US3] Create `templates/livre/_reviews_list.html.twig`: `<turbo-frame id="reviews-list">`, filter buttons `<button aria-pressed="true/false">RÉCENTES</button>` and `<button>AVEC COMMENTAIRE</button>` as links with `data-turbo-frame="reviews-list"` href `{{ path('app_book_reviews', {slug: book.slug, filter: '...', page: 1}) }}`, loop `{% for review in paginator %} {% include '_review_item.html.twig' %}`, pagination links `{% set params = app.request.query.all|merge({page: p, slug: book.slug}) %} <a href="{{ path('app_book_reviews', params) }}" data-turbo-frame="reviews-list">`, `{% if totalPages > 1 %}` guard on pagination controls, "Aucune évaluation pour l'instant" when paginator count = 0
- [X] T034 [US3] Modify `templates/livre/show.html.twig`: replace static reviews placeholder with `<turbo-frame id="reviews-list" src="{{ path('app_book_reviews', {slug: book.slug}) }}">{% include 'livre/_reviews_list.html.twig' %}</turbo-frame>`

**Checkpoint**: All 3 user stories functional — reviews can be submitted, stats display correctly, filtered/paginated list works in Turbo Frame.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Accessibility verification, design fidelity, and full test run.

- [X] T035 [P] Verify shield-selector accessibility in `assets/controllers/shield-selector_controller.js`: container has `role="radiogroup"`, each pip has `aria-label="Note X sur 10"` (FR-021), keyboard navigation works (ArrowLeft/ArrowRight move selection, Enter/Space confirm)
- [X] T036 [P] Design fidelity audit: compare rendered `templates/livre/show.html.twig` and partials against `design/pages/livre.html` — verify CSS classes `.rating-pip`, `.rating-pip.is-on`, `.histo-bar`, `.reviews-filter button[aria-pressed]`, `.review`, `.mini-shield`, `.role-pip`, `.role-pip.admin` are applied correctly; verify `public/images/avatar-placeholder.svg` exists (referenced by `_stats_header.html.twig` and `_review_item.html.twig` when user has no initials) — add the file if absent
- [X] T037 Run full test suite `php bin/phpunit` and confirm all unit + functional tests pass

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Phase 1 completion — BLOCKS all user stories
- **User Story 1 (Phase 3)**: Depends on Phase 2 completion
- **User Story 2 (Phase 4)**: Depends on Phase 2 completion — may run in parallel with US1 after Phase 2
- **User Story 3 (Phase 5)**: Depends on Phase 2 completion — requires US1 ReviewController to add the `app_book_reviews` route
- **Polish (Phase 6)**: Depends on all user stories complete

### User Story Dependencies

- **US1 (P1)**: No cross-story dependencies — independent after Phase 2
- **US2 (P2)**: Depends on Review entity from Phase 2; adds to ReviewRepository and show template; independent of US1 logic
- **US3 (P3)**: `app_book_reviews` route added to same ReviewController created in US1 (T018/T030) — T030 extends the controller, not a hard dependency; ReviewRepository paginator method (T029) independent

### Within Each User Story

- Tests written first (TDD red phase)
- Repository before Service (T012 before T014)
- Service + Voter before Controller (T014, T015 before T018)
- Partials before stream template (T019 before T020)
- Stream template and partials before show.html.twig modification (T020 before T021)

### Parallel Opportunities

Within Phase 2: T005 and T006 run in parallel (different entity files). T007 must wait for T004, T005, T006.

Within Phase 3: T009, T010, T011 (tests) in parallel. T012, T013, T015, T016, T017, T019, T020 in parallel. T014 after T012. T018 after T014 + T015. T021 after T018 + T019 + T020.

Within Phase 4: T022, T023 in parallel. T024 after T023. T025, T026 in parallel after T023. T027 after T024 + T025 + T026.

Within Phase 5: T028, T031, T032 in parallel. T029 before T030. T033 after T032 + T029. T034 after T033.

---

## Parallel Example: User Story 1

```bash
# Parallel: write all tests first
T009: tests/Unit/Entity/ReviewTest.php
T010: tests/Unit/Service/ReviewServiceTest.php
T011: tests/Functional/Controller/ReviewControllerTest.php (submit scenarios)

# Parallel: independent infrastructure
T012: src/Repository/ReviewRepository.php (findByUserAndBook)
T013: src/Twig/Extension/UserInitialsExtension.php
T015: src/Security/Voter/ReviewVoter.php
T016: assets/controllers/shield-selector_controller.js
T017: assets/controllers/char-counter_controller.js
T019: templates/livre/_review_form.html.twig
T020: templates/livre/_review_stream.html.twig

# Sequential after T012
T014: src/Service/ReviewService.php

# Sequential after T014 + T015
T018: src/Controller/ReviewController.php

# Sequential after T018 + T019 + T020
T021: templates/livre/show.html.twig (add turbo targets)
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (T001–T003)
2. Complete Phase 2: Foundational (T004–T008) — CRITICAL
3. Complete Phase 3: User Story 1 (T009–T021)
4. **STOP and VALIDATE**: Users can rate books, Turbo Stream updates work
5. Deploy/demo MVP

### Incremental Delivery

1. Setup + Foundational → `review` table live
2. US1 → Rate and delete reviews with Turbo Stream updates (MVP!)
3. US2 → Stats header + histogram display
4. US3 → Filtered + paginated review list in Turbo Frame
5. Each story adds independent value

---

## Notes

- [P] = different files, no incomplete dependencies
- `setRequestFormat(TurboBundle::STREAM_FORMAT)` must be called **before** `render()` (research.md gotcha)
- Turbo Frame response for `app_book_reviews` must include matching `<turbo-frame id="reviews-list">` or Turbo silently does nothing
- `npm install --force` required (not just `npm install`) for `@hotwired/turbo` peer-dep
- Empty string comment → null normalization happens in `Review::setComment()` (entity-level), not only in service
- `user_id` nullable in Review — anonymized reviews (user deleted) must not break display; template must handle `review.user === null`
- Doctrine `Paginator` count = total results; `ceil(count / perPage)` = totalPages
