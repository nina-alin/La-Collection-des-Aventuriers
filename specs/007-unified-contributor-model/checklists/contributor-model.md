# Pre-Plan Review Checklist: Unified Contributor Model

**Purpose**: Validate spec completeness and requirement quality across all layers before implementation planning
**Created**: 2026-05-25
**Reviewed**: 2026-05-25
**Feature**: [spec.md](../spec.md)
**Depth**: Pre-planning review | **Scope**: Full feature (data model + routes + UI + fixtures)

## Data Model — Contributor Entity

- [x] CHK001 - Are data types and nullability constraints specified for every Contributor field (firstName, lastName, pseudo, slug, biography, nationality, birthDate, deathDate, portraitImage)? [Completeness, Spec §FR-003]
  - **Fixed**: Key Entities updated with types, nullability, and portraitImage as relative file path (string 255).
- [x] CHK002 - Is nationality field format (ISO 3166-1 alpha-2) enforcement specified at DB and/or application level, or only documented as a storage convention? [Clarity, Spec §Key Entities]
  - **Fixed**: Key Entities now states "validated at application level."
- [x] CHK003 - Are date field constraints defined — specifically, is a logical validation rule required (e.g. deathDate ≥ birthDate)? [Completeness, Gap]
  - **Fixed**: Key Entities explicitly waives logical validation — historical data may be incomplete.
- [x] CHK004 - Is the `details` field on Contribution specified with length and/or format constraints? [Completeness, Spec §FR-005]
  - **N/A**: Implementation detail — acceptable as nullable String.
- [x] CHK005 - Is UUID generation strategy for id fields specified (auto-generated on creation, version, uniqueness guarantee)? [Completeness, Gap]
  - **Pass**: Implementation detail — Doctrine auto-generation is standard and implied.

## Data Model — Data Integrity (Mandatory Gate)

- [x] CHK006 - **[GATE]** Is the slug uniqueness collision resolution strategy (`john-doe-2`) specified at both DB level (unique constraint) and application level, or only one? [Clarity, Spec §FR-004, Assumption]
  - **Pass**: Edge Cases explicitly states "uniqueness enforced at DB + application level."
- [x] CHK007 - **[GATE]** Is the slug generation algorithm specified precisely enough for deterministic implementation — character normalization for accents, special characters, spaces, and maximum length? [Clarity, Spec §FR-004]
  - **Fixed**: FR-004 updated with full normalization rules (accents→ASCII, non-alphanumeric→hyphen, collapsed, lowercase, max 255).
- [x] CHK008 - **[GATE]** Is the cascade-delete requirement for Book → Contribution (FR-007) specified for hard-delete only, or does it address soft-delete/archive scenarios too? [Ambiguity, Spec §FR-007]
  - **Fixed**: FR-007 updated — soft-delete Book cascade-soft-deletes all Contributions.
- [x] CHK009 - **[GATE]** Is the cascade-delete requirement for Contributor → Contribution (FR-008) specified for hard-delete only, or does it address soft-delete/archive scenarios too? [Ambiguity, Spec §FR-008]
  - **Fixed**: FR-008 updated — soft-delete Contributor returns 404 on profile routes; Contributions are preserved.
- [x] CHK010 - **[GATE]** Is the Contribution role enum value set locked (Author | Illustrator | Traductor only) and is the extensibility policy (closed vs. open for future roles) stated? [Clarity, Spec §FR-006]
  - **Pass**: PHP Enum enforced via FR-006; inherently closed set. Extensibility is an implementation concern.
- [x] CHK011 - Are uniqueness constraints beyond slug specified — specifically, can a single Contributor have two Contribution rows with the same Book + role combination (duplicate guard)? [Completeness, Gap]
  - **Fixed**: FR-021 added — (contributor, book, role) combination must be unique.

## Route Requirements

- [x] CHK012 - Are the full URL patterns for all three routes explicitly documented with slug format examples? [Completeness, Spec §FR-009, §FR-010, §FR-011]
  - **Pass**: All three patterns documented; slug examples (`john-doe`, `john-blanche`) in acceptance scenarios.
- [x] CHK013 - Is the 404 condition "contributor exists but has no contributions of the requested role" explicitly equated to "unknown slug" for all three routes consistently? [Clarity, Spec §FR-012, Edge Cases]
  - **Pass**: FR-012 + Edge Cases both state 404 for wrong-role access, equated explicitly.
- [x] CHK014 - Is "single database query" in FR-013 defined unambiguously — does it mean one SQL query, one ORM call, or merely N+1 prevention? [Ambiguity, Spec §FR-013, §SC-003]
  - **Fixed**: FR-013 updated — "without N+1 queries; maximum two fixed queries acceptable."
- [x] CHK015 - Is a tie-breaking sort rule defined for books sharing the same publication year (FR-019)? [Completeness, Spec §FR-019]
  - **Fixed**: FR-019 updated — secondary sort by title alphabetical (A→Z).
- [x] CHK016 - Are HTTP response codes beyond 404 specified for error scenarios (e.g., 500 on DB failure, redirect on slug change)? [Coverage, Gap]
  - **N/A**: Standard HTTP error handling — not in scope for this feature.
- [x] CHK017 - Is the scope of "role-filtered books" per route unambiguous — does `/authors/{slug}` include books where the contributor holds multiple roles on the same book? [Clarity, Spec §FR-009, User Story 2]
  - **Pass**: User Story 2 Scenario 2 explicitly filters to Contribution.role == Author only; illustrator contributions excluded.

## UI Requirements — Null Handling

- [x] CHK018 - Is the CSS initials avatar fully specified — which characters form the initials (firstName[0]+lastName[0] vs. pseudo first letter), background color scheme, and sizing? [Clarity, Spec §FR-016]
  - **Fixed**: FR-016 updated — pseudo first char if set, else firstName first char; color/sizing follow design system tokens.
- [x] CHK019 - Is the neutral placeholder tile for missing book covers specified — dimensions, visual appearance, and accessibility alt text? [Clarity, Spec §FR-017]
  - **Fixed**: FR-017 updated — same aspect ratio as covers, neutral background color, alt text "Cover not available".
- [x] CHK020 - Are null-suppression requirements for `details` and `biography` specified at the visual layout level — is surrounding whitespace/gap also removed, or only the DOM element? [Ambiguity, Spec §FR-018, §FR-020]
  - **Pass**: "No placeholder or empty element rendered" and Clarifications confirm full DOM suppression. Layout gap is an implementation concern.

## UI Requirements — Design & Layout

- [x] CHK021 - Is the visual distinction between Author (text-focused), Illustrator (image-focused), and Traductor (list) page layouts specified with measurable criteria, or only described qualitatively? [Clarity, Spec §FR-009, §FR-010, §FR-011]
  - **Fixed**: FR-009/010/011 updated with concrete layout descriptions (components, cover grid vs. text list).
- [x] CHK022 - Is "Claude Design" token adherence (FR-014) documented with specific token references or a pointer to the token system, or is the requirement vague? [Clarity, Spec §FR-014]
  - **Pass**: "Adhere to Claude Design + Bootstrap" is sufficient for pre-plan; token system already set up per Assumptions.
- [x] CHK023 - Are accessibility requirements defined for all three profile pages (ARIA labels, alt text, keyboard navigation, color contrast ratios)? [Coverage, Gap]
  - **Fixed**: FR-022 added — alt text for images/avatars, semantic HTML, keyboard navigation via Bootstrap.
- [x] CHK024 - Are responsive/mobile layout requirements specified for contributor profile pages? [Coverage, Gap]
  - **Fixed**: FR-023 added — mobile-first, Bootstrap grid, no minimum viewport assumed.
- [x] CHK025 - Are page loading states and error states defined for contributor profile pages? [Coverage, Gap]
  - **N/A**: Server-rendered Twig app — no client-side loading states required.

## Migration & Fixtures

- [x] CHK026 - Is "zero references remain" (SC-001) scoped — does it include test files, documentation, and configuration, or only application source code? [Ambiguity, Spec §SC-001]
  - **Fixed**: SC-001 updated — scope includes PHP, Twig, tests, fixtures, config; git history rewrite not required.
- [x] CHK027 - Are fixture data requirements specified — minimum record counts, coverage of all three roles, at least one multi-role contributor, and edge cases (missing portrait, missing cover)? [Completeness, Spec §FR-015]
  - **Fixed**: SC-006 updated — explicit fixture requirements: Author-only, Illustrator-only, multi-role, missing portrait, missing cover.
- [x] CHK028 - Is the migration execution order specified to safely handle foreign key constraints (create new tables before or after dropping old ones)? [Completeness, Gap]
  - **N/A**: Implementation detail — Doctrine migrations handle constraint ordering.
- [x] CHK029 - Are rollback and recovery requirements defined if the schema migration fails mid-execution? [Coverage, Gap]
  - **N/A**: No production data per Assumptions — rollback risk is low; out of scope.

## Cross-Cutting Concerns

- [x] CHK030 - Are performance requirements beyond N+1 prevention quantified with specific thresholds (page load time, maximum query duration)? [Coverage, Gap]
  - **N/A**: No production traffic requirements for this feature.
- [x] CHK031 - Are SEO requirements for contributor profile pages specified (meta title format, canonical URLs, Open Graph tags)? [Coverage, Gap]
  - **N/A**: Out of scope for this feature.

## Review Summary

**All items resolved.** Spec updated 2026-05-25.

**Pass (original)**: CHK004, CHK005, CHK006, CHK010, CHK012, CHK013, CHK016, CHK017, CHK020, CHK022, CHK025, CHK028, CHK029, CHK030, CHK031

**Fixed via spec update**: CHK001, CHK002, CHK003, CHK007, CHK008, CHK009, CHK011, CHK014, CHK015, CHK018, CHK019, CHK021, CHK023, CHK024, CHK026, CHK027

**Spec additions**: FR-021 (unique contribution constraint), FR-022 (accessibility), FR-023 (responsive)
