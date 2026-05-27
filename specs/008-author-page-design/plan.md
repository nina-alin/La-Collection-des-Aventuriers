# Implementation Plan: Intégration du Design de la Page Auteur

**Branch**: `008-author-page-design` | **Date**: 2026-05-26 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/008-author-page-design/spec.md`

## Summary

Rewrite `templates/contributeur/author_show.html.twig` to match the `design/pages/auteur.html` mockup (two-column layout: styled portrait + biography left, filtered/sorted book bibliography right). Extends `ContributorRepository` with `findContributionsBySlug()` for saga-filtered, sorted contribution loading; updates `ContributorController::authorShow` to read `?saga=` and `?sort=` query params; adds `_auteur.scss` to the SCSS pipeline.

## Technical Context

**Language/Version**: PHP 8.3 / Symfony 7.2 LTS

**Primary Dependencies**: Doctrine ORM 2.x, Twig 3.x, Bootstrap SCSS (selective imports), PHPUnit 12, Gedmo SoftDeleteable, Symfony Webpack Encore

**Storage**: PostgreSQL via Doctrine (existing schema — no migrations needed)

**Testing**: PHPUnit 12 — WebTestCase for controller/integration tests, unit tests for repository logic

**Target Platform**: Platform.sh / Linux server

**Project Type**: Server-rendered web application (Symfony MVC + Twig)

**Performance Goals**: <2s full page load for authors with up to 50 contributions (SC-005); single JOIN query, PHP-side collection processing acceptable at this scale

**Constraints**: No new JS frameworks; Bootstrap + custom SCSS only; Twig-only templates; no infrastructure changes

**Scale/Scope**: Single author detail page; up to ~50 contributions per author; PHP-side filtering acceptable (<50 objects)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Complémentarité Stricte | ✅ PASS | Read-only author encyclopedia page — no forum, no news, no competition with La Taverne |
| II. Architecture Symfony LTS | ✅ PASS | Controller remains thin (reads Request params, calls repo, renders). Filtering/sorting logic in repository. Doctrine ORM exclusively. DI throughout. No infrastructure changes → no platform file updates needed. |
| III. Workflow de Validation | ✅ PASS | Page is read-only; no content submission path |
| IV. RBAC | ✅ PASS | Public GET route; no mutation → no CSRF or `#[IsGranted]` needed. Authenticated user counter (FR-008) is display-only placeholder requiring `is_granted()` in Twig, not a route guard. |
| V. Sécurité et Couverture de Tests | ✅ PASS | Tests required for: new repository method (saga filter, sort order, unknown saga fallback), controller query param handling, template rendering (exclusions FR-011/FR-012/FR-013). |

**Post-design re-check**: No new concerns introduced in Phase 1 design.

## Project Structure

### Documentation (this feature)

```text
specs/008-author-page-design/
├── plan.md              # This file (/speckit-plan output)
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── http-interface.md
└── tasks.md             # Phase 2 output (/speckit-tasks — NOT created by /speckit-plan)
```

### Source Code

```text
src/
├── Controller/
│   └── ContributorController.php          # MODIFY: authorShow reads ?saga=, ?sort=; calls new repo method
└── Repository/
    └── ContributorRepository.php          # MODIFY: add findContributionsBySlug()

templates/
└── contributeur/
    └── author_show.html.twig              # REWRITE: full mockup integration

assets/
└── styles/
    ├── app.scss                           # MODIFY: add @import "pages/auteur"
    └── pages/
        └── _auteur.scss                   # NEW: styles from design/pages/auteur.html

tests/
└── Controller/
    └── ContributorControllerTest.php      # MODIFY: add tests for ?saga=, ?sort=, template assertions
```

**Structure Decision**: Standard Symfony single-project layout. No new directories needed beyond `_auteur.scss` in the existing `assets/styles/pages/` directory.

---

## Phase 0: Research

*See [research.md](./research.md) for full findings. Summary below.*

### Finding 1 — Slugification (Twig + PHP)

**Decision**: Use a private `slugify()` helper method in `ContributorRepository` using Symfony's injected `SluggerInterface`. In Twig, pre-compute saga slugs in the controller (passed as `sagaGroups`) to avoid adding a Twig extension.

**Rationale**: The spec requires slug comparison in both PHP (repository filter) and Twig (pill URL generation). Pre-computing in controller keeps template logic minimal. `SluggerInterface` is already available in Symfony DI; no new dependencies.

**Alternatives considered**: Custom Twig filter (extra file, not needed), `LOWER(REPLACE(...))` in DQL (fragile for accented chars like "Légendes").

### Finding 2 — Age Calculation in Twig

**Decision**: Compute `age` and `ageAtDeath` in the controller (simple year-diff integers) and pass to template as `contributorAge` and `contributorAgeAtDeath`.

**Rationale**: Spec says "côté Twig" but Twig's `date()` filter supports arithmetic sufficient for year-precision age. However, PHP is cleaner and avoids Twig complexity. The spec assumption ("sans logique PHP supplémentaire dans le contrôleur") is relaxed — this is view-preparation, not business logic.

**Implementation**:
```php
$age = $contributor->getBirthDate()?->diff(new \DateTime())->y;
$ageAtDeath = ($contributor->getBirthDate() && $contributor->getDeathDate())
    ? $contributor->getBirthDate()->diff($contributor->getDeathDate())->y
    : null;
```

**Alternatives considered**: Custom Twig extension (overkill), full Twig date arithmetic (complex, error-prone).

### Finding 3 — Filtered/Sorted Contributions (DQL + PHP)

**Decision**: `findContributionsBySlug()` loads all author contributions via one JOIN query, then filters/sorts in PHP. Returns `?array{contributor: Contributor, filteredContributions: Contribution[], sagaGroups: list<array{slug: string, name: string, count: int}>}`.

**Rationale**: Slug comparison (including accented saga names) cannot be reliably done in DQL without fragile REPLACE chains. With ≤50 contributions per author (SC-005), PHP-side processing is negligible. Returning the saga groups from the repository avoids controller business logic (controller remains thin).

The departure from spec's `?Contributor` return type is a pragmatic design decision documented here. The spec intent — keep filtering logic in repository — is preserved.

**Query**: Single DQL SELECT with INNER JOIN on contributions+books (role=Author, slug=:slug), all sagas loaded. PHP `usort` + `array_filter` for saga slug and sort.

**NULL-last ordering** (chrono): `usort` returning `PHP_INT_MAX` for null `frenchPublicationYear`.

**Unknown saga fallback**: If filtered contributions are empty → return all contributions (pill "TOUT" active).

**Alternatives considered**: DQL WITH filtering (fragile), separate `getFilteredContributions()` method (splits logic across two calls).

---

## Phase 1: Design & Contracts

*See [data-model.md](./data-model.md) and [contracts/http-interface.md](./contracts/http-interface.md).*

### Key Design Decisions

#### Repository Method Signature

```php
/**
 * @return array{
 *   contributor: Contributor,
 *   filteredContributions: Contribution[],
 *   sagaGroups: list<array{slug: string, name: string, count: int}>
 * }|null
 */
public function findContributionsBySlug(
    string $slug,
    ?string $sagaFilter,
    string $sortOrder = 'chrono'
): ?array
```

#### Controller Changes

`authorShow` reads `?saga=` and `?sort=` from `Request`, calls `findContributionsBySlug`, computes `contributorAge` / `contributorAgeAtDeath`, renders template with:
- `contributor` — Contributor entity (profile data)
- `contributions` — filtered/sorted Contribution[] (bibliography)
- `sagaGroups` — `[{slug, name, count}]` for pills
- `activeSaga` — string|null (current ?saga= value)
- `activeSort` — 'chrono'|'alpha' (current ?sort=, default 'chrono')
- `contributorAge` — int|null
- `contributorAgeAtDeath` — int|null

#### Template Structure (author_show.html.twig)

```
extends base.html.twig
└── block body
    └── .auteur > .auteur-grid
        ├── .portrait-card (left col)
        │   ├── .portrait-eyebrow (ref/role)
        │   ├── .portrait-frame (framed portrait OR stylized placeholder)
        │   ├── .nameplate (firstName + lastName)
        │   ├── .vitals (attributes table: prénom, nom, pseudo?, nationalité)
        │   ├── .life (birth/death dates block — hidden if birthDate null)
        │   └── .bio-card (biography with lettrine + collapse toggle)
        └── .bibliography (right col)
            ├── .biblio-head (title "SA BIBLIOGRAPHIE · N", auth counter placeholder)
            ├── .biblio-toolbar (Trier + Vue controls)
            ├── .collection-filters role="group" (saga pills)
            └── .books-grid role="list" (book cards) OR empty state message
```

**Exclusions enforced**: No `.seal-row`, `.seal-btn`, `.also-strip` in output HTML.

#### SCSS Approach

Extract all CSS from `design/pages/auteur.html` `<style>` block that is specific to the author page (not in `tokens.css` / `components.css`). Place in `assets/styles/pages/_auteur.scss`. Import in `app.scss`.

Layout CSS from mockup (`.session-header`, `.crumbs`) is already handled by `base.html.twig` + existing components. Only author-specific styles go in `_auteur.scss`: `.auteur`, `.auteur-grid`, `.portrait-card`, `.portrait-frame`, `.nameplate`, `.vitals`, `.life`, `.bio-card`, `.bibliography`, `.biblio-head`, `.collection-filters`, `.coll-pill`, `.books-grid`, `.book-card`, `.bc-*`.

#### Saga Color Mapping (in Twig template)

Static mapping defined in template as Twig variable:

```twig
{% set sagaColors = {
  'Loup Solitaire': 'mousse',
  'Légendes de Magnamund': 'encre',
  'Le Monde de Loup Solitaire': 'encre',
  'Défis Fantastiques': 'sang',
  'Sorcellerie!': 'or',
  'Feux de la Forge': 'parchemin',
} %}
```

Fallback for unmapped sagas: no `data-bg` attribute (default cuir gradient from CSS).

#### Saga Abbreviation Mapping (in Twig template)

```twig
{% set sagaAbbreviations = {
  'Loup Solitaire': 'LS',
  'Légendes de Magnamund': 'LM',
  'Le Monde de Loup Solitaire': 'MLS',
  'Défis Fantastiques': 'DF',
  'Sorcellerie!': 'SO',
  'Feux de la Forge': 'FF',
} %}
```

Fallback: first 2-3 uppercase letters from saga name words.

---

## Complexity Tracking

| Principle | Finding | Justification |
|-----------|---------|---------------|
| II. Architecture Symfony LTS | Saga-slug filtering and sorting live in `ContributorRepository`, not a Service class. Constitution requires business logic in Services. | Filtering at ≤50 objects is a data-access concern, not domain logic. The repository is registered as a Symfony service (DI), satisfies the DI requirement, and keeps the controller thin. Extracting to a dedicated `AuthorBibliographyService` would add a layer with no architectural benefit at this scale. Accepted trade-off, documented here per constitution governance rules. |
