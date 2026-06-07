# Research: Landing Page Publique Dynamique

## Existing Repository Methods (validated by reading source)

### Stats for pilule + animated counters

| Metric | Repository | Method | Notes |
|--------|-----------|--------|-------|
| `total_books` | `BookRepository` | `countPublished(): int` | Counts `BookStatus::PUBLISHED` |
| `total_users` | `UserRepository` | `countActive(): int` | Active user accounts |
| `new_this_week` | `BookRepository` | `countPublishedSince(\DateTimeImmutable): int` | Pass `new \DateTimeImmutable('-7 days')` |

### Marquee entities (20-30 items mixed)

| Entity | Repository | Method | Criterion |
|--------|-----------|--------|-----------|
| Books | `BookRepository` | `findMostPopular(int $limit = 4)` | Orders by `SIZE(b.reviews) DESC`, status=PUBLISHED |
| Authors | `ContributorRepository` | `findMostPopular(int $limit = 2)` | Orders by `SIZE(c.contributions) DESC` |
| Collections | `CollectionRepository` | `findMostPopular(int $limit = 2)` | Orders by `SIZE(gc.books) DESC` |

**Decision**: Call each with `limit=10` to get ~30 items total, shuffle for visual variety.
**Rationale**: Methods exist; no new queries needed; shuffling avoids static visual order.
**Alternatives considered**: A single denormalized `popular_items` DB table — rejected (over-engineering; existing methods sufficient).

---

## Route / Auth Split

**Problem**: `DashboardController` has class-level `#[IsGranted('ROLE_USER')]` on `Route('/', name:'home')`. This redirects unauthenticated visitors to login instead of showing the landing page.

**Decision**: 
- `LandingController` takes `Route('/', name:'home')` — public, no `#[IsGranted]`.
  - If `$this->getUser()` is not null → `redirectToRoute('app_dashboard')`.
  - Otherwise → render `landing/index.html.twig`.
- `DashboardController` moves to `Route('/dashboard', name:'app_dashboard')` with method-level `#[IsGranted('ROLE_USER')]`.

**Rationale**: All 15+ existing `path('home')` template references remain valid (they point to `/`). For authenticated users, `/` → redirect `/dashboard` (two hops, acceptable). 
**Alternatives considered**: Controller with conditional branching on single `'home'` route — rejected (single controller would violate SRP and require a `DashboardService` dependency in `LandingController`).

---

## Theme Toggle

**Problem**: Existing `profile_menu_controller.js` already manages `localStorage.setItem('theme', 'parchment'|'dark')` and sets `document.documentElement.dataset.theme`. The landing page needs its own theme button.

**Decision**: New `theme_controller.js` Stimulus controller for the landing page. Reads and writes the same `localStorage.theme` key (`'parchment'` = light, `'dark'` = dark). Sets `document.documentElement.dataset.theme` on toggle.

**Rationale**: Separate controller avoids coupling the landing page to `profile_menu_controller` (which manages a full user menu card with focus trapping, swipe-to-close, etc.). Theme state stays consistent across pages because both controllers share the same localStorage key.
**Alternatives considered**: Reuse `profile_menu_controller.toggleTheme` action — rejected (controller carries unrelated menu state).

---

## Anti-Flash Theme Script (FR-020)

**Decision**: Add a `<script>` block inline in `base.html.twig` `<head>` (before `encore_entry_link_tags`):
```html
<script>
  (function(){var t=localStorage.getItem('theme');if(t==='dark'){document.documentElement.dataset.theme='dark';}})();
</script>
```

**Rationale**: Runs synchronously before CSS parses; prevents FOUC. `profile_menu_controller` already checks `localStorage` on `connect()` to sync the checkbox — this script prevents the visual flash before JS loads.
**Alternatives considered**: Server-side cookie — rejected (adds complexity; localStorage is spec-defined approach for this project).

---

## Marquee CSS Animation

**Decision**: Pure CSS `@keyframes marquee` animation (`animation-play-state: paused` on `.hero-marquee:hover .marquee-track`). No JavaScript needed for the pause behavior.

**Rationale**: Design `design/landing.html` already contains the complete CSS. Spec clarification confirmed: "CSS animation seule suffit" — the API guarantees enough items.
**Alternatives considered**: Stimulus controller for pause — rejected (CSS hover handles it natively, simpler).

---

## Skeleton Placeholders (FR-018)

**Decision**: Twig template renders skeleton `<div>` elements by default. The `landing-stats_controller.js` Stimulus controller:
1. On `connect()`: calls `fetch('/api/public/stats')` and `fetch('/api/public/marquee')`.
2. On success: replaces skeleton elements with real data via DOM updates.
3. On failure: hides stat pill / marquee container; displays `--` for counters.

**Rationale**: Skeleton-first avoids layout shift and satisfies FR-018. Stimulus fetch is lightweight (no extra library needed).
**Alternatives considered**: Turbo Frames — rejected (over-complex for this read-only scenario; Turbo Frames require matching frame IDs and server renders).

---

## Counter Animation (FR-014)

**Decision**: `landing-stats_controller.js` uses `IntersectionObserver` on the stats section. On first intersection:
- Runs a `requestAnimationFrame` loop counting from 0 to target over 2000ms with `ease-out` timing.
- Sets a `data-animated` attribute to prevent replay.

**Rationale**: Spec requires one-shot animation (not replayed on re-scroll). IntersectionObserver is native, no library needed. 2s ease-out matches spec clarification.
**Alternatives considered**: CSS `@keyframes` counter — rejected (requires known-at-compile-time target values; data comes from API).

---

## No New Migrations

**Decision**: No database migrations required.
**Rationale**: Feature is entirely read-only. All needed data is accessible via existing repositories on existing tables. No new entities or columns.
