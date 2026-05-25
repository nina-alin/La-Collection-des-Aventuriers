# Implementation Plan: Book Detail Page (Fiche Œuvre)

**Branch**: `005-book-detail-view` | **Date**: 2026-05-25 | **Spec**: specs/005-book-detail-view/spec.md

**Input**: Feature specification from `/specs/005-book-detail-view/spec.md`

## Summary

Create the `Book` entity (and its related `Author`, `Illustrator`, `Translator`, `Editor`, `BookImage` entities) with Doctrine ORM mappings, Gedmo Sluggable via `stof/doctrine-extensions-bundle`, and filesystem image storage via `vich/uploader-bundle`. Expose a single public route `GET /livre/{slug}` backed by a thin `BookController` and a `BookAccessChecker` service that enforces PUBLISHED/PENDING/REJECTED visibility rules. Render the page using a Twig template faithful to the "vieux grimoire" design system from `design/pages/livre.html`, covering gallery tabs (WCAG 2.1 AA), fiche technique table, résumé, Taverne link, SEO meta tags, and the action bar (authenticated users only).

## Technical Context

**Language/Version**: PHP 8.2+, Symfony 7.2 LTS

**Primary Dependencies**:
- `doctrine/orm` 3.6, `doctrine/doctrine-bundle` 2.18, `doctrine/doctrine-migrations-bundle` 3.7
- `stof/doctrine-extensions-bundle` *(to install — Gedmo Sluggable for slug generation)*
- `vich/uploader-bundle` *(to install — filesystem-local image storage for `coverImage` and `BookImage.imagePath`)*
- `symfony/security-bundle` 7.2 (existing — role hierarchy extension needed)

**Storage**: PostgreSQL 16 (existing managed service on Platform.sh)

**Testing**: PHPUnit 12.5 (existing); no Foundry — PHPUnit functional tests with entity fixtures managed directly via EntityManager

**Target Platform**: Linux server on Platform.sh; developer workstation via Docker Compose (spec 003)

**Project Type**: Symfony web application — new entities, migration, controller, service, Twig template

**Performance Goals**: Page load < 2 s p95 (SC-001) — single DB query with eager-loaded relations; no additional cache layer required for this spec

**Constraints**:
- No new infrastructure service (no Redis, no queue) — no Platform.sh YAML update required beyond writable mount for VichUploader
- `stof/doctrine-extensions-bundle` and `vich/uploader-bundle` are required new PHP packages
- Security: `/livre/{slug}` route must be publicly accessible; access logic is in `BookAccessChecker`, not in `access_control`
- Spec 004 (full RBAC) does not exist yet; minimal `role_hierarchy` for `ROLE_MODERATOR` and `ROLE_ADMIN` is added in this spec

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I — Complémentarité Stricte | ✅ PASS | Book detail = encyclopédie (FR-010, FR-011 exclude community/ratings). No forum, no news. Taverne link (FR-008) is a partner link, not an in-platform discussion feature. |
| II — Architecture Symfony LTS | ✅ PASS | `BookController` is thin (route → service → Twig render). `BookAccessChecker` holds visibility logic. Doctrine ORM for all DB access. DI throughout. `.platform.app.yaml` updated with writable mount for VichUploader uploads directory. No new managed service → `services.yaml` unchanged. |
| III — Workflow de Validation | ✅ PASS | `BookStatus` enum enforces PENDING/PUBLISHED/REJECTED. Only `ROLE_MODERATOR`/`ROLE_ADMIN` can see PENDING/REJECTED (FR-003). Default status for new books is PENDING (constitution principle III). |
| IV — RBAC Trois Niveaux | ⚠️ PARTIAL | `ROLE_MODERATOR` and `ROLE_ADMIN` role hierarchy absent from current `security.yaml`. **This plan adds the minimal `role_hierarchy`** (see Complexity Tracking). All read routes are CSRF-exempt (read-only). No mutation routes in this spec. |
| V — Sécurité et Couverture | ✅ PASS | PHPUnit tests for entity persistence (all FR-001/FR-002 fields), access control logic (BookAccessCheckerTest), and functional controller tests (BookControllerTest). Read-only routes require no CSRF tokens. |

**Post-design re-check**: All gates pass. The `role_hierarchy` addition is the only security.yaml change; it extends RBAC without removing any protection.

## Project Structure

### Documentation (this feature)

```text
specs/005-book-detail-view/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── contracts/
│   └── web-routes.md    # HTTP route contract
└── tasks.md             # Phase 2 output (/speckit-tasks — NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
src/
├── Controller/
│   └── BookController.php                  # GET /livre/{slug} — thin, delegates to service
├── Entity/
│   ├── Book.php
│   ├── Author.php
│   ├── Illustrator.php
│   ├── Translator.php
│   ├── Editor.php
│   ├── BookImage.php
│   └── Enum/
│       ├── BookStatus.php
│       └── BookImageTab.php
├── Repository/
│   └── BookRepository.php
└── Service/
    └── BookAccessChecker.php               # assertViewable(Book, ?UserInterface): void

templates/
└── livre/
    └── show.html.twig                      # Fiche livre — vieux grimoire design system

migrations/
└── Version[timestamp].php                  # Book entity graph + indexes

config/packages/
├── stof_doctrine_extensions.yaml          # sluggable: true
└── vich_uploader.yaml                     # book_cover + book_image mappings

tests/
├── Unit/
│   └── Service/
│       └── BookAccessCheckerTest.php
└── Functional/
    └── Controller/
        └── BookControllerTest.php
```

**Structure Decision**: Single-project Symfony layout (existing). New files follow existing `src/Entity`, `src/Controller`, `src/Service` conventions. Enums live in `src/Entity/Enum/` (consistent with Doctrine attribute-mapped entities).

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|--------------------------------------|
| `role_hierarchy` (ROLE_MODERATOR, ROLE_ADMIN) added in this spec instead of spec 004 | FR-003 requires ROLE_MODERATOR to access PENDING/REJECTED books; spec 004 does not exist | Cannot defer: acceptance tests for US1-SC3 require ROLE_MODERATOR access control to be functional in this spec |
| Two new Composer packages (`stof/doctrine-extensions-bundle`, `vich/uploader-bundle`) | Spec explicitly mandates Gedmo Sluggable (FR-001 slug) and VichUploader (FR-001 coverImage, FR-002 BookImage.imagePath) | Manual slug generation would not cover collision strategy; custom file upload bypasses bundle's lifecycle hook model used by rest of project |
