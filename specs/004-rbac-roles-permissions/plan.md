# Implementation Plan: RBAC — Roles & Permissions

**Branch**: `004-rbac-roles-permissions` | **Date**: 2026-05-24 | **Spec**: specs/004-rbac-roles-permissions/spec.md

**Input**: Feature specification from `/specs/004-rbac-roles-permissions/spec.md`

## Summary

Implement a three-tier RBAC system (ROLE_USER → ROLE_MODERATOR → ROLE_ADMIN) on the existing Symfony 7.2 application. This feature adds role hierarchy configuration, a banned-user request subscriber, soft-delete for user accounts, three new entities (WorkEntry, CorrectionProposal, ModerationLog), two new service classes (ModerationService, UserManagementService), two new controllers (ModerationController, AdminController), and a conditional navigation update — all backed by PHPUnit integration and unit tests.

## Technical Context

**Language/Version**: PHP 8.2+ / Symfony 7.2 LTS

**Primary Dependencies**: symfony/security-bundle (existing), doctrine/orm (existing), symfony/ux-twig-component (existing), phpunit/phpunit ^12.5 (existing), symfony/browser-kit (existing)

**Storage**: PostgreSQL 16 — existing managed service; one new migration adds two columns to `"user"` and creates three new tables. No new Platform.sh managed services introduced.

**Testing**: PHPUnit 12.5 with symfony/browser-kit for integration tests; unit tests for entities and services; no new test dependencies needed

**Target Platform**: Platform.sh (production), Docker Compose (local dev — from spec 003)

**Project Type**: Web application (Symfony MVC, Twig frontend)

**Performance Goals**: Standard web response times; moderation volume expected low; no special concurrency requirements (last-write-wins on concurrent moderation per spec)

**Constraints**:
- All data-mutating routes MUST use CSRF tokens and `#[IsGranted]` (Constitution IV)
- No new Platform.sh managed services → `.platform.app.yaml`, `.platform/routes.yaml`, `.platform/services.yaml` unchanged
- Controllers MUST be thin — business logic in Services (Constitution II)
- PHPUnit tests MUST cover all new entities and moderation workflows (Constitution V)

**Scale/Scope**: 3 new entities, 1 DB migration, 2 new services, 2 new controllers, 1 new security subscriber, 1 UserChecker, updated security.yaml, updated Navbar Twig component

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I — Complémentarité Stricte | ✅ PASS | RBAC + moderation are not forum/news features; they implement the collaborative encyclopedia quality workflow that is core to the platform's complementary identity |
| II — Architecture Symfony LTS | ✅ PASS | Thin controllers (HTTP I/O only), business logic in `ModerationService` and `UserManagementService`, Doctrine ORM throughout, DI everywhere. No new managed service → Platform.sh files untouched |
| III — Workflow de Validation | ✅ IMPLEMENTS | This feature directly implements the PENDING→PUBLISHED/REJECTED content workflow mandated by this principle |
| IV — RBAC Trois Niveaux | ✅ IMPLEMENTS | This feature directly implements the three-level RBAC model mandated by this principle. All data-mutating routes protected with CSRF + `#[IsGranted]` |
| V — Sécurité et Couverture | ✅ PASS | PHPUnit tests planned for all entities, services, and moderation workflows. CSRF + `#[IsGranted]` on every POST route |

**Post-design re-check**: All five gates still pass. No new infrastructure introduced. UserChecker + KernelEvents subscriber follow Symfony security conventions.

## Project Structure

### Documentation (this feature)

```text
specs/004-rbac-roles-permissions/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/
│   └── http-routes.md   # HTTP route + CSRF contracts
└── tasks.md             # Phase 2 output (/speckit-tasks command)
```

### Source Code (additions)

```text
src/
├── Entity/
│   ├── User.php                          # EXTEND: add status, deletedAt fields
│   ├── WorkEntry.php                     # NEW: id, title, status, author (nullable), createdAt
│   ├── CorrectionProposal.php            # NEW: id, workEntry, proposedContent, status, author (nullable), createdAt
│   └── ModerationLog.php                 # NEW: append-only audit entity
│
├── Repository/
│   ├── UserRepository.php                # EXTEND: countActiveAdministrators, countAccountsWithModerationCapability, loadUserByIdentifier (exclude deleted)
│   ├── WorkEntryRepository.php           # NEW: findPending()
│   └── CorrectionProposalRepository.php  # NEW: findPending()
│
├── Service/
│   ├── ModerationService.php             # NEW: approve, reject, editPending (+ ModerationLog creation)
│   └── UserManagementService.php         # NEW: changeRole, banUser, softDeleteUser (+ guards)
│
├── Security/
│   ├── GoogleAuthenticator.php           # UNCHANGED
│   └── UserChecker.php                   # NEW: blocks banned users at login
│
├── EventSubscriber/
│   ├── AuthenticationEventSubscriber.php # UNCHANGED
│   └── BannedUserSubscriber.php          # NEW: KernelEvents::REQUEST, priority 8, blocks banned sessions
│
└── Controller/
    ├── ModerationController.php          # NEW: /moderation/* (dashboard, approve, reject, edit)
    └── AdminController.php               # NEW: /admin/* (users, role, ban, delete, settings)

templates/
├── moderation/
│   └── dashboard.html.twig               # NEW: PENDING queue listing
├── admin/
│   └── users.html.twig                   # NEW: user management table
└── components/
    └── Layout/
        └── Navbar.html.twig              # EXTEND: conditional moderation/admin links

migrations/
└── Version20260524000000.php             # NEW: User columns + 3 new tables

config/packages/
└── security.yaml                         # EXTEND: role_hierarchy, UserChecker, access_control rules
```

## Implementation Notes

### BannedUserSubscriber

Fires on `KernelEvents::REQUEST` (priority 8, after Symfony's firewall listener at priority 8 but before access control). Checks `$token->getUser()->getStatus() === 'banned'` for authenticated requests. Returns 403 or redirects to login. Skips public routes and unauthenticated requests.

> Priority note: Symfony firewall runs at priority 8. The subscriber must run *after* the firewall has set the security token. Use priority 7 to ensure correct ordering.

### UserChecker

`App\Security\UserChecker` implements `UserCheckerInterface`. `checkPreAuth()` throws `CustomUserMessageAccountStatusException` for deleted users (double-check; primary exclusion is in UserProvider). `checkPostAuth()` throws for banned users. Registered in `security.yaml` under `firewalls.main.user_checker`.

### UserManagementService Guards

All three mutating methods (`changeRole`, `banUser`, `softDeleteUser`) apply guards in this order:
1. Self-action check (FR-014): actor === target → throw
2. Last-admin check (FR-012): would the action leave zero active admins? → throw
3. For demotion only — last-moderator check (FR-015): would this leave zero accounts with moderation capability (no ROLE_MODERATOR, no ROLE_ADMIN)? → throw

### ModerationLog Append-Only

`ModerationLog` has no `setCreatedAt` or `setId` public setter. `PreUpdate` callback: always throws `\LogicException`. `PreRemove` callback: always throws `\LogicException`.

### WorkEntry / CorrectionProposal Status Enforcement

`ModerationService` always validates current status is `PENDING` before applying a transition. Attempting to transition from a terminal state throws `\InvalidArgumentException`. Callers (controllers) catch this and return a user-facing error flash.

### Role Hierarchy in security.yaml

```yaml
role_hierarchy:
  ROLE_ADMIN: [ROLE_MODERATOR]
  ROLE_MODERATOR: [ROLE_USER]
```

### Access Control in security.yaml

Insert before the catch-all rule:
```yaml
- { path: ^/moderation, roles: ROLE_MODERATOR }
- { path: ^/admin,      roles: ROLE_ADMIN }
```

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No violations. No complexity entries required.
