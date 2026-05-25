# Spec Review Checklist: Book Detail Page (Fiche Œuvre)

**Purpose**: Author self-review — validate completeness and clarity of spec.md requirements before implementation starts. Tests the spec as written, not the implementation.
**Created**: 2026-05-24
**Reviewed**: 2026-05-25
**Feature**: [specs/005-book-detail-view/spec.md](../spec.md)
**Scope**: Data model + view/UI (equal coverage); RBAC in dedicated section; VichUploader inline.

**Legend**: ✅ Pass — adequately specified | ❌ Fail — gap found | 🔧 Fixed — gap resolved in spec update

---

## Requirement Completeness — Data Model

- [x] CHK001 🔧 - Are all scalar field types explicitly specified for every field in FR-001? [Completeness, Spec §FR-001]
  > Fixed: FR-001 now contains a typed table with nullability for all fields.

- [x] CHK002 🔧 - Is nullability explicitly stated for each field in FR-001? [Completeness, Spec §FR-001]
  > Fixed: nullability column added to FR-001 table. Corrections applied: `isbn` nullable, `pages` nullable.

- [x] CHK003 🔧 - Is the `slug` field documented in FR-001 alongside its auto-generation strategy? [Completeness, Gap — Spec §Assumptions]
  > Fixed: `slug` added to FR-001 field table with auto-generation rule and collision strategy.

- [x] CHK004 🔧 - Are `Author`, `Illustrator`, `Translator`, `Editor` entity schemas defined? [Completeness, Gap — Spec §FR-002]
  > Fixed: FR-002 and Key Entities now define fields for all related entities. Persons: firstName + lastName + slug. Editor: name + slug.

- [x] CHK005 ✅ - Are the `BookImage` fields (`tab`, `imagePath`) fully typed? [Completeness, Spec §FR-002]
  > FR-002 updated table includes both fields with types.

- [x] CHK006 🔧 - Are cascading behaviors specified for the OneToMany `BookImage` relationship? [Completeness, Gap — Spec §FR-002]
  > Fixed: cascadeRemove + orphanRemoval=true added to FR-002.

- [x] CHK007 🔧 - Are database-level constraints specified for key fields? [Completeness, Gap — Spec §FR-001]
  > Fixed: isbn unique index (nullable), slug unique index, (book+tab) DB-level unique index all specified.

- [x] CHK008 ✅ - Is the `tab` enum exhaustively listed with all valid values and display labels? [Completeness, Spec §FR-007]
  > FR-007 lists all 5 values: Tome, Dos, Tranche, Pages, Carte. Names serve as display labels.

- [x] CHK009 🔧 - Are index requirements specified for performance-critical lookups? [Completeness, Gap]
  > Fixed: FR-001 now requires DB indexes on `slug` and `status`.

- [x] CHK010 🔧 - Is the `editionInfo` field display location specified? [Completeness, Gap — Spec §FR-001]
  > Fixed: FR-005 Fiche Technique table now shows `editionInfo` as row 9 "Édition".

---

## Requirement Clarity — Data Model

- [x] CHK011 🔧 - Is the `languages` JSON array schema fully defined? [Clarity, Spec §FR-001]
  > Fixed: FR-001 specifies default `[]`, codes ISO 639-1. Empty array = row hidden (FR-013 + FR-005).

- [x] CHK012 🔧 - Is the (book, tab) uniqueness constraint specified at DB level? [Clarity, Spec §FR-002]
  > Fixed: FR-002 now explicitly states "enforced au niveau base de données (unique index)".

- [x] CHK013 🔧 - Is the slug collision resolution strategy traceable to an FR? [Traceability, Gap — Spec §Assumptions]
  > Fixed: slug field now in FR-001 with collision rule. Assumptions retains the detail; FR-001 owns the field.

- [x] CHK014 🔧 - Is VichUploaderBundle configuration scope defined? [Clarity, Spec §FR-015]
  > Fixed: FR-015 now states "mapping filesystem local, répertoire de destination à définir en implémentation".

---

## Access Control (RBAC)

- [x] CHK015 🔧 - Is `ROLE_MODERATOR` explicitly cross-referenced from spec-004 in FR-003? [Completeness, Spec §FR-003]
  > Fixed: FR-003 now includes "s'appuie sur le système RBAC défini dans la spec 004".

- [x] CHK016 🔧 - Is the dependency on spec-004 stated as a hard prerequisite? [Dependency, Spec §Assumptions]
  > Fixed: Assumptions now marks spec-004 as "Dépendance bloquante".

- [x] CHK017 ✅ - Is the HTTP response code explicitly specified for non-moderator access to PENDING/REJECTED? [Clarity, Spec §FR-003]
  > FR-003 + US1-SC3: HTTP 404 specified.

- [x] CHK018 🔧 - Is behavior for authenticated non-moderators specified separately from anonymous visitors? [Coverage, Spec §FR-003]
  > Fixed: FR-003 now explicitly states both anonymous and ROLE_USER get 404 — parity is intentional.

- [x] CHK019 ✅ - Is `ROLE_ADMIN` hierarchy over `ROLE_MODERATOR` documented? [Clarity, Spec §FR-003]
  > FR-003: "ROLE_ADMIN par hiérarchie de rôles définie dans spec 004".

- [x] CHK020 ✅ - Are acceptance scenarios measurably verifiable? [Measurability, Spec §US1-SC3]
  > HTTP 404 specified. Error page content follows Symfony default — no additional spec needed.

---

## Requirement Completeness — Display Rules

- [x] CHK021 🔧 - Are all Fiche Technique fields exhaustively listed and in defined order? [Completeness, Spec §FR-005]
  > Fixed: FR-005 now contains 12-row ordered table with labels. `tirage` removed from US1-SC2 (was an error).

- [x] CHK022 🔧 - Are French display labels specified for each Fiche Technique row? [Completeness, Gap — Spec §FR-005]
  > Fixed: FR-005 table includes "Label affiché" column for all 12 rows.

- [x] CHK023 🔧 - Is "badge de collection" defined? [Clarity, Spec §FR-004]
  > Fixed: removed from FR-004 (out of scope). Header = volume + titre français + titre original.

- [x] CHK024 🔧 - Is action bar button state specified for authenticated users? [Completeness, Spec §FR-009]
  > Fixed: FR-009 now specifies "stylisés normalement (design system, apparence active), sans handler de clic".

- [x] CHK025 🔧 - Are the 4 action buttons specified for anonymous vs. authenticated users? [Coverage, Gap — Spec §FR-009]
  > Fixed: FR-009 now states hidden for anonymous, visible for authenticated only.

- [x] CHK026 ✅ - Is `translator: null` row behavior consistent between FR-013 and Edge Cases? [Consistency, Spec §FR-013 vs. Edge Cases]
  > FR-013 and Clarifications both specify row masked when null. Consistent.

- [x] CHK027 🔧 - Is year display format specified? [Completeness, Gap — Spec §FR-005]
  > Fixed: FR-001 types both year fields as `int` ("Année sur 4 chiffres"). Display = numeric year.

---

## Requirement Clarity — Display Rules

- [x] CHK028 ✅ - Is "lettrine" behavior quantified or deferred to design system? [Clarity, Spec §FR-006]
  > FR-006 defers to design/pages/livre.html ("si le design system la prévoit"). Acceptable — implementation follows design file.

- [x] CHK029 🔧 - Is the placeholder SVG specified with accessibility alt text? [Clarity, Spec §FR-013]
  > Fixed: FR-013 now specifies `alt` attribute MUST contain the book's French title.

- [x] CHK030 🔧 - Is `volumeNumber` display behavior defined when absent? [Clarity, Spec §FR-004 + Edge Cases]
  > Fixed: FR-004 and Edge Cases now specify both saga + volumeNumber hidden when null; header shows titles only.

- [x] CHK031 🔧 - Is `saga` display behavior defined when absent? [Clarity, Spec §FR-013 + Edge Cases]
  > Fixed: same rule as CHK030 — both hidden together.

- [x] CHK032 🔧 - Is "multiple illustrators" display rule specified? [Clarity, Gap — Spec §FR-004]
  > Fixed: FR-004 now explicitly covers illustrators: inline comma-separated, same rule as authors.

- [x] CHK033 🔧 - Is "La Taverne" parameter name and configuration source referenced? [Clarity, Spec §Assumptions]
  > Fixed: FR-008 and Assumptions now specify env var `TAVERNE_URL` with note to document in `.env.example`.

---

## Edge Case Coverage

- [x] CHK034 🔧 - Is the `languages` empty array scenario defined? [Edge Case, Gap — Spec §FR-001]
  > Fixed: FR-013 and FR-005 now specify empty array = row hidden, same rule as null.

- [x] CHK035 🔧 - Is the scenario of a book with zero `galleryImages` defined? [Edge Case, Spec §FR-007]
  > Fixed: FR-007 now specifies zero BookImage → "Tome" tab shown with placeholder SVG.

- [x] CHK036 ✅ - Is the scenario where all 5 gallery tabs are present specified? [Edge Case, Spec §FR-007]
  > Layout handled by design system (responsive). No additional spec rule needed.

- [x] CHK037 ✅ - Is the scenario of many authors (3+) specified for header line wrapping? [Edge Case, Gap — Spec §FR-004]
  > Comma-separated string — CSS wrapping handled by design system. No additional spec rule needed.

- [x] CHK038 🔧 - Is ISBN format validated or specified? [Edge Case, Gap — Spec §FR-001]
  > Fixed: FR-001 now states ISBN-10 and ISBN-13 both accepted, stored as-is, no format validation.

---

## Non-Functional Requirements

- [x] CHK039 🔧 - Is the "< 2 seconds" load target defined for a specific percentile? [Measurability, Spec §SC-001]
  > Fixed: SC-001 now specifies "p95, cache chaud, conditions normales de charge".

- [x] CHK040 ✅ - Are mobile breakpoints or responsive rules specified? [Completeness, Gap — Spec §Assumptions]
  > Assumptions references design/pages/livre.html for breakpoints. Acceptable.

- [x] CHK041 🔧 - Are accessibility requirements defined for gallery tab keyboard navigation? [Coverage, Gap]
  > Fixed: FR-007 now requires WCAG 2.1 AA with ARIA roles (tablist/tab/tabpanel) and keyboard navigation.

- [x] CHK042 🔧 - Are SEO requirements specified? [Coverage, Gap]
  > Fixed: FR-016 added — page title, meta description, og:title, og:image.

- [x] CHK043 ✅ - Is design system conformance validation defined with a concrete reference? [Measurability, Spec §SC-003]
  > SC-003 references design/pages/livre.html for visual review.

---

## Assumptions & Dependencies

- [x] CHK044 🔧 - Are assumptions about pre-existing entity absence traceable to a migration strategy? [Assumption, Spec §Assumptions]
  > Fixed: Assumptions now states "migrations Doctrine sont à générer dans cette spec".

- [x] CHK045 🔧 - Is Gedmo Sluggable / StofDoctrineExtensionsBundle assumed installed? [Assumption, Spec §Assumptions]
  > Fixed: Assumptions now marks StofDoctrineExtensionsBundle as a prerequisite ("vérifier présence dans composer.json").

- [x] CHK046 ✅ - Is responsive mobile support referenced to a specific design system document? [Assumption, Spec §Assumptions]
  > Fixed: Assumptions now references design/pages/livre.html for breakpoints.

- [x] CHK047 🔧 - Are future spec dependencies cross-referenced by spec number? [Traceability, Gap]
  > Fixed: all future spec references now numbered — spec 006 (gestion de collection membre) in FR-009 and Assumptions; spec 007 (backoffice modération) in FR-014, FR-015.

---

## Out-of-Scope Clarity

- [x] CHK048 ✅ - Is FR-010 specific enough about "La Communauté" exclusion? [Clarity, Spec §FR-010]
  > Lists: comments, member avatars, global rating averages. Specific enough.

- [x] CHK049 ✅ - Does FR-011 exclusion of "Ma note personnelle" conflict with FR-009 action buttons? [Clarity, Spec §FR-011 vs. §FR-009]
  > No conflict. FR-011 = ratings (boucliers/étoiles). FR-009 = collection management buttons. Distinct.

- [x] CHK050 ✅ - Is FR-014's status transition deferral clear enough? [Clarity, Spec §FR-014]
  > FR-014 explicit: "statut est défini via fixtures ou en base directement".

---

## Final Summary

| Result | Count | Items |
|--------|-------|-------|
| ✅ Pass (was already OK) | 10 | CHK008, CHK017, CHK019, CHK020, CHK026, CHK028, CHK036, CHK037, CHK043, CHK049, CHK050 |
| 🔧 Fixed (resolved in spec update) | 38 | All others |
| ❌ Remaining gaps | 0 | — |

**50/50 — spec ready for implementation planning.**
