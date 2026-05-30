# Research — 011 Page Contributeur Suggestions

**Date**: 2026-05-30 | **Branch**: `011-contributor-page`

## 1. symfony/ux-live-component Installation

**Decision**: Install `symfony/ux-live-component` via Composer before implementing the wizard.

**Rationale**: The spec mandates LiveComponent for the wizard (FR-027). The package is not currently installed (confirmed: `composer show symfony/ux-live-component` fails). It auto-registers via Symfony Flex recipe — no manual bundle registration needed. Compatible with Symfony 7.2 (requires `^2.x`).

**Command**: `composer require symfony/ux-live-component`

**Alternatives considered**: Server-sent events or plain form with full-page reloads — rejected because spec explicitly requires LiveComponent (FR-027) and in-browser diff with no round-trip on each step navigation.

---

## 2. Wizard State Storage

**Decision**: LiveComponent PHP object properties hold all wizard state server-side (step index, form data, `$originalData` for diff). No session storage, no DB draft, no localStorage.

**Rationale**: Spec explicitly states "Pas de système de brouillon" (clarification 1). LiveComponent's server-side state model already persists component state across re-renders within the same page lifecycle. On page unload, state is lost (as designed).

**Alternatives considered**: PHP session — overhead, harder to GC; localStorage — client-side bypass risk, violates SC-006 (JS-free degraded mode must work).

---

## 3. Diff Computation (Correction Mode)

**Decision**: `WizardComponent::computeDiff()` performs a pure PHP `array_diff_assoc` between `$this->originalData` (fetched when source entity is selected) and `$this->formData` (current form state). Result: count of changed top-level keys.

**Rationale**: Spec states "calculé à la volée dans le LiveComponent — données originales en `$originalData`, diff recalculé à chaque render" (clarification 7). No persistence needed. Relational fields count as 1 key regardless of sub-entity depth (FR-038).

**Alternatives considered**: Persisted diff table — rejected (spec explicitly prohibits it, FR-017).

---

## 4. Autocomplete Fallback

**Decision**: Stimulus `suggestion-autocomplete` controller sets a 3-second `AbortController` timeout on `fetch()`. On timeout or 5xx response, the controller replaces the combobox with a plain `<input type="text">` and shows the label "Saisie libre — service de recherche indisponible" (FR-031).

**Rationale**: Spec specifies 3-second threshold for fallback (FR-031). Stimulus controller is already the right level of abstraction for progressive enhancement.

**Alternatives considered**: Server-side LiveComponent fallback — would require extra round-trip; the spec targets pure client-side timeout detection.

---

## 5. Cover Image Processing

**Decision**: `CoverImageProcessor` service uses PHP `GdImage` (GD extension, available on all Platform.sh PHP 8.3 runtimes) to:
1. Validate MIME type via `finfo` (JPG/PNG/WEBP only)
2. Validate file size ≤ 4MB
3. Crop/resize to 3:4 ratio (center-crop, then resize to max 600×800px)
4. Save as JPEG to `public/uploads/covers/`

**Rationale**: GD is pre-installed on Platform.sh PHP 8.3. Imagick is available but GD is simpler for basic crop/resize. No external service needed.

**Alternatives considered**: Liip/ImagineBundle — adds a dependency; overkill for a single simple transformation.

---

## 6. Polling Error Handling

**Decision**: Stimulus `suggestion-polling` controller tracks consecutive fetch failures in a `failCount` property. On each failure, `failCount++`. At `failCount >= 3`, the controller adds `data-suspended="true"` to the panel element, revealing the "Mise à jour suspendue" indicator (FR-043). On next successful response, `failCount = 0` and `data-suspended` is removed.

**Rationale**: FR-043 specifies exactly this behavior. Stimulus targets+values API is the right abstraction.

**Alternatives considered**: Server-Sent Events for status push — not in scope (spec explicitly says polling, no WebSocket/SSE).

---

## 7. ContributorLevel Seeding

**Decision**: 6 `ContributorLevel` records seeded via `DataFixtures/ContributorLevelFixture.php`. Levels (approximate):

| Rank | Name | Threshold (validated entries) |
|------|------|-------------------------------|
| 1 | Novice | 0 |
| 2 | Apprenti | 5 |
| 3 | Chroniqueur confirmé | 15 |
| 4 | Archiviste | 30 |
| 5 | Érudit | 60 |
| 6 | Grand Sage | 100 |

Thresholds may be adjusted based on real data during implementation.

**Rationale**: Spec references "Rang III · Chroniqueur confirmé" and "Archiviste" as examples (SC-001, FR-001). Exact thresholds not specified — seeded values are reasonable starting points.

**Alternatives considered**: YAML config file — harder to query/join; Doctrine entity allows `ORDER BY threshold` queries directly.

---

## 8. ISBN Validation

**Decision**: Client-side only. Stimulus `suggestion-autocomplete` controller (or a dedicated `suggestion-isbn` controller) computes the ISBN-10/ISBN-13 check digit in JavaScript. No backend round-trip for format validation. Backend re-validates on submission for security (standard Symfony constraint).

**Rationale**: SC-002/SC-003 require ≤100–500ms feedback. ISBN check digit is O(1) math — pure JS is sufficient and instant. FR-010 confirms "côté client."

**Alternatives considered**: LiveComponent action — adds unnecessary latency.

---

## 9. Entity On-the-Fly Creation

**Decision**: Dedicated POST endpoint `POST /api/suggestions/entities/{type}` accepts `{"name": "..."}`, creates the entity (e.g., new `Contributor` with role Author), returns `{"id": "uuid", "label": "name"}`. Protected by `#[IsGranted('ROLE_USER')]` and CSRF token in the request header.

**Rationale**: FR-031 requires "un appel API dédié insère l'entité avant de la sélectionner dans le champ." A generic endpoint per entity type is the simplest implementation.

**Alternatives considered**: LiveComponent action — would mix HTTP side-effects into component render cycle; harder to test and rate-limit independently.

---

## 10. Mobile Breakpoint Implementation

**Decision**: CSS breakpoint at `max-width: 1079px` (to match "< 1080px" from FR-024). Stimulus `suggestion-tabs` controller manages ARIA tab pattern (`role="tablist"`, `aria-selected`, `aria-controls`) and preserves form state across tab switches (LiveComponent state is server-side — tab switch does not reset form).

**Rationale**: FR-024, FR-046, FR-049. LiveComponent state survives tab switches because it's server-side. Polling continues regardless of active tab (FR-049) because `suggestion-polling` runs on the panel element, not the tab content.

**Alternatives considered**: Pure CSS show/hide without JS — would not update ARIA attributes or manage focus per WCAG requirement (FR-025, FR-059).
