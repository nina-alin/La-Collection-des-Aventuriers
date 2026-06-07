# Release Gate Checklist: Landing Page Publique Dynamique

**Purpose**: Full-coverage formal release gate — validates requirement quality across all dimensions before implementation is considered shippable
**Created**: 2026-06-07
**Feature**: [spec.md](../spec.md) · [tasks.md](../tasks.md)
**Depth**: Formal release gate (thorough — edge cases, consistency conflicts, mobile/touch, resilience)

---

## Requirement Completeness

- [x] CHK001 — Is the localStorage key name for theme persistence explicitly specified in FR-005, or is `'theme'` an implementer assumption only visible in tasks.md T017? [Gap, Spec §FR-005]
  > **RESOLVED**: Key `'theme'`, values `'parchment'` (light) / `'dark'` (dark) — confirmed in `profile_menu_controller.js:104` and now explicit in FR-005. T017 updated: `'light'` → `'parchment'`. T008 anti-flash checks `=== 'dark'` only — correct, no change needed.
- [x] CHK002 — Does FR-006 ("nouvelles fiches ajoutées dans les 7 derniers jours") define what "fiche" covers — books only, or also authors/collections? Key Entities lists "total livres" separately, leaving "nouvelles fiches" ambiguous. [Ambiguity, Spec §FR-006]
  > **RESOLVED**: "nouvelle fiche" = book only. `data-model.md` and T005 both use `BookRepository::countPublishedSince()`. No other entity type is tracked for `new_this_week`. Spec label is consistent with implementation.
- [x] CHK003 — Is the "contributions validées" counter (FR-013) requirement reconciled with the tasks.md note that `total_books` is used as proxy (T016)? This discrepancy between the spec label and the data source is undocumented in FR-013. [Conflict, Spec §FR-013]
  > **RESOLVED**: Label "contributions validées" abandoned. Third counter renamed "livres répertoriés" using `total_books`. FR-013, Key Entities, and T016 updated. Note: counters 1 and 3 now share the same metric and label — acceptable per product decision.
- [x] CHK004 — Are SEO requirements defined — meta description, OG tags, `<title>`, canonical URL, robot indexability — for the public landing page? [Gap]
  > **RESOLVED**: Product decision 2026-06-07 — add `<meta name="description">` + Open Graph `og:title`/`og:description`/`og:url` to `templates/landing/index.html.twig` (T009). No `<title>` gap — base template already provides `{% block title %}`. Canonical URL and robots.txt out of scope.
- [x] CHK005 — Are caching or rate-limiting requirements defined for `/api/public/stats` and `/api/public/marquee`, given these endpoints serve every anonymous visitor? [Gap]
  > **RESOLVED**: Product decision 2026-06-07 — no caching for MVP. These are cheap read-only queries on a small dataset. Revisit post-MVP if load warrants it.
- [x] CHK006 — Is a `prefers-reduced-motion` requirement specified for the marquee scroll animation, counter animation (FR-013/FR-014), and skeleton pulse? [Gap, Spec §FR-019]
  > **RESOLVED**: `design/landing.html:379` covers marquee (`animation: none`). Counter animation and skeleton pulse not covered in design. Implementation decision: under `prefers-reduced-motion: reduce`, counter shows final value instantly (no rAF loop) and skeleton pulse `animation` is set to `none`. Required by WCAG AA (FR-019).
- [x] CHK007 — Are touch target size requirements specified for the theme toggle button and search submit button? WCAG 2.5.8 specifies minimum 24×24px (AA), commonly implemented as 44×44px. [Gap, Spec §FR-019]
  > **RESOLVED**: Minimum 44×44px enforced in `_landing.scss` for theme toggle and search submit button. Exceeds WCAG 2.5.8 minimum (FR-019 compliance).
- [x] CHK008 — Is there a requirement specifying what happens when a marquee entity has no slug (null/empty slug from backend)? The URL patterns are defined (FR-010) but the missing-slug edge case is not. [Gap, Spec §FR-010]
  > **RESOLVED**: Product decision 2026-06-07 — `LandingService::getMarqueeItems()` skips any entity with a null or empty slug. No dead links in marquee.

---

## Requirement Clarity

- [x] CHK009 — Is "moins de 2 secondes" in SC-001 defined with a specific metric (TTFB, Time to Interactive, FCP, or time until skeletons are replaced by real data)? Ambiguity makes SC-001 unverifiable. [Ambiguity, Spec §SC-001]
  > **RESOLVED**: SC-001 "2s" = time from first byte until skeleton→real data transition completes (both API calls return and DOM is updated). Skeleton renders immediately from server; the 2s budget covers the JS fetch + DOM replacement. Sufficient for implementation.
- [x] CHK010 — Does FR-003 ("logo/titre cliquable, retour haut de page") specify the logo behavior on non-landing authenticated pages, where `#top` would scroll the dashboard — not navigate to landing? The navbar is site-wide (Navbar.html.twig). [Ambiguity, Spec §FR-003]
  > **RESOLVED**: `Navbar.html.twig` logo links to `path('home')` (current state). After T001 migration, `path('home')` → `/` → `LandingController` redirects authenticated users to `/dashboard` automatically (one extra hop, acceptable per research.md). No navbar change needed.
- [x] CHK011 — Is "populaires" (FR-008) defined with measurable selection criteria? Assumptions delegate this to the implementation (`findMostPopular` method), but the business definition — most-viewed, highest-rated, most recent — is absent. [Ambiguity, Spec §FR-008]
  > **RESOLVED**: Spec Assumptions explicitly states "le critère exact est laissé à l'implémentation". `findMostPopular()` methods already exist in repositories. No new decision needed.
- [x] CHK012 — Is "tous les navigateurs desktop modernes" (SC-005) quantified with a specific browser/version support matrix? [Ambiguity, Spec §SC-005]
  > **RESOLVED**: No `.browserslistrc` in project. "Desktop modernes" = Chrome, Firefox, Safari, Edge — last 2 major versions. Standard industry default. All targeted APIs (IntersectionObserver, CSS custom properties, Stimulus) supported across all targets.
- [x] CHK013 — Is the skip-link text ("Aller au contenu principal" in T009) and its visual treatment (visible only on focus? always visible?) specified in the requirements, or only in tasks? [Gap, Spec §FR-019]
  > **RESOLVED**: Skip-link not in `design/landing.html` but required by FR-019 (WCAG AA). T009 defines markup. Implementation: visually hidden until focused (standard WCAG pattern). Focus-visible style from `--ring-focus` CSS variable (consistent with rest of site).
- [x] CHK014 — Does FR-017 ("respecter intégralement le design défini dans design/landing.html") define a version/stability guarantee on the reference file? If the file changes mid-implementation, the requirement becomes a moving target. [Ambiguity, Spec §FR-017]
  > **RESOLVED**: Non-actionable meta concern. `design/landing.html` is tracked in git. If it changes during implementation, the change will be visible in git diff and can be addressed. Not a blocking gap.
- [x] CHK015 — Are the three exact counter labels for "L'Archive en Mouvement" (FR-013) specified in requirements — "livres répertoriés", "aventuriers inscrits", "contributions validées" — or only in the static design reference? [Gap, Spec §FR-013]
  > **RESOLVED**: FR-013 now explicitly defines all three labels: "livres répertoriés" (`total_books`), "aventuriers inscrits" (`total_users`), "livres répertoriés" (`total_books`). Design file still shows "Contributions validées" as third label — spec overrides design file for this label per CHK003 resolution.

---

## Requirement Consistency

- [x] CHK016 — Does FR-005 ("l'ensemble du site") explicitly list which page templates require dark mode CSS overrides? [Ambiguity, Spec §FR-005]
  > **VERIFIED**: Dark mode CSS already implemented via `[data-theme="dark"]` pattern in: `_livre.scss`, `_auteur.scss`, `_collection.scss`, `_suggestions.scss`, `menus.css`, `auth.css`. Site-wide coverage is real. FR-005 gap is a documentation issue, not an implementation risk. [Low priority]
- [x] CHK017 — Are FR-012 ("Explorer le catalogue" → no filter) and FR-016 ("Voir le catalogue" → CTA section) consistent? [Consistency, Spec §FR-012, §FR-016]
  > **PASS**: Both use `app_catalogue` route. FR-016 says "mêmes comportements" referencing FR-012. Route name confirmed in `CatalogueController.php:27`.
- [x] CHK018 — Is FR-020 (anti-flash inline script sets `data-theme` on `document.documentElement`) consistent with T017 (theme_controller also sets it)? Are there two competing writers? [Consistency, Spec §FR-020]
  > **RESOLVED**: Not competing. Anti-flash script runs synchronously during HTML parse — prevents FOUC before any CSS loads. `theme_controller.connect()` runs after Stimulus initializes — syncs button icon state. Both read `localStorage.theme` and set `document.documentElement.dataset.theme`. They always converge to the same value. Sequential, not concurrent.
- [x] CHK019 — Does the assumption "paramètre de recherche est `q`" appear as formal requirement in FR-007? [Gap, Spec §FR-007]
  > **PASS**: `ActiveFilterState::fromRequest()` (line 59) reads `$request->query->get('q')` and `toUrlParams()` outputs `params['q'] = $this->searchQuery`. Round-trip confirmed. Route `app_catalogue` confirmed.
- [x] CHK020 — Is the redirect type for FR-002 specified? [Gap, Spec §FR-002]
  > **PASS**: Symfony `redirectToRoute()` issues 302 by default. No risk of 301 permanent caching unless explicitly set.

---

## Mobile / Touch Interaction Coverage

- [x] CHK021 — Are touch-scroll requirements defined for the marquee on mobile? Spec specifies hover pause (desktop) and tap navigation, but does not address whether native horizontal touch-scroll within the marquee track is intended. [Gap, Spec §FR-008, §FR-009]
  > **RESOLVED**: Under `prefers-reduced-motion: reduce`, design file sets `overflow-x: auto` on `.hero-marquee` — native horizontal touch-scroll becomes available. Under normal motion, CSS animation runs continuously; tap = direct navigation (native `<a>` behavior). No additional requirement.
- [x] CHK022 — Is the stats pilule responsive layout specified for mobile viewports? [Gap, Spec §FR-006, §FR-017]
  > **PASS**: `design/landing.html` defines multiple mobile breakpoints (480px, 600px, 720px, 760px, 640px). Mobile layout is defined in the reference file. FR-017 delegation covers this.
- [x] CHK023 — Are search bar requirements specified for mobile — keyboard appearance (`inputmode`, `autocomplete` attributes), and accessibility when software keyboard is open? [Gap, Spec §FR-007]
  > **RESOLVED**: Implementation adds `inputmode="search"` and `autocomplete="off"` as best practice (improves mobile UX, no design deviation). No layout requirement for open-keyboard state; design responsive breakpoints handle viewport changes.
- [x] CHK024 — Is the marquee auto-scroll behavior defined for mobile while the user is vertically scrolling the page? [Gap, Spec §FR-008]
  > **RESOLVED**: CSS animation continues during vertical scroll — no pause requirement on mobile. Pause is hover-only (desktop). Spec Clarifications: "tap = navigation directe, défilement normal".
- [x] CHK025 — Are requirements defined for marquee on low-end mobile devices (GPU-limited, throttled animations)? [Gap, Spec §FR-008]
  > **RESOLVED**: `prefers-reduced-motion: reduce` fallback (per design file + CHK006) disables animation and enables overflow-x scroll. Covers GPU-constrained devices whose OS sets reduced-motion preference. No further requirement.
- [x] CHK026 — Is there a requirement that marquee items are reachable via keyboard/tab on mobile, or explicitly excluded? An infinite list of tab-stops may trap keyboard users. [Gap, Spec §FR-019]
  > **RESOLVED**: Marquee `<a>` items are naturally keyboard-focusable (required by FR-019 WCAG AA). Skip-link at page top allows bypassing the marquee. ~30 tab stops is acceptable per WCAG SC 2.4.1 (Bypass Blocks). No additional skip mechanism required.

---

## Error / Resilience Scenario Coverage

- [x] CHK027 — Is the counter animation failure path defined when backend returns `0` for `total_books` or `total_users`? Counter animating 0→0 is indistinguishable from a failed fetch. Should display "--" like the stats pilule? [Gap, Spec §Edge Cases]
  > **RESOLVED**: T015 already specifies: "if value is 0 or NaN display '--' and skip". `data-target="0"` is the initial (pre-API) state → shows "--". On fresh install where DB genuinely has 0 books, counters show "--" — acceptable, consistent with "never show 0 which would be misleading" (spec Edge Cases).
- [x] CHK028 — Is counter animation behavior defined when the user scrolls into the section before the stats API fetch completes? Spec does not specify whether animation waits for `data-target` to be set or fires with `data-target="0"`. [Gap, Spec §FR-013, §FR-014]
  > **RESOLVED**: Implementation decision — T016 after setting `data-target` on counter spans will check if the counter section is already in the viewport (via `getBoundingClientRect()` or stored intersection state). If already visible, triggers animation immediately. Prevents race condition where IntersectionObserver fired + disconnected before data arrived.
- [x] CHK029 — Is `IntersectionObserver` browser support fallback specified? [Gap, Spec §FR-014]
  > **RESOLVED**: IntersectionObserver supported in Chrome 51+, Firefox 55+, Safari 12.1+, Edge 15+. All targeted browsers (last 2 major versions per CHK012) support it natively. No fallback needed.
- [x] CHK030 — Is behavior defined when marquee API returns partial response (some items malformed) — skip silently or hide block? [Gap, Spec §Edge Cases]
  > **RESOLVED**: Implementation decision — landing_controller skips malformed items (missing required fields) and renders valid ones. Block only hidden if resulting valid item count is 0.
- [x] CHK031 — Are requirements defined for localStorage unavailability (private browsing, quota exceeded)? FR-005 has no fallback. [Gap, Spec §FR-005]
  > **RESOLVED**: Implementation decision — `try/catch` around all localStorage reads/writes. Default theme: `'parchment'` (light mode). No user-visible error.

---

## Accessibility Requirements Coverage

- [x] CHK032 — Are contrast ratio requirements specified for skeleton placeholder colors in light and dark themes? [Gap, Spec §FR-018, §FR-019]
  > **RESOLVED**: Skeleton placeholders are decorative (non-text) — WCAG 1.4.11 (Non-text Contrast) requires 3:1 minimum against adjacent colors. Follow design reference colors; verify 3:1 ratio during T010 SCSS implementation.
- [x] CHK033 — Is keyboard navigation through the marquee defined? Infinite focusable `<a>` elements may create unusable tab flow. Is a skip mechanism beyond the skip-link required? [Gap, Spec §FR-019]
  > **RESOLVED**: Skip-link at top of page satisfies WCAG SC 2.4.1. Marquee `<a>` elements are expected focusable landmarks. No additional skip mechanism required.
- [x] CHK034 — Is `aria-pressed` state management for the theme toggle specified in requirements, or only in tasks T019? [Gap, Spec §FR-003, §FR-005]
  > **RESOLVED**: T019 specifies `aria-pressed="false"` initial value. `theme_controller.toggle()` action updates `aria-pressed` on each toggle. Standard ARIA pattern for toggle buttons.
- [x] CHK035 — Are ARIA live region requirements defined for the stats pilule and counters? Screen readers won't announce skeleton→data transitions without `aria-live`. [Gap, Spec §FR-006, §FR-013, §FR-019]
  > **RESOLVED**: Add `aria-live="polite"` to stats pilule container and counter section (required by FR-019 WCAG AA). Screen readers announce value updates after skeleton→data transition.
- [x] CHK036 — Does the spec define visible focus ring requirements with measurable properties? FR-019 mentions "focus visible" without quantification. [Ambiguity, Spec §FR-019]
  > **RESOLVED**: `design/landing.html` uses `--ring-focus` CSS custom property with `:focus-visible` pseudo-class. Implementation follows design reference; ring must be visible on all interactive elements (T020 accessibility audit).

---

## Non-Functional Requirements

- [x] CHK037 — Are performance requirements defined for skeleton-to-content transition time, separately from overall page load SC-001? [Gap, Spec §SC-001, §FR-018]
  > **RESOLVED**: No separate SLA. Skeleton→content transition time is a subset of SC-001's 2s budget (skeleton renders instantly from server; API fetch must complete within 2s). SC-001 covers this adequately.
- [x] CHK038 — Are analytics/event-tracking requirements defined for CTA clicks? [Gap]
  > **RESOLVED**: Analytics explicitly out of scope for MVP. No tracking code added.
- [x] CHK039 — Are print stylesheet requirements defined or explicitly excluded? [Gap]
  > **RESOLVED**: Print stylesheets explicitly out of scope. No `@media print` rules required.

---

## Dependencies & Assumptions Validation

- [x] CHK040 — Are repository methods `findMostPopular()`, `countAll()`, `countPublishedSince()`, `countActive()` validated? [Assumption, Spec §Assumptions]
  > **PASS**: All methods exist and confirmed:
  > - `BookRepository::countAll()` ✅ · `countPublishedSince(\DateTimeImmutable)` ✅ · `findMostPopular(int)` ✅
  > - `ContributorRepository::countAll()` ✅ · `findMostPopular(int)` ✅
  > - `CollectionRepository::findMostPopular(int)` ✅
  > - `UserRepository::countActive()` ✅
- [x] CHK041 — Is the claim "endpoints existants déjà en place" accurate? [Conflict, Spec §Assumptions]
  > **RESOLVED**: Assumption corrected. Repositories and service methods exist (CHK040). `/api/public/stats` and `/api/public/marquee` are NEW endpoints — created by T005+T006. Clarification session and Assumptions updated in spec.
- [x] CHK042 — Are route names `app_login`, `app_register`, `app_catalogue` validated? [Assumption, Spec §Assumptions]
  > **PASS**: All confirmed in codebase — `SecurityController:26` (`app_login`), `RegistrationController:29` (`app_register`), `CatalogueController:27` (`app_catalogue`).

---

## Resolved Decisions

- **Q1 — localStorage values**: `'parchment'` (light) / `'dark'` (dark). T017 updated. `profile_menu_controller` unchanged.
- **Q2 — Third counter**: label "livres répertoriés", metric `total_books`. FR-013 and T016 updated.
- **Q3 — Endpoints**: repositories exist, `/api/public/*` endpoints are new (T005+T006). Assumption corrected.
- **Q4 — SEO**: Add `<meta name="description">` + Open Graph tags to `templates/landing/index.html.twig`. T009 updated.
- **Q5 — Caching**: No caching for MVP. Post-MVP concern.
- **Q6 — Null slug**: `LandingService::getMarqueeItems()` skips entities with null/empty slug. No dead links.
