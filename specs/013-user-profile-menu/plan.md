# Implementation Plan: Menu Profil Utilisateur Responsive

**Branch**: `013-user-profile-menu` | **Date**: 2026-05-31 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/013-user-profile-menu/spec.md`

## Summary

Replace the inline user dropdown in `Navbar.html.twig` with a dedicated `ProfileMenu` Twig Component that renders as a desktop dropdown (≥720px) and a mobile bottom-sheet (<720px). All data (rank, validated suggestion count, pending moderation count) is loaded server-side via `ProfileMenuService` injected into the component — no async fetch. A single Stimulus controller (`profile-menu`) handles open/close, swipe-to-dismiss, keyboard navigation, and focus trap.

## Technical Context

**Language/Version**: PHP 8.2+ / Symfony 7.x LTS, JavaScript (Stimulus 3.x)

**Primary Dependencies**:
- Symfony UX Twig Components (existing)
- Symfony Security (`is_granted`, `#[IsGranted]`)
- Doctrine ORM — `SuggestionRepository`, `WorkEntryRepository`, `CorrectionProposalRepository`, `ContributorLevelRepository`
- `ContributorLevelService` (existing — `computeRank()`)
- Bootstrap + custom CSS (`design/assets/menus.css` — already provides all component styles)

**Storage**: PostgreSQL (existing, no schema change)

**Testing**: PHPUnit with `InteractsWithTwigComponents` trait (pattern from `tests/Twig/Components/BadgeTest.php`)

**Target Platform**: Symfony web app — server-side rendered Twig, progressive enhancement via Stimulus

**Performance Goals**: Zero async calls — all data loaded at page render via Repository queries

**Constraints**:
- No new CSS frameworks; `menus.css` provides `.user-card`, `.menu-card`, `.menu-link`, `.menu-toggle-row`, `.logout-section` — use as-is
- No new JS frameworks; Stimulus only
- No new API endpoints (FR-007, FR-008, FR-010 — Repository at render time only)
- Reuse existing logout form/handler (FR-013)

**Project Type**: web-service (Symfony full-stack)

**Scale/Scope**: 1 new Twig Component, 1 new Stimulus controller, 1 new service, 2 new `countPending()` repo methods

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Complémentarité | ✅ PASS | User profile menu is collection-management UI, not a forum or news feature |
| II. Architecture Symfony LTS | ✅ PASS | Twig Component + thin Navbar, business data via dedicated `ProfileMenuService`, DI throughout |
| III. Validation Workflow | ✅ N/A | Feature renders data; does not submit user content |
| IV. RBAC — Trois Niveaux | ✅ PASS | Moderation section gated by `is_granted('ROLE_MODERATOR')` in Twig; badge gated by role check in component; no privilege bypass |
| V. Sécurité & Tests | ✅ PASS (with obligation) | Must deliver PHPUnit tests for: role badge visibility (3 roles), moderation section DOM gating, ARIA attributes on trigger |

**GATE RESULT: PASSED** — no violations; proceed to Phase 0.

## Project Structure

### Documentation (this feature)

```text
specs/013-user-profile-menu/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/
│   ├── profile-menu-component.md   # Twig Component props contract
│   └── profile-menu-stimulus.md    # Stimulus controller targets/values
└── tasks.md             # Phase 2 output (/speckit-tasks)
```

### Source Code (repository root)

```text
src/
├── Twig/Components/Layout/
│   ├── Navbar.php                  # existing — add ProfileMenuService injection
│   └── ProfileMenu.php             # NEW — AsTwigComponent, injected service
├── Service/
│   └── ProfileMenuService.php      # NEW — aggregates rank, validatedCount, pendingModerationCount
└── Repository/
    ├── WorkEntryRepository.php      # ADD countPending(): int
    └── CorrectionProposalRepository.php  # ADD countPending(): int

templates/
└── components/Layout/
    ├── Navbar.html.twig             # existing — replace inline dropdown with <twig:Layout:ProfileMenu />
    └── ProfileMenu.html.twig        # NEW — full menu markup from design/dashboard.html:771-832

assets/controllers/
└── profile_menu_controller.js       # NEW — Stimulus: open/close, swipe, keyboard, focus trap

tests/
└── Twig/Components/
    └── ProfileMenuTest.php          # NEW
```

**Structure Decision**: Single Symfony project (existing layout). `ProfileMenu` is a child component embedded by `Navbar.html.twig`, matching the existing pattern (Footer, FlashBag are also sub-components of Layout).

## Complexity Tracking

> No constitution violations — table not required.
