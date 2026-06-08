# Research: Page Mentions Légales

**Branch**: `021-mentions-legales-page` | **Phase**: 0

## Date Formatting — `app.legal.last_updated`

**Decision**: Store the date as a pre-formatted French string in `config/services.yaml` (e.g., `"3 juin 2026"`). The controller passes it as-is to the template.

**Rationale**: The project does not use `twig/intl-extra`, and installing it solely for one date field would be disproportionate. PHP's native `date()` filter cannot produce French month names without locale configuration. A pre-formatted string in `services.yaml` is idiomatic for static legal content that rarely changes, requires no new dependencies, and remains trivially editable.

**Alternatives considered**:
- `twig/intl-extra` with `format_datetime(pattern='d MMMM y', locale='fr')` — would work but adds a new package for a single field.
- PHP `IntlDateFormatter` in the controller — adds code complexity for a static string.

**How empty value is handled**: If `app.legal.last_updated` is absent or an empty string, the template outputs nothing for the date field (Twig `{% if lastUpdated %}` guard). No fallback text is shown (FR-004).

---

## Bootstrap Scrollspy — rootMargin & Mobile Disable

**Decision**: Initialize Bootstrap 5.3 Scrollspy via JavaScript with `rootMargin: '-88px 0px -65% 0px'`. Conditionally initialize only when `window.innerWidth >= 900`. First TOC item receives `is-active` class on page load as Bootstrap's default state.

**Rationale**: Bootstrap 5.3 Scrollspy supports custom `rootMargin` via JS initialization (`bootstrap.ScrollSpy(element, { rootMargin: ... })`). The `data-bs-root-margin` attribute also works but JS initialization gives finer control over the mobile disable condition.

**Implementation**: A small inline `<script>` block at the bottom of the page template initializes Scrollspy only on the `body` element when the viewport is wide enough. The CSS `scroll-behavior: smooth` on `html` handles smooth scroll natively.

**Alternatives considered**:
- `data-bs-spy="scroll"` auto-init — does not support conditional disable on mobile cleanly.
- IntersectionObserver custom implementation (as in the design mockup) — the spec explicitly requires Bootstrap Scrollspy (`data-bs-spy`).

---

## Twig Partials — IdCard & Callout

**Decision**: Implement `TableauKeyValue` and `AlertBlock` as **Twig include partials** (`{% include %}`) rather than UX Twig components. Template files live in `templates/legal/`.

**Rationale**: Both components carry no PHP logic, receive only simple HTML slot content, and are only used on legal pages. UX Twig components require an empty PHP class per component, which adds no value here. Simple `{% include %}` with `with {...}` is idiomatic Twig for this pattern.

**Alternatives considered**:
- UX Twig components (`#[AsTwigComponent]`) — requires empty PHP class; overkill for static HTML blocks.
- Twig macros — viable but `include` is easier to read and extend.

---

## Routing

**Decision**: New `LegalController` with a single `GET /mentions-legales` route via PHP attribute.

**Rationale**: Consistent with all other controllers in the project (attribute-based routing, thin controller). No service dependency required — the controller receives `app.legal.last_updated` as a constructor-injected string parameter.

---

## Tests

**Decision**: `tests/Functional/Controller/LegalControllerTest.php` — smoke test asserting `GET /mentions-legales` returns HTTP 200.

**Rationale**: The page has no business logic and no database interaction. A route smoke test is the minimum required by Constitution Principle V for a public read-only page.
