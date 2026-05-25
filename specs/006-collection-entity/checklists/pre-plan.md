# Pre-Plan Gate Checklist: Entité Collection et Vue Détail

**Purpose**: Validate spec completeness and quality across all layers before planning. Author self-check.
**Created**: 2026-05-25
**Updated**: 2026-05-25 (all items resolved via checklist session)
**Feature**: [spec.md](../spec.md)
**Depth**: Standard — all layers (entity, page display, navigation, testing)
**Audience**: Author (pre-plan self-check)

## Requirement Completeness — Entity Schema & Doctrine

- [x] CHK001 — Are all 6 `GenreCollection` enum values sufficient to cover all expected collection types? [Completeness, Spec §FR-001]
  > **Resolved**: Values sufficient for current dataset. Note added in FR-001 and Assumptions: `genre` will migrate to a separate `Genre` entity in a future iteration.

- [x] CHK002 — Is the behavior defined when slug regeneration on `nom` update produces a value that already exists in the database (slug collision on rename)? [Gap, Spec §FR-003]
  > **Resolved**: Append numeric suffix (`-2`, `-3`…) until unique. FR-003 updated.

- [x] CHK003 — Are index requirements complete — should `collection_id` on `book` also carry a DB index for join/query performance? [Gap, Spec §FR-001]
  > **Resolved**: Yes. Index on `collection_id` added to FR-001.

- [x] CHK004 — Is the `createurs` JSON field constrained beyond cardinality (element type, max length per element, max array size)? [Clarity, Spec §FR-008]
  > **Resolved**: No additional constraints — element type and size are unconstrained. Existing spec coverage sufficient.

- [x] CHK005 — Is the reference point for `anneeCreation` validation ("≤ année courante") server-side or client-side, and is timezone handling defined? [Ambiguity, Spec §FR-008]
  > **Resolved**: Server-side year. No timezone complexity (year comparison only). Edge cases updated.

- [x] CHK006 — Is the `description` field length unbounded (`text`)? Are display truncation or rendering requirements specified? [Gap, Spec §FR-001]
  > **Resolved**: Full display on collection page, no truncation. `text` type is appropriate.

## Requirement Clarity — Page Display & Pagination

- [x] CHK007 — Is the display behavior on the collection page defined when `createurs` is an empty array `[]`? [Edge Case, Gap]
  > **Resolved**: Row hidden (same pattern as absent optional fields). FR-004 and edge cases updated.

- [x] CHK008 — Is the `imageLogo` placeholder defined with specific visual properties? [Clarity, Spec §FR-004]
  > **Resolved**: Reuse existing `placeholder-cover.svg`. FR-004 and edge cases updated.

- [x] CHK009 — Is "tri alphabétique par titre" for books without `volumeNumber` defined as French title or original title? [Ambiguity, Spec §FR-004]
  > **Resolved**: French title (`titre`). FR-004 and edge cases updated.

- [x] CHK010 — Is the HTTP behavior defined when `?page=N` receives a non-integer value (e.g., `?page=abc`)? [Edge Case, Gap]
  > **Resolved**: HTTP 404 (same as out-of-bounds). Edge cases and FR-004 updated.

- [x] CHK011 — Does the performance requirement SC-001 ("< 2s p95") apply to all paginated pages or only page 1? [Ambiguity, Spec §SC-001]
  > **Resolved**: Applies to all pages including `?page=N`. SC-001 updated.

- [x] CHK012 — Are SEO requirements defined for paginated pages (canonical tags, `<title>` format for `?page=N`)? [Gap]
  > **Resolved**: In scope. New FR-012 added: `<title>` suffix `(page N)` for pages ≥ 2, `<link rel="canonical">` pointing to page 1 on all paginated pages.

- [x] CHK013 — Is the minimum display specification for each book card complete for the case where `couverture` image fails to load? [Edge Case, Spec §FR-005]
  > **Resolved**: Covered by CHK008 resolution — `placeholder-cover.svg` used for missing covers too (existing pattern from book page).

## Requirement Consistency — Navigation & Breadcrumb

- [x] CHK014 — Is "tout autre affichage du nom de collection sur la fiche livre" in FR-006 exhaustively enumerated? [Ambiguity, Spec §FR-006]
  > **Resolved**: Open-ended by design — delegation to implementer. Current template inspection shows only one occurrence (Saga/Volume row, line 244 of show.html.twig). Implementer should audit the full template.

- [x] CHK015 — Do breadcrumb formats use consistent terminology? FR-004 vs FR-007 inconsistency. [Consistency, Spec §FR-004 vs §FR-007]
  > **Resolved**: "Catalogue" root used everywhere. FR-007 corrected: book with collection → `Catalogue / {Nom Collection (link)} / {Titre}`, book without → `Catalogue / {Titre}`.

- [x] CHK016 — Does "Collections" in the book breadcrumb (FR-007) link to a `/collections` list page that is out-of-scope? [Conflict, Spec §FR-007 vs §Assumptions]
  > **Resolved**: No intermediate "Collections" segment in breadcrumb. See FR-007 and Assumptions update.

- [x] CHK017 — Are link requirements on the collection page defined for `editeurHistorique`? [Clarity, Spec §FR-004]
  > **Resolved**: Plain text (no URL field, no external link). FR-004 updated.

## Scenario Coverage — Testing & Migrations

- [x] CHK018 — Does the test coverage in FR-010 include the edge case of books with NULL `volumeNumber` (sort verification)? [Completeness, Spec §FR-010]
  > **Resolved**: Yes, explicitly added to FR-010 test coverage list.

- [x] CHK019 — Are Foundry factory default values defined for all nullable fields? [Gap, Spec §FR-010]
  > **Resolved**: Defaults specified in FR-010: `nomOriginal: null`, `anneeCreation: null`, `editeurHistorique: null`, `imageLogo: null`, `createurs: []`.

- [x] CHK020 — Are migration rollback requirements defined (Down() method)? [Gap, Spec §FR-010]
  > **Resolved**: Yes, Down() required. FR-010 updated: drop `collection` table + remove `collection_id` FK from `book`.

- [x] CHK021 — Is FR-011 specific enough on security configuration approach? [Clarity, Spec §FR-011]
  > **Resolved**: `access_control` with `PUBLIC_ACCESS`. FR-011 updated.

- [x] CHK022 — Are functional test scenarios defined for the book breadcrumb (FR-007) explicitly? [Completeness, Spec §FR-010]
  > **Resolved**: Yes, explicitly listed in FR-010.

## Non-Functional Requirements — Design System

- [x] CHK023 — Are design system badge CSS variables documented for all 6 `GenreCollection` values? [Gap, Spec §FR-009]
  > **Resolved**: Classes `.badge-genre-{valeur}` to be created in `_badges.scss` as part of this spec. FR-009 updated with convention.

- [x] CHK024 — Are design system badge CSS variables documented for all 3 `StatutCollection` values? [Gap, Spec §FR-009]
  > **Resolved**: Classes `.badge-statut-en-cours`, `.badge-statut-terminee`, `.badge-statut-reeditee` to be created. FR-009 updated.

- [x] CHK025 — Are mobile layout requirements specified for the collection detail page? [Completeness, Spec §Assumptions]
  > **Resolved**: Covered by existing responsive design system tokens. No specific breakpoints to define. Assumptions updated.

- [x] CHK026 — Are loading/transition state requirements defined for the paginated book list? [Gap]
  > **Resolved**: Out of scope. Server-side rendering, no JS pagination, no loading states needed.

## Dependencies & Assumptions

- [x] CHK027 — Is there a concrete verification step defined to confirm spec 005 is merged before implementation begins? [Assumption, Spec §Assumptions]
  > **Resolved**: Verification command added to Assumptions: `git log --oneline master | grep 005`.

- [x] CHK028 — Is the `SluggerInterface` vs Gedmo Sluggable divergence documented with rationale? [Assumption, Spec §Assumptions]
  > **Resolved**: Documented in Assumptions with explicit note to add code comment on slug generation method.

- [x] CHK029 — Is the assumption "design system inclut déjà les variables CSS pour les badges" validated? [Assumption, Spec §Assumptions]
  > **Resolved**: Assumption was incorrect — badge classes for genre/statut do NOT exist yet. Corrected in Assumptions and FR-009: new classes must be created in this spec.

## Notes

- All 29 items resolved. Spec updated with 12+ changes.
- Key additions: FR-012 (SEO pagination), FR-009 badge convention, FR-007 breadcrumb correction, FR-003 slug collision, FR-010 factory defaults + Down() migration.
- CHK001: `genre` enum → future `Genre` entity migration planned (documented in spec, hors scope for this feature).
- CHK029 was a false assumption — design system badges for collection genre/statut must be implemented in this spec.
