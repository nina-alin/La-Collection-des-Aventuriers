# Tasks: Landing Page Publique Dynamique

**Feature**: `020-landing-page-dynamic`
**Branch**: `020-landing-page-dynamic`
**Tech Stack**: PHP 8.2, Symfony 6.4 LTS, Doctrine ORM, Twig, Bootstrap 5, SCSS, Webpack Encore, Stimulus (Hotwire)
**Design Reference**: `design/landing.html` (absolute reference — match exactly)

## Format: `[ID] [P?] [Story?] Description with file path`

- **[P]**: Can run in parallel (different files, no blocking dependencies)
- **[Story]**: User story this task belongs to
- Exact file paths in all descriptions

---

## Phase 1: Setup — Route Migration

**Purpose**: Move the `/` route from `DashboardController` to a new public `LandingController`. Required before any landing page work can start.

- [X] T001 Update `src/Controller/DashboardController.php` — change `#[Route('/', name: 'home')]` to `#[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]`; remove the class-level `#[IsGranted('ROLE_USER')]` attribute; add method-level `#[IsGranted('ROLE_USER')]` directly on the `home()` action
- [X] T002 Update `config/packages/security.yaml` — (1) in `access_control`, add `{ path: ^/api/public/, roles: PUBLIC_ACCESS }` before any authenticated path entry; (2) in `form_login`, change `default_target_path: /` to `default_target_path: /dashboard`; the `/dashboard` route is protected by method-level `#[IsGranted]` added in T001 — no separate ACL entry is needed
- [X] T003 [P] Update `src/Controller/SecurityController.php` — find the `redirectToRoute('home')` call (used when an already-authenticated user hits `/connexion`) and change it to `redirectToRoute('app_dashboard')`; run `grep -n "redirectToRoute" src/Controller/SecurityController.php` to locate the exact line
- [X] T004 [P] Verify `path('home')` template references — run `grep -rn "path('home')" templates/` to audit; **NO changes needed**: the new `LandingController` keeps `name: 'home'` on `Route('/')`, so authenticated users following these links are transparently redirected to `/dashboard` by `LandingController` (one extra hop, acceptable per research.md); if `templates/components/Layout/Navbar.html.twig` has a dedicated authenticated "Dashboard" nav item, update only that link to `path('app_dashboard')` for a direct route

**Checkpoint**: `GET /dashboard` works for authenticated users. `/` returns 404 (LandingController not yet created).

---

## Phase 2: Foundational — Core Landing Infrastructure

**Purpose**: Backend service, DTOs, public API endpoints, controller, and anti-flash script. All user stories depend on this phase.

**Checkpoint**: `GET /` renders a skeleton-only landing page. `GET /api/public/stats` and `GET /api/public/marquee` return correct JSON to anonymous callers.

- [X] T005 Create DTOs and `src/Service/LandingService.php` — (1) create `src/Dto/LandingStatsDto.php` as `readonly class LandingStatsDto` with constructor `(public int $totalBooks, public int $totalUsers, public int $newThisWeek)`; (2) create `src/Dto/MarqueeItemDto.php` as `readonly class MarqueeItemDto` with constructor `(public string $name, public string $type, public string $url, public string $subtitle, public string $initials, public string $colorClass)`; (3) create `src/Service/LandingService.php` injecting `BookRepository`, `ContributorRepository`, `CollectionRepository`, `UserRepository`; implement `getStats(): LandingStatsDto` using `BookRepository::countPublished()` (total_books), `UserRepository::countActive()` (total_users), `BookRepository::countPublishedSince(new \DateTimeImmutable('-7 days', new \DateTimeZone('UTC')))` (new_this_week); implement `getMarqueeItems(): array` fetching `BookRepository::findMostPopular(10)`, `ContributorRepository::findMostPopular(10)`, `CollectionRepository::findMostPopular(10)`, mapping each to `MarqueeItemDto`: books → name=title, type='book', url='/livre/{slug}', subtitle=`$book->getFrenchPublicationYear() !== null ? "Livre · {frenchPublicationYear}" : "Livre"`, initials=first word of title (max 8 chars), colorClass=round-robin from `['bg-cuir','bg-mousse','bg-encre','bg-sang','bg-or']`; authors → name="{firstName} {lastName}" or pseudo, type='author', url='/authors/{slug}', subtitle="Auteur · {N} œuvres" where N=contributions count, initials=initials of full name, colorClass='is-author'; collections → name=nom, type='collection', url='/collections/{slug}', subtitle="Collection · {N} tomes" where N=books count, initials=first word of nom (max 8 chars), colorClass='bg-grimoire'; merge arrays, shuffle, return up to 30 items
- [X] T006 [P] Create `src/Controller/PublicApiController.php` — no `#[IsGranted]` (fully public); inject `LandingService`; `#[Route('/api/public/stats', name: 'api_public_stats', methods: ['GET'])]` → call `getStats()`, serialize DTO to `JsonResponse(['total_books' => $dto->totalBooks, 'total_users' => $dto->totalUsers, 'new_this_week' => $dto->newThisWeek])`; on any `\Throwable` return `JsonResponse(['error' => 'unavailable'], 503)`; `#[Route('/api/public/marquee', name: 'api_public_marquee', methods: ['GET'])]` → call `getMarqueeItems()`, serialize each `MarqueeItemDto` to `['name', 'type', 'url', 'subtitle', 'initials', 'color_class']` array, return `JsonResponse($items)` (raw array, NOT wrapped in `{'items': ...}`); on any `\Throwable` or empty result on error return `JsonResponse(['error' => 'unavailable'], 503)`
- [X] T007 [P] Create `src/Controller/LandingController.php` — no `#[IsGranted]`; `#[Route('/', name: 'home', methods: ['GET'])]`; if `$this->getUser() !== null` return `$this->redirectToRoute('app_dashboard')`; else return `$this->render('landing/index.html.twig')` with no template variables (all data loaded async by JS)
- [X] T008 [P] Modify `templates/base.html.twig` — add inline `<script>` in `<head>` **before** the first `{% block stylesheets %}` / `encore_entry_link_tags` line: `(function(){var t=localStorage.getItem('theme');if(t==='dark'){document.documentElement.setAttribute('data-theme','dark');}})();` wrapped in `<script>` tags; this prevents flash of unstyled content on dark mode reload (FR-020)

---

## Phase 3: User Story 1 — Visiteur non-connecté découvre la plateforme (Priority: P1) 🎯 MVP

**Goal**: Anonymous visitor at `/` sees the full landing page structure with skeleton placeholders; within ~1s placeholders are replaced by real stats and marquee items.

**Independent Test**: Open `GET /` in a fresh browser session (no cookie) → full landing page renders with animated skeleton zones → real data populates stats pilule and marquee track → no redirect to login.

- [X] T009 [US1] Create `templates/landing/index.html.twig` — extends `templates/base.html.twig`; override `{% block body %}` with the full HTML structure from `design/landing.html` exactly: (1) `<a href="#main-content" class="skip-link">Aller au contenu principal</a>` as first child of body; (2) public navbar: logo/title linking to `#top`, theme toggle button `<button data-action="click->theme#toggle" aria-label="Basculer le thème">` with moon/sun Bootstrap Icons, "Se connecter" link to `{{ path('app_login') }}`; (3) `<main id="main-content">` wrapping all page content; (4) hero section with `data-controller="landing"`: stats pilule skeleton div (`data-landing-target="pilule"`), heading, search form (`data-landing-target="searchForm"` with `role="search"`) containing input (`data-landing-target="searchInput"` with `aria-label="Rechercher un titre"`) + "Explorer" submit button, marquee wrapper (`data-landing-target="marqueeBlock"`) with inner track (`data-landing-target="marqueeTrack"`) containing 6 skeleton item divs; (5) ecosystem section (static); (6) 3 pillars section (static); (7) `<section data-controller="counter">` "L'Archive en Mouvement" with 3 counter `<span data-counter-target="number" data-target="0">0</span>` elements; (8) CTA section with "Nous rejoindre" → `{{ path('app_register') }}` and "Voir le catalogue" → `{{ path('app_catalogue') }}`
- [X] T010 [P] [US1] Create `assets/styles/pages/_landing.scss` — port all CSS from the `<style>` block in `design/landing.html`: hero section, navbar, marquee track `@keyframes marquee` animation, marquee item styles, `.hero-marquee:hover .marquee-track { animation-play-state: paused; }` (CSS-only hover pause per research.md), counter section, ecosystem and pillars sections, CTA section, dark mode overrides (`[data-theme="dark"]` variable overrides), skeleton pulse `@keyframes skeleton-pulse { 0%,100% { opacity:0.4 } 50% { opacity:0.8 } }` with `.skeleton` class, `:focus-visible` ring on all interactive elements, all responsive breakpoints; add import to `assets/styles/app.scss` using the project's existing `@use`/`@import` pattern
- [X] T011 [US1] Create `assets/controllers/landing_controller.js` Stimulus controller — `static targets = ['pilule', 'marqueeTrack', 'marqueeBlock', 'searchForm', 'searchInput']`; on `connect()`: (1) `fetch('/api/public/stats')` — on HTTP 200 set `data-target` integer attribute on each of the 3 counter `<span>` elements (books=`total_books`, users=`total_users`, third=`total_books`), populate pilule HTML with `total_books` + `total_users` + `new_this_week` values, remove skeleton class; on error (non-200 or network failure) set `this.piluleTarget.style.display='none'` and set counter spans to `--`; (2) `fetch('/api/public/marquee')` — on success build `<a href="${item.url}" class="marquee-item" aria-label="${item.name}">` elements per `design/landing.html` markup for each item, append to `this.marqueeTrackTarget`, remove skeleton divs; on error or empty array set `this.marqueeBlockTarget.style.display='none'`
- [X] T012 [P] [US1] Verify Stimulus wiring completeness in `templates/landing/index.html.twig` (created in T009) — confirm `data-controller="landing"` on hero section, all `data-landing-target` attributes present on pilule/marqueeTrack/marqueeBlock/searchForm/searchInput, skeleton placeholder markup for both pilule (1 div) and marquee track (6 divs) with the `skeleton` CSS class; confirm `data-controller="counter"` on the Archive section and `data-counter-target="number"` + `data-target="0"` on all 3 counter spans

**Checkpoint**: US1 complete — anonymous visitor sees live stats and marquee items on the landing page.

---

## Phase 4: User Story 2 — Visiteur effectue une recherche (Priority: P2)

**Goal**: Search bar in hero redirects to catalogue with pre-filled query param.

**Independent Test**: Type "Lone Wolf" in the hero search input → press Enter (or click "Explorer") → browser navigates to `/catalogue?q=Lone+Wolf` with results. Empty search → `/catalogue` with no query param.

- [X] T013 [US2] Add search logic to `assets/controllers/landing_controller.js` — in `connect()`, add submit listener on `this.searchFormTarget`: `event.preventDefault()`, read `this.searchInputTarget.value.trim()`, if non-empty navigate to `/catalogue?q=${encodeURIComponent(term)}` via `window.location.href`, if empty navigate to `/catalogue`; search param must be `q` (maps to `ActiveFilterState::fromRequest()` which reads `$request->query->get('q')`) and route name is `app_catalogue`

**Checkpoint**: US2 complete — search redirect works from landing page.

---

## Phase 5: User Story 3 — Visiteur interagit avec le bandeau défilant (Priority: P2)

**Goal**: Marquee pauses on hover (CSS), each item navigates to the correct entity page on click.

**Independent Test**: Hover over any marquee item → scroll animation pauses. Move mouse away → scroll resumes. Click an item → correct entity page opens.

- [X] T014 [US3] Verify marquee interaction completeness in `assets/styles/pages/_landing.scss` and `assets/controllers/landing_controller.js` — confirm `.hero-marquee:hover .marquee-track { animation-play-state: paused; }` is present in the SCSS (hover pause is pure CSS, no JS needed per research.md); confirm each marquee `<a>` built in T011 has: correct `href` per FR-010 patterns (`/livre/{slug}`, `/collections/{slug}`, `/authors/{slug}`), accessible `aria-label="${item.name}"`, and visible `:focus-visible` ring; confirm no `pointer-events: none` anywhere on the marquee chain

**Checkpoint**: US3 complete — marquee fully interactive via CSS + semantic anchor links.

---

## Phase 6: User Story 4 — Visiteur voit les statistiques animées en scrollant (Priority: P3)

**Goal**: Three counters in "L'Archive en Mouvement" animate 0→N on first viewport entry, then stop.

**Independent Test**: Load page → counters show "0" → scroll into "L'Archive en Mouvement" section → counters animate to real values over 2s ease-out → scroll away and back → animation does NOT replay.

- [X] T015 [P] [US4] Create `assets/controllers/counter_controller.js` Stimulus controller — `static targets = ['number']`; on `connect()`: set up `IntersectionObserver` on `this.element` with threshold `0.3`; when intersection fires and `isIntersecting`: for each `this.numberTargets` read `parseInt(el.dataset.target, 10)` as the target value (set by landing_controller after stats fetch); if value is 0 or NaN display `'--'` and skip; animate with `requestAnimationFrame` loop over 2000ms: compute `t = elapsed/2000` clamped to 1, apply ease-out `eased = 1 - Math.pow(1 - t, 3)`, set `el.textContent = Math.round(eased * target).toLocaleString('fr-FR')`; stop at 2000ms; after first trigger `this.observer.disconnect()` so animation never replays (FR-014)
- [X] T016 [US4] Wire counter data in `assets/controllers/landing_controller.js` — in the stats fetch success handler (T011), after populating the pilule, also set `data-target` attribute on the 3 counter spans: books counter (label "livres répertoriés") → `total_books`, users counter (label "aventuriers inscrits") → `total_users`, third counter (label "livres répertoriés" — "contributions validées" label abandoned, see FR-013 clarification) → `total_books`; query spans via `document.querySelectorAll('[data-counter-target="number"]')` or pass via a Stimulus outlet if preferred

**Checkpoint**: US4 complete — animated counters fire once when section enters viewport.

---

## Phase 7: User Story 5 — Visiteur bascule le thème clair/sombre (Priority: P3)

**Goal**: Theme toggle switches the entire site between light/dark, persisted in localStorage across all pages and reloads.

**Independent Test**: Click moon/sun button → site switches dark mode → reload any page → dark mode preserved → click again → back to light.

- [X] T017 [US5] Create `assets/controllers/theme_controller.js` Stimulus controller — on `connect()`: read `localStorage.getItem('theme') || 'parchment'`, call `document.documentElement.setAttribute('data-theme', theme)`, sync button icon to match; `toggle()` action: get current `document.documentElement.getAttribute('data-theme')`, compute next (`'parchment'` → `'dark'` and vice versa), call `setAttribute('data-theme', next)`, save `localStorage.setItem('theme', next)`, update button icon (Bootstrap Icon `bi-moon-fill` when in parchment mode, `bi-sun-fill` when in dark mode); values MUST be `'parchment'`/`'dark'` (not `'light'`) to stay compatible with `profile_menu_controller.js` which shares the same localStorage key (FR-005, spec clarification)
- [X] T018 [P] [US5] Audit and complete dark mode CSS in `assets/styles/` — check `assets/styles/tokens/_colors.scss` for existing `[data-theme="dark"]` variable overrides; add missing overrides for CSS custom properties used across all site pages (catalogue, dashboard, livre, auteur, collection pages) matching the dark palette from `design/landing.html`; note `assets/styles/app.scss` already imports `bootstrap/scss/variables-dark` so Bootstrap dark variables are available
- [X] T019 [P] [US5] Wire theme controller in `templates/base.html.twig` — add `data-controller="theme"` on the `<body>` tag; update `templates/components/Layout/Navbar.html.twig` to add the theme toggle button `<button data-action="click->theme#toggle" class="lp-theme-toggle" aria-label="Basculer le thème" aria-pressed="false">` with Bootstrap Icons `<i class="bi bi-moon-fill"></i>` / `<i class="bi bi-sun-fill"></i>` inside; button must render for both anonymous and authenticated users (site-wide, FR-005, SC-007)

**Checkpoint**: US5 complete — theme persists site-wide after page reload.

---

## Phase 8: Polish & Cross-Cutting Concerns

- [X] T020 Accessibility audit `templates/landing/index.html.twig` — verify: skip-link `<a href="#main-content" class="skip-link">` is first child of body with visible focus style in SCSS; `id="main-content"` on `<main>`; hero search form has `role="search"`; all icon-only buttons (theme toggle, search submit) have `aria-label`; all marquee `<a>` links have `aria-label`; Tab order is logical (navbar → hero → search → marquee → ecosystem → pillars → counters → CTA); focus ring visible on all interactive elements; run axe or Lighthouse accessibility audit (FR-019, SC-007)
- [X] T021 [P] Update `templates/components/Layout/Navbar.html.twig` — add conditional: `{% if app.user is null %}` show public nav items (theme toggle, "Se connecter" link → `path('app_login')`); `{% else %}` show existing authenticated nav items; theme toggle button appears in both states (FR-003)
- [X] T022 [P] Review `templates/components/Layout/Footer.html.twig` — confirm no auth-specific content leaks to anonymous users on the landing page; add `{% if app.user %}` guards around any authenticated-only footer links if present

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No prerequisites — start immediately
- **Phase 2 (Foundational)**: Requires Phase 1 (route `app_dashboard` must exist before `LandingController` can redirect to it — T007 depends on T001)
- **Phase 3 (US1)**: Requires Phase 2 complete (T005 LandingService + T006 API endpoints + T007 LandingController + T008 anti-flash)
- **Phase 4 (US2)**: Requires Phase 3 (search bar must exist in template from T009)
- **Phase 5 (US3)**: Requires Phase 3 (marquee populated by T011, CSS from T010)
- **Phase 6 (US4)**: Requires Phase 3 (stats fetched in T011, counter targets set by T016 depends on T011)
- **Phase 7 (US5)**: Requires Phase 2 T008 (anti-flash script) and T019 depends on T017
- **Phase 8 (Polish)**: Requires all user story phases complete

### User Story Dependencies

- **US1 (P1)**: No story dependencies — start after Phase 2
- **US2 (P2)**: Depends on US1 template (T009) and search input target
- **US3 (P2)**: Depends on US1 marquee build (T011) for `<a>` link structure
- **US4 (P3)**: Depends on US1 stats fetch (T011) which sets `data-target` on counter spans
- **US5 (P3)**: Depends on Phase 2 anti-flash script (T008); T019 depends on T017

### Within Each User Story

- T005 (Service + DTOs) before T006 (API controller) and T007 (LandingController)
- T009 (template) before T012 (wiring verification) and T013 (search logic)
- T011 (JS fetch logic) before T013 (search) and T016 (counter data wiring)
- T015 (counter controller) before T016 (counter wiring) can be verified end-to-end
- T017 (theme controller) before T019 (wiring in base.html.twig)

---

## Parallel Opportunities

```bash
# Phase 1: T003 and T004 run in parallel (different files, independent)
Task T003: "Update SecurityController redirectToRoute to app_dashboard"
Task T004: "Verify path('home') references in templates (audit only)"

# Phase 2: T006, T007, T008 can all run in parallel after T005
Task T006: "Create src/Controller/PublicApiController.php"
Task T007: "Create src/Controller/LandingController.php"
Task T008: "Add anti-flash script to templates/base.html.twig"

# Phase 3: T009 and T010 run in parallel
Task T009: "Create templates/landing/index.html.twig"
Task T010: "Create assets/styles/pages/_landing.scss"

# Phase 7: T018 and T019 run in parallel after T017
Task T018: "Complete dark mode CSS tokens"
Task T019: "Wire theme controller in base.html.twig / Navbar"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (route migration — T001–T004)
2. Complete Phase 2: Foundational (T005–T008)
3. Complete Phase 3: User Story 1 (T009–T012)
4. **STOP and VALIDATE**: Open `GET /` as anonymous → verify full landing page with live data; verify `GET /dashboard` redirects to login for anonymous, loads dashboard for authenticated
5. Deploy/demo if ready

### Incremental Delivery

1. Phase 1 + Phase 2 → `GET /` shows skeleton-only landing page
2. Phase 3 (US1) → full landing with real data (MVP!)
3. Phase 4 (US2) → search redirect works
4. Phase 5 (US3) → marquee links and hover pause verified
5. Phase 6 (US4) → counter animation on scroll
6. Phase 7 (US5) → theme toggle persists site-wide
7. Phase 8 (Polish) → WCAG AA compliance

---

## Key Implementation Notes

- **Design reference**: `design/landing.html` is absolute — port HTML structure and all CSS to SCSS verbatim; no design freedom
- **Route names**: `home` (LandingController, `/`) and `app_dashboard` (DashboardController, `/dashboard`); `path('home')` in templates remains valid and resolves to `/` which redirects authenticated users
- **Marquee hover pause**: pure CSS via `.hero-marquee:hover .marquee-track { animation-play-state: paused }` — no JS needed (research.md decision)
- **Search URL param**: use `q` (not `searchQuery`) — `ActiveFilterState::fromRequest()` reads `$request->query->get('q')`; catalogue route name is `app_catalogue`
- **Dark mode attribute**: `data-theme` on `<html>` element (per `design/landing.html`) — not a body class
- **Theme localStorage values**: `'parchment'` for light (not `'light'`), `'dark'` for dark — must match `profile_menu_controller.js` (spec clarification)
- **Stats API shape**: `{total_books, total_users, new_this_week}` — no `total_authors` field (contract is authoritative; "aventuriers inscrits" UI label = `total_users`)
- **Marquee API shape**: raw JSON array (not `{"items": [...]}`) — `JsonResponse($items)` not `JsonResponse(['items' => $items])`
- **Error responses**: HTTP 503 with `{"error": "unavailable"}` for both API endpoints on database failure
- **Existing stats methods** (validated in research.md, no new repo methods needed): `BookRepository::countPublished()`, `BookRepository::countPublishedSince(\DateTimeImmutable)`, `UserRepository::countActive()`
- **Existing popular entity methods**: `BookRepository::findMostPopular(int)`, `ContributorRepository::findMostPopular(int)`, `CollectionRepository::findMostPopular(int)`
- **Controller location**: `src/Controller/PublicApiController.php` (not `src/Controller/Api/`) per plan.md structure
- **No new database migrations**: feature is frontend/controller only, no schema changes
