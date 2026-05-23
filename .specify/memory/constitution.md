<!--
## Sync Impact Report

**Version Change**: [template] → 1.0.0
**Modified Principles**: N/A (initial ratification — all principles new)
**Added Sections**: All (initial fill from template)
**Removed Sections**: None
**Templates Requiring Updates**:
- ✅ `.specify/templates/plan-template.md` — "Constitution Check" gate aligns with 5 principles
- ✅ `.specify/templates/spec-template.md` — scope/requirements align with collection-only perimeter
- ✅ `.specify/templates/tasks-template.md` — task categorization aligns with Symfony project phases
- ⚠ `.specify/templates/commands/` — directory not found; no command templates to update
**Deferred TODOs**: None
-->

# La Collection des Aventuriers — Constitution

## Core Principles

### I. Complémentarité Stricte (NON-NÉGOCIABLE)

The platform MUST remain strictly complementary to "La Taverne des Aventuriers".
Features MUST be limited to: personal collection management, wishlists, and
collaborative encyclopedia entries. Implementing or suggesting general discussion
forums, news publishing, or any feature that competes with "La Taverne des
Aventuriers" is PROHIBITED.

**Rationale**: Partnership integrity. Overlap would damage trust with the partner
site and dilute the platform's focused value proposition.

### II. Architecture Symfony LTS (NON-NÉGOCIABLE)

All backend code MUST follow strict Symfony LTS best practices:
- Controllers MUST be thin: no business logic, only HTTP request/response handling
- Business logic MUST be encapsulated in Services
- Database access MUST use Doctrine ORM exclusively
- Dependency injection MUST be used throughout; no service locator pattern
- Any infrastructure addition or modification (database, cache, queue) MUST be
  accompanied by updates to `.platform.app.yaml`, `.platform/routes.yaml`,
  and `.platform/services.yaml` in the same commit

**Rationale**: Maintainability, testability, and Platform.sh deployment consistency.

### III. Workflow de Validation du Contenu (NON-NÉGOCIABLE)

All content submitted by standard users (`ROLE_USER`) MUST default to `PENDING`
status. Only `ROLE_MODERATOR` or `ROLE_ADMIN` MAY transition content to `PUBLISHED`.
No user-submitted content MAY bypass this workflow.

**Rationale**: Collaborative encyclopedia quality control. Prevents vandalism and
ensures editorial accuracy before public visibility.

### IV. RBAC — Trois Niveaux de Droits (NON-NÉGOCIABLE)

Access control MUST be implemented using the Symfony Security component with at
minimum three roles:
- `ROLE_USER`: personal collection management, wishlist management, content
  submission (PENDING status only)
- `ROLE_MODERATOR`: approve or reject pending submissions
  (transition PENDING → PUBLISHED or REJECTED)
- `ROLE_ADMIN`: full platform administration, user management, configuration

All routes that mutate data MUST be protected by both:
1. CSRF tokens
2. `#[IsGranted]` annotations (or equivalent Symfony `security` attribute)

**Rationale**: Minimal-privilege model prevents unauthorized moderation or
administration actions.

### V. Sécurité et Couverture de Tests (NON-NÉGOCIABLE)

PHPUnit tests MUST be written for all main entities and moderation workflows.
Every data-mutating route MUST be verified for CSRF protection and `#[IsGranted]`
coverage before merging. No feature MAY ship without test coverage for its primary
business logic.

**Rationale**: The collaborative nature of the platform makes it a prime target for
privilege escalation and content injection attacks. Automated tests are the minimum
safety net.

## Infrastructure et Déploiement

This platform MUST be deployed exclusively on Platform.sh. The following files MUST
be kept in strict sync with any infrastructure change:

- `.platform.app.yaml` — application runtime, build hooks, web configuration
- `.platform/routes.yaml` — routing and redirect rules
- `.platform/services.yaml` — managed services (database, cache, etc.)

No infrastructure service (PostgreSQL, Redis, etc.) MAY be added or removed without
updating all three files in the same commit.

## Intégration Frontend

The UI designs and mockups are finalized. Frontend integration MUST respect:
- Templates MUST use the Twig engine only
- Styling MUST use Bootstrap and the provided assets; no new CSS frameworks
- No new front-end JavaScript frameworks MAY be introduced without explicit
  architectural approval
- Asset pipeline changes MUST NOT alter the existing design system or layouts

## Gouvernance

This constitution supersedes all other development practices within this project.
Amendments MUST follow semantic versioning:
- **MAJOR** (x.0.0): removal or redefinition of an existing principle
- **MINOR** (x.y.0): new principle, section, or materially expanded guidance
- **PATCH** (x.y.z): clarifications, wording, non-semantic refinements

All pull requests MUST include a Constitution Check section in their implementation
plan (see `plan-template.md`) verifying compliance with all five Core Principles.
Violations MUST be justified in `plan.md` under the Complexity Tracking table.

Compliance review is expected at each code review. `ROLE_ADMIN` approval is required
for MAJOR version amendments.

**Version**: 1.0.0 | **Ratified**: 2026-05-23 | **Last Amended**: 2026-05-23
