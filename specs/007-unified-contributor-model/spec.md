# Feature Specification: Unified Contributor Model

**Feature Branch**: `007-unified-contributor-model`

**Created**: 2026-05-25

**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Refactor Data Model: Replace Author/Illustrator with Contributor (Priority: P1)

A site administrator or developer needs the system to consolidate the separate Author and Illustrator entities into a single Contributor entity linked to books via a Contribution pivot. This eliminates data duplication for people who hold multiple roles on a single or multiple books.

**Why this priority**: Foundation for all other stories. Without the new data model in place, front-end views and role-based queries cannot be built.

**Independent Test**: Can be verified independently by confirming the old `author`, `book_author`, `illustrator`, and `book_illustrator` tables no longer exist in the schema, and that `contributor` and `contribution` tables exist with the correct structure.

**Acceptance Scenarios**:

1. **Given** the legacy Author and Illustrator entities exist, **When** the schema migration runs, **Then** the old tables are removed and the new `contributor` and `contribution` tables are created.
2. **Given** a person who was both an Author and an Illustrator in the legacy system, **When** they are stored in the new model, **Then** they appear as a single `Contributor` row with two `Contribution` rows (one per role) — no duplicate person record.
3. **Given** a `Contribution` row, **When** the linked `Book` is deleted, **Then** the `Contribution` row is also deleted (cascade).
4. **Given** a `Contribution` row, **When** the linked `Contributor` is deleted, **Then** the `Contribution` row is also deleted (cascade).
5. **Given** a new `Contributor` is created, **When** the record is saved, **Then** a URL-safe slug is automatically generated and stored — derived from `pseudo` if set, otherwise from `firstName + lastName` (e.g., `john-blanche` or `mad-painter`).

---

### User Story 2 - Author Profile Page (/authors/{slug}) (Priority: P2)

A visitor to the site wants to view the profile of an author, including their biography and a chronological list of books they wrote.

**Why this priority**: Primary public-facing consumer of the new data model. Authors are the most common contributor type and drive the most traffic.

**Independent Test**: Navigate to `/authors/john-doe`. Verify the page shows the contributor's biography and only books where their role is `Author`, in a text-focused layout matching the design system.

**Acceptance Scenarios**:

1. **Given** a `Contributor` exists with at least one `Contribution` of role `Author`, **When** a visitor navigates to `/authors/{slug}`, **Then** the page displays the contributor's name, biography, portrait, and a list of authored books ordered by publication year ascending.
2. **Given** a `Contributor` is both an Author and an Illustrator, **When** a visitor views the `/authors/{slug}` page, **Then** only books where the role is `Author` are listed (illustrator contributions do not appear).
3. **Given** no `Contributor` exists for the given slug, **When** a visitor navigates to `/authors/{unknown-slug}`, **Then** a 404 page is returned.
4. **Given** the author page loads, **When** the page renders, **Then** all book data is retrieved in a single database query (no N+1).

---

### User Story 3 - Illustrator Profile Page (/illustrators/{slug}) (Priority: P2)

A visitor wants to view the profile of an illustrator, including a visual gallery of cover art for books they illustrated.

**Why this priority**: Equal priority to the Author page; illustrators are a primary navigation target for visual-oriented users.

**Independent Test**: Navigate to `/illustrators/john-blanche`. Verify the page shows only books where the contributor's role is `Illustrator`, in an image-focused cover gallery layout.

**Acceptance Scenarios**:

1. **Given** a `Contributor` exists with at least one `Contribution` of role `Illustrator`, **When** a visitor navigates to `/illustrators/{slug}`, **Then** the page displays the contributor's name, portrait, and a visual gallery of illustrated book covers.
2. **Given** a `Contributor` is both an Author and an Illustrator, **When** a visitor views the `/illustrators/{slug}` page, **Then** only books where the role is `Illustrator` are listed.
3. **Given** no `Contributor` exists for the given slug, **When** a visitor navigates to `/illustrators/{unknown-slug}`, **Then** a 404 page is returned.
4. **Given** the illustrator page loads, **When** the page renders, **Then** all book data is retrieved in a single database query.

---

### User Story 4 - Translator Profile Page (/traductors/{slug}) (Priority: P3)

A visitor wants to view the profile of a translator and see the books they translated.

**Why this priority**: Less traffic than Author/Illustrator pages; same technical pattern but lower priority.

**Independent Test**: Navigate to `/traductors/{slug}`. Verify only books with role `Traductor` are shown.

**Acceptance Scenarios**:

1. **Given** a `Contributor` exists with at least one `Contribution` of role `Traductor`, **When** a visitor navigates to `/traductors/{slug}`, **Then** the page displays the contributor's details and a list of translated books.
2. **Given** no `Contributor` exists for the slug, **When** a visitor navigates to `/traductors/{unknown-slug}`, **Then** a 404 page is returned.

---

### Edge Cases

- A `Contributor` exists but has no contributions of the requested role (e.g., an illustrator-only person navigated via `/authors/{slug}`) → **404 response**, same as an unknown slug. The contributor does not exist in that role context.
- Two contributors sharing the same name → numeric suffix appended to slug (e.g., `john-doe-2`). Implementation detail; uniqueness enforced at DB + application level.
- Missing `portraitImage` → CSS initials avatar rendered (no broken image icon).
- Missing book cover on illustrator gallery → neutral placeholder tile rendered.
- `details` on a `Contribution` being null → field suppressed silently; not rendered in the template.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST remove the legacy Author entity, its database table, and all associated migrations/references.
- **FR-002**: The system MUST remove the legacy Illustrator entity, its database table, and all associated migrations/references.
- **FR-003**: The system MUST provide a `Contributor` entity storing identity data (firstName, lastName, pseudo, slug, biography, nationality, birthDate, deathDate, portraitImage) independent of any book role.
- **FR-004**: The system MUST auto-generate a unique, URL-safe slug for each `Contributor` on record creation/update. The slug source is: `pseudo` if non-null, otherwise `firstName + lastName`. Normalization rules: accents stripped to ASCII (é→e, ü→u, etc.), non-alphanumeric characters replaced with a hyphen, consecutive hyphens collapsed to one, all lowercase, maximum 255 characters.
- **FR-005**: The system MUST provide a `Contribution` pivot entity linking a `Contributor` to a `Book` with a typed role value (Author, Illustrator, Traductor) and optional details.
- **FR-006**: The `Contribution` role MUST be enforced via a PHP Enum or named constants to prevent invalid values.
- **FR-007**: Deleting a `Book` MUST cascade-delete all associated `Contribution` rows. Soft-deleting a `Book` MUST cascade-soft-delete all associated `Contribution` rows.
- **FR-008**: Deleting a `Contributor` MUST cascade-delete all associated `Contribution` rows. Soft-deleting a `Contributor` MUST NOT soft-delete their `Contribution` rows; instead, all profile routes for that contributor's slug MUST return 404 (same behavior as an unknown slug).
- **FR-009**: The system MUST expose a public route `/authors/{slug}` that displays a text-focused bibliography page for contributors with Author-role contributions. Layout: contributor header (name, portrait/avatar, biography) followed by an ordered book list showing title and publication year — no cover thumbnails in the list.
- **FR-010**: The system MUST expose a public route `/illustrators/{slug}` that displays an image-focused gallery page for contributors with Illustrator-role contributions. Layout: contributor header (name, portrait/avatar) followed by a responsive grid of book cover images — one cover tile per book, each linked to the book's detail page.
- **FR-011**: The system MUST expose a public route `/traductors/{slug}` that displays a list page for contributors with Traductor-role contributions. Layout: contributor header (name, portrait/avatar) followed by an ordered book list showing title and publication year.
- **FR-012**: Each contributor profile route MUST return a 404 if no matching `Contributor` slug is found, OR if the matched `Contributor` has no contributions of the route's role (e.g., `/authors/{slug}` for an illustrator-only contributor).
- **FR-013**: Each contributor profile page MUST retrieve all required data (contributor + role-filtered books) without N+1 queries. A maximum of two fixed queries is acceptable (e.g., one for the contributor, one for the filtered book list).
- **FR-014**: All UI components (cards, avatars, typography, layout) MUST adhere to the project's "Claude Design" token system and Bootstrap utility classes.
- **FR-015**: The system MUST include updated data fixtures reflecting the new `Contributor` / `Contribution` data model.
- **FR-016**: When `portraitImage` is null, contributor profile pages MUST render a CSS initials avatar in place of the portrait (no broken image). The initial character is derived from: the first character of `pseudo` if `pseudo` is set, otherwise the first character of `firstName`. Color and sizing follow the project's design system tokens.
- **FR-017**: When a book's cover image is null or missing, the illustrator gallery MUST render a neutral placeholder tile in place of the cover (no broken image). The placeholder tile must maintain the same aspect ratio as book cover images, use the design system's neutral background color, and render with alt text "Cover not available".
- **FR-018**: When `details` on a `Contribution` is null, contributor profile pages MUST suppress the field entirely — no placeholder or empty element rendered.
- **FR-019**: Books listed on all contributor profile pages (Author, Illustrator, Traductor) MUST be ordered by publication year ascending (oldest to newest). When multiple books share the same publication year, they MUST be sorted alphabetically by title (A→Z) as a secondary sort key.
- **FR-020**: When `biography` on a `Contributor` is null, contributor profile pages MUST suppress the biography section entirely — no placeholder text rendered.
- **FR-021**: The combination of (contributor, book, role) on a `Contribution` MUST be unique — a contributor cannot hold the same role on the same book more than once.
- **FR-022**: All contributor profile pages MUST meet baseline accessibility requirements: all images and avatars must have descriptive `alt` text or an `aria-label` containing the contributor's name; pages must use semantic HTML landmarks; keyboard navigation must function through Bootstrap's default interactive components.
- **FR-023**: All contributor profile pages MUST be responsive and mobile-first using Bootstrap's grid system. No minimum viewport width is assumed.
- **FR-024**: The system MUST remove the legacy Translator entity, its database table (`translator`), `book.translator_id` column, all associated repositories, and all references — including the `src/Twig/Components/Author/Card.php` Twig component and any templates that reference it.

### Key Entities

- **Contributor**: Represents a real person (author, illustrator, translator, etc.). Stores identity and biographical data. Has no direct knowledge of roles — role context is provided by Contribution rows. Key attributes: id (UUID, auto-generated), firstName (string, required), lastName (string, required), pseudo (string, nullable), slug (string 255, unique, auto-generated), biography (text, nullable), nationality (string 2, ISO 3166-1 alpha-2, nullable, validated at application level), birthDate (Date, nullable), deathDate (Date, nullable; no logical validation enforced — historical data may be incomplete), portraitImage (string 255, nullable, stored as relative file path).
- **Contribution**: Pivot entity linking a Contributor to a Book for a specific role. Key attributes: id, book (ManyToOne → Book), contributor (ManyToOne → Contributor), role (Enum: Author | Illustrator | Traductor), details (nullable String).
- **Book**: Existing entity. Gains a `contributions` OneToMany collection. Loses direct `authors` (ManyToMany), `illustrators` (ManyToMany), and `translator` (ManyToOne) relationships.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: The legacy Author, Illustrator, and Translator entities, their tables, and all codebase references are fully removed — zero references remain after the refactor. Scope includes PHP source files, Twig components, Twig templates, test files, fixture files, and configuration. Rewriting git history is not required.
- **SC-002**: A single person who held both Author and Illustrator roles is stored as one `Contributor` row and two `Contribution` rows — confirmed by database inspection.
- **SC-003**: Each contributor profile page (`/authors/`, `/illustrators/`, `/traductors/`) loads all required data in ≤2 queries — verifiable via query log or profiler.
- **SC-004**: All three role-based profile routes correctly filter contributions — a multi-role contributor displays only the relevant books on each route.
- **SC-005**: All profile pages render correctly according to the design system — verified by visual review against design tokens and Bootstrap class usage.
- **SC-006**: Fixtures load successfully and populate `contributor` and `contribution` tables with representative data covering all three roles. Fixtures must include at minimum: one Author-only contributor, one Illustrator-only contributor, one multi-role contributor covering all three roles (Author, Illustrator, Traductor), one contributor without `portraitImage`, and at least one book without a cover image (to exercise the illustrator placeholder tile).

## Clarifications

### Session 2026-05-25

- Q: When a `Contributor` exists but has no contributions for the requested route's role (e.g., illustrator-only person at `/authors/{slug}`), what should the response be? → A: 404 — contributor not found in this role context, same behavior as an unknown slug.
- Q: When a `Contributor` has a `pseudo`, which value drives slug generation — pseudo or firstName+lastName? → A: Use pseudo if present, fall back to firstName+lastName.
- Q: Are contributor listing/index pages (e.g., `/authors/`, `/illustrators/`) in scope? → A: Out of scope — future feature.
- Q: What fallback renders when `portraitImage` is null or a book cover is missing? → A: CSS initials avatar for missing portrait; neutral placeholder tile for missing book cover.
- Q: Should the migration task transform existing Author/Illustrator fixture files or replace them? → A: Delete old fixture files; write new Contributor/Contribution fixtures from scratch.
- Q: When `details` on a `Contribution` is null, how should the UI handle it? → A: Suppress silently — field not rendered when null.
- Q: What sort order for books on contributor profile pages? → A: Publication year ascending (oldest to newest), same for all three role pages.
- Q: When `biography` on a `Contributor` is null, how should the UI handle it? → A: Suppress silently — biography section not rendered when null.

## Assumptions

- The legacy Author, Illustrator, and Translator tables contain no production data and can be safely dropped without data migration. Existing Author/Illustrator fixture files are deleted; new Contributor/Contribution fixtures are written from scratch.
- The `Book` entity already has a functional slug or ID for routing; no changes to the Book routing are in scope.
- The project design system ("Claude Design" tokens + Bootstrap) is already set up and available to Twig templates.
- Slug uniqueness conflicts (two contributors with the same name) are handled by appending a numeric suffix (e.g., `john-doe-2`) — specific collision strategy is an implementation detail.
- The `Traductor` spelling (vs. "Translator") is intentional per the project's domain language and route naming convention.
- Nationality is stored as a 2-character ISO 3166-1 alpha-2 code; display formatting is a front-end concern.
- No admin CRUD interface for Contributor or Contribution is in scope for this feature — data entry is via fixtures and future admin work.
- Contributor listing/index pages (`/authors/`, `/illustrators/`, `/traductors/`) are out of scope — only role-specific profile pages (`/{role}/{slug}`) are delivered.
