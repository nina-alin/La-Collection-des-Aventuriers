# Implementation Plan: Système de Notation et Commentaires

**Branch**: `009-book-review-rating` | **Date**: 2026-05-30 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/009-book-review-rating/spec.md`

## Summary

Allow authenticated users to rate books 1–10 with optional comment (max 1 000 chars), display community statistics (average, total count, last 4 evaluators, histogram), and show a filterable + paginated review list — all updated in real time via Turbo Streams after each write. Implemented with Symfony 7.2, Doctrine ORM, and the new `symfony/ux-turbo` dependency.

## Technical Context

**Language/Version**: PHP 8.2+, Symfony 7.2

**Primary Dependencies**: Doctrine ORM 3.6, `symfony/ux-turbo` (new — required), `symfony/stimulus-bundle` (existing), Bootstrap 5.3, PHPUnit 12.5

**Storage**: PostgreSQL 16 (Platform.sh)

**Testing**: PHPUnit 12.5 (unit + functional)

**Target Platform**: Platform.sh (Linux)

**Project Type**: Web application — Symfony MVC with Turbo Streams + Turbo Frames

**Performance Goals**: Filter/pagination response < 500ms perceived; Turbo Stream update perceived as instantaneous

**Constraints**: Twig only; Bootstrap + existing design assets (no new CSS frameworks); CSRF + `#[IsGranted]` on all mutating routes; no new managed infrastructure service

**Scale/Scope**: Community feature; potentially 1 000+ reviews per book

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-checked post-design below.*

### Principle I — Complémentarité Stricte
✅ **PASS**. Personal book ratings are a core personal-collection feature. Not a discussion forum, not news publishing. Complements, not competes with, La Taverne.

### Principle II — Architecture Symfony LTS
✅ **PASS**.
- `ReviewController`: HTTP only — form binding, format check, delegate to service, return response
- `ReviewService`: all business logic (upsert, delete, 409 handling, comment normalization)
- `ReviewRepository`: all DB access via Doctrine ORM
- `ReviewVoter`: Symfony Security component for delete permission
- DI throughout; no service locator

### Principle III — Workflow de Validation du Contenu
✅ **PASS** — Constitution v1.1.0 adds an explicit exception for quantitative `Review` ratings (immediate publication). See constitution.md Principle III Exception block.

### Principle IV — RBAC
✅ **PASS**.
- `#[IsGranted('IS_AUTHENTICATED_FULLY')]` on all mutating routes
- CSRF tokens on all forms
- `ReviewVoter` enforces: author → own review only; `ROLE_MODERATOR`/`ROLE_ADMIN` → any review

### Principle V — Sécurité et Couverture de Tests
✅ **PASS**.
- Unit tests: `ReviewTest`, `ReviewServiceTest`, `ReviewRepositoryTest` (stats calculation)
- Functional tests: CSRF check, auth redirect, score validation, duplicate handling, role-based delete

### Post-Design Re-check
Constitution v1.1.0 re-confirmed: no principles violated. Principle III exception (v1.1.0) covers Review entities. No new principles violated by the data-model (Review entity, ReviewStats DTO, Turbo Stream approach).

## Project Structure

### Documentation (this feature)

```text
specs/009-book-review-rating/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/
│   └── routes.md        # Phase 1 output
└── tasks.md             # Phase 2 output (/speckit-tasks — NOT created here)
```

### Source Code

```text
src/
├── Controller/
│   └── ReviewController.php              # NEW: submit (POST), delete (DELETE)
├── Entity/
│   └── Review.php                        # NEW
│   └── Book.php                          # MODIFIED: add OneToMany reviews
│   └── User.php                          # MODIFIED: add OneToMany reviews
├── Repository/
│   └── ReviewRepository.php              # NEW: stats query, paginated list
├── Service/
│   └── ReviewService.php                 # NEW: upsert + delete + 409 handling
├── Security/
│   └── Voter/
│       └── ReviewVoter.php               # NEW: CAN_DELETE permission
└── Twig/
    └── Extension/
        └── UserInitialsExtension.php     # NEW: user_initials(user) Twig filter

migrations/
└── VersionXXX.php                        # NEW: review table + indexes + constraints

templates/livre/
├── show.html.twig                        # MODIFIED: Turbo targets + community section
├── _stats_header.html.twig              # NEW: Turbo Stream target (avg, count, voters)
├── _histogram.html.twig                  # NEW: Turbo Stream target (10-bar histogram)
├── _review_form.html.twig               # NEW: Turbo Stream target (shield selector + textarea)
├── _reviews_list.html.twig              # NEW: Turbo Frame wrapper + paginated list
├── _review_item.html.twig               # NEW: single review card (avatar, meta, score)
└── _review_stream.html.twig             # NEW: 4-target Turbo Stream response template

assets/controllers/
├── shield-selector_controller.js        # NEW: Stimulus (rating pip + keyboard nav + aria)
├── char-counter_controller.js           # NEW: Stimulus (textarea char counter)
└── relative-date_controller.js          # NEW: Stimulus (Intl.RelativeTimeFormat, browser TZ)

tests/
├── Unit/
│   ├── Entity/
│   │   └── ReviewTest.php               # NEW: score range, comment normalization, timestamps
│   └── Service/
│       └── ReviewServiceTest.php        # NEW: upsert logic, delete, comment normalization
└── Functional/
    └── Controller/
        └── ReviewControllerTest.php     # NEW: auth, CSRF, validation, role-based delete
```

**Structure Decision**: Single Symfony MVC project — no new sub-projects. All new files added to existing src/ tree following established conventions (Entity, Service, Security/Voter).

## Complexity Tracking

| ~~Violation~~ Resolved | Resolution | Notes |
|-----------|------------|--------------------------------------|
| ~~Principle III: Reviews bypass PENDING moderation workflow~~ | Constitution amended to v1.1.0 — Principle III now includes an explicit exception for `Review` entities (quantitative ratings, immediate publication). No longer a violation. | Original rationale preserved: personal ratings are non-editorial; PENDING workflow would contradict SC-002, SC-007 and spec decision "Les notes soumises sont publiées immédiatement" |
