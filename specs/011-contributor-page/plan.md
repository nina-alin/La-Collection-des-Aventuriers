# Implementation Plan: Page Contributeur — Suggestions

**Branch**: `011-contributor-page` | **Date**: 2026-05-30 | **Spec**: [specs/011-contributor-page/spec.md](spec.md)

**Input**: Feature specification from `/specs/011-contributor-page/spec.md`

## Summary

Implement the contributor suggestion page (`/mes-suggestions`) — a split-panel interface where authenticated users submit new encyclopedia entries or corrections via a 4-step LiveComponent wizard, and monitor their submission history via a Stimulus-polled side panel. The page introduces three new entities (`Suggestion`, `SuggestionRefusal`, `ContributorLevel`), requires installing `symfony/ux-live-component`, and exposes four new backend endpoints (page, polling, autocomplete, entity creation). Design reference: `design/pages/suggestions.html`.

## Technical Context

**Language/Version**: PHP 8.2+ (platform.sh runtime: 8.3), Symfony 7.2 LTS

**Primary Dependencies**:
- `symfony/ux-live-component` — **NOT YET INSTALLED** (must be added via Composer before implementation)
- `symfony/ux-twig-component ^2.35` — installed (existing Twig components in `src/Twig/Components/`)
- `symfony/stimulus-bundle ^2.35` — installed
- `symfony/ux-turbo ^2.36` — installed
- `doctrine/orm ^3.6` — installed, Doctrine Attribute mapping
- `symfony/security-bundle 7.2` — installed
- `symfony/webpack-encore-bundle ^2.4` — installed
- `gedmo/doctrine-extensions` (via `stof/doctrine-extensions-bundle`) — installed (SoftDeleteable, Slug)

**Storage**: PostgreSQL 16 (Platform.sh managed service `database`). Existing tables: `book`, `contributor`, `contribution`, `correction_proposal`, `user`, etc.

**Testing**: PHPUnit (`tests/Unit/`, `tests/Integration/`, `tests/Functional/`). No Pest. Existing patterns: functional controller tests in `tests/Functional/Controller/`, integration tests in `tests/Integration/`.

**Target Platform**: Platform.sh (`.platform.app.yaml`, `.platform/routes.yaml`, `.platform/services.yaml` — no new infrastructure services required for this feature; no Redis/queue needed)

**Project Type**: Symfony web application — Twig templates, Doctrine ORM, Symfony Security

**Performance Goals**:
- Polling endpoint ≤ 100ms p95 (simple query, 50-row hard cap)
- Autocomplete endpoint ≤ 200ms p95 (indexed LIKE query)
- Live-validation debounce 300ms, visual feedback ≤ 500ms (SC-002)
- Local field validation (Date/ISBN) ≤ 100ms (SC-003)

**Constraints**:
- No WebSockets — polling via Stimulus (30s interval, FR-021)
- No draft persistence — wizard data lives in LiveComponent server-side state only (FR-015 clarification)
- Max 20 pending suggestions per user (hard cap, FR-018)
- Max 50 suggestions displayed in side panel (hard cap, FR-021)
- WCAG 2.1 AA required (FR-025)
- No new CSS frameworks — Bootstrap + existing design tokens (constitution)
- No new JS frameworks — Stimulus controllers only (constitution)
- Diff computed at LiveComponent render time (`$originalData` vs current state) — never persisted (FR-017)

**Scale/Scope**: Single authenticated-user page; ~3–5 concurrent users realistic; no caching layer needed

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Complémentarité Stricte | ✅ PASS | Suggestion page is encyclopedia contribution management — within scope (personal collection + collaborative encyclopedia). No forum/news feature introduced. |
| II. Architecture Symfony LTS | ✅ PASS | Controller thin (route + security + render only). Business logic in `SuggestionService`, `ContributorLevelService`. Doctrine ORM exclusively. DI throughout. No new Platform.sh infrastructure services → no `.platform.*` file changes required. |
| III. Workflow de Validation | ✅ PASS | All `Suggestion` entities default to `PENDING` status. Only `ROLE_MODERATOR`/`ROLE_ADMIN` may transition to `VALIDATED`/`REFUSED`. Contribution data submitted by `ROLE_USER` never auto-published. |
| IV. RBAC | ✅ PASS | Page protected by `#[IsGranted('ROLE_USER')]`. Submission route protected by CSRF token + `#[IsGranted]`. Autocomplete and polling endpoints also require `ROLE_USER`. |
| V. Sécurité et Couverture de Tests | ✅ PASS | PHPUnit tests required for: `Suggestion` entity, `SuggestionRefusal` entity, `ContributorLevel` entity, `SuggestionService`, `ContributorLevelService`, controller access control, CSRF coverage. |

**Post-design re-check**: No violations anticipated. LiveComponent state management (wizard steps) is server-side; no client-side state bypass possible.

## Project Structure

### Documentation (this feature)

```text
specs/011-contributor-page/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── endpoints.md
└── tasks.md             # Phase 2 output (/speckit-tasks — NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
src/
├── Controller/
│   └── SuggestionController.php            # NEW — thin: renders page + handles polling/autocomplete/entity-create endpoints
├── Entity/
│   ├── Enum/
│   │   ├── SuggestionStatus.php            # NEW — PENDING | VALIDATED | REFUSED
│   │   ├── SuggestionEntityType.php        # NEW — BOOK | AUTHOR | ILLUSTRATOR | TRADUCTOR | EDITOR | COLLECTION
│   │   ├── SuggestionMode.php              # NEW — NEW_ENTRY | CORRECTION
│   │   └── SuggestionRefusalAction.php     # NEW — VOIR_FICHE | MASQUER (contextual refusal actions)
│   ├── Suggestion.php                      # NEW — core suggestion entity
│   ├── SuggestionRefusal.php               # NEW — refusal details with actions array
│   └── ContributorLevel.php                # NEW — rank definitions (thresholds, names)
├── Repository/
│   ├── SuggestionRepository.php            # NEW
│   └── ContributorLevelRepository.php      # NEW
├── Service/
│   ├── SuggestionService.php               # NEW — submit, quota check, pending count
│   ├── ContributorLevelService.php         # NEW — rank computation, acceptance rate
│   └── CoverImageProcessor.php            # NEW — 3×4 crop, format validation, 4MB limit
├── Twig/
│   └── Components/
│       └── Suggestion/
│           └── WizardComponent.php         # NEW — AsLiveComponent, 4-step wizard state
└── Security/
    └── Voter/
        └── SuggestionVoter.php             # NEW (optional — if per-suggestion access logic needed)

templates/
├── suggestion/
│   └── index.html.twig                     # NEW — page shell: dashboard banner + split panel
└── components/
    └── Suggestion/
        ├── WizardComponent.html.twig       # NEW — LiveComponent template (4 steps)
        ├── StepType.html.twig              # NEW — Step 1: mode + entity type
        ├── StepForm.html.twig              # NEW — Step 2: Book form fields
        ├── StepCover.html.twig             # NEW — Step 3: image upload zone
        └── StepPreview.html.twig           # NEW — Step 4: preview + diff + submit

assets/
└── controllers/
    ├── suggestion-upload_controller.js     # NEW — Stimulus: drag-and-drop, upload states
    ├── suggestion-autocomplete_controller.js # NEW — Stimulus: debounced search, ARIA listbox
    ├── suggestion-polling_controller.js    # NEW — Stimulus: 30s poll, badge update, error tracking
    ├── suggestion-tabs_controller.js       # NEW — Stimulus: mobile Action/Suivi tabs
    └── suggestion-abandon_controller.js    # NEW — Stimulus: beforeunload guard + custom modal

migrations/
└── VersionXXXXXXXXXXXX.php               # NEW — adds suggestion, suggestion_refusal, contributor_level tables

tests/
├── Unit/
│   ├── Entity/
│   │   ├── SuggestionTest.php              # NEW
│   │   └── ContributorLevelTest.php        # NEW
│   └── Service/
│       ├── SuggestionServiceTest.php       # NEW
│       └── ContributorLevelServiceTest.php # NEW
├── Integration/
│   └── Controller/
│       └── SuggestionControllerTest.php    # NEW — access control, CSRF, quota enforcement
└── Functional/
    └── Accessibility/
        └── SuggestionPageA11yTest.php      # NEW — axe-core audit (WCAG 2.1 AA)
```

**Structure Decision**: Single Symfony web application (Option 1 pattern). Feature adds a new controller, 3 entities, 2 services, 1 LiveComponent, 5 Stimulus controllers. All changes in existing `src/` and `templates/` trees. No new top-level directories.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No constitution violations. No complexity tracking entries needed.

---

## Phase 0 Research Findings

See `research.md` for full details. Summary of decisions:

| Topic | Decision |
|-------|----------|
| LiveComponent install | `composer require symfony/ux-live-component` — no config changes needed beyond bundle auto-registration |
| Wizard state storage | LiveComponent server-side PHP properties — no session/DB draft storage |
| Diff computation | Pure PHP array comparison in `WizardComponent::computeDiff()` at render time |
| Autocomplete fallback | 3s timeout via Stimulus; field switches to `data-fallback="true"` → plain `<input>` |
| Image processing | PHP GD or Imagick (already available on Platform.sh PHP 8.3) — `CoverImageProcessor` |
| Polling error handling | Stimulus tracks consecutive failures; after 3 → shows `data-suspended` indicator |
| ContributorLevel seeding | Seeded via Doctrine fixtures (6 levels defined in spec) |
| ISBN validation | Client-side only: Stimulus controller computes check digit (no backend round-trip) |
| Entity autocreate | Dedicated POST endpoint per entity type; creates DB entry, returns `{id, label}` JSON |
| Mobile breakpoint | `< 1080px` → CSS media query toggles tab layout; Stimulus `suggestion-tabs` manages ARIA |
