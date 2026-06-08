---

description: "Task list for Page Mentions Légales implementation"
---

# Tasks: Page Mentions Légales

**Input**: Design documents from `/specs/021-mentions-legales-page/`

**Prerequisites**: plan.md ✓, spec.md ✓, research.md ✓, data-model.md ✓, contracts/http-routes.md ✓, quickstart.md ✓

**Tests**: Smoke test only — no TDD approach requested. One functional test asserting HTTP 200.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)
- All file paths are relative to the repository root

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Configure the Symfony parameter and create the controller. These two tasks are independent (different files) and can run in parallel.

- [X] T001 [P] Add `app.legal.last_updated: "3 juin 2026"` under `parameters` and wire `App\Controller\LegalController` argument `$legalLastUpdated: '%app.legal.last_updated%'` under `services` in `config/services.yaml`
- [X] T002 [P] Create `src/Controller/LegalController.php` with constructor injecting `string $legalLastUpdated`, and a single `#[Route('/mentions-legales', name: 'app_mentions_legales', methods: ['GET'])]` action returning `$this->render('legal/mentions-legales.html.twig', ['lastUpdated' => $this->legalLastUpdated])`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Base template scaffold and smoke test — both tasks are independent (different files) and can run in parallel. The smoke test validates the route once the template exists.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [X] T003 [P] Create `templates/legal/mentions-legales.html.twig` minimal skeleton extending `base.html.twig`, overriding `{% block title %}`, `{% block meta %}`, and `{% block page_wrapper %}` blocks (empty content for now — filled in US1 phase)
- [X] T004 [P] Create `tests/Functional/Controller/LegalControllerTest.php` extending `WebTestCase` with one test asserting `$this->client->request('GET', '/mentions-legales')` returns HTTP status 200

**Checkpoint**: Route `GET /mentions-legales` is reachable and returns 200 — user story implementation can now begin.

---

## Phase 3: User Story 1 — Consulter les Mentions Légales (Priority: P1) 🎯 MVP

**Goal**: A visitor navigates to `/mentions-legales` via the footer link and reads the full legal content in a well-structured, responsive layout with breadcrumb, TOC, date, and all 9 sections.

**Independent Test**: Navigate to `/mentions-legales`; verify breadcrumb "ACCUEIL > MENTIONS LÉGALES" is present with `/` link, title and last-updated date display correctly, all 9 section headings are present (éditeur, publication, hébergeur, nature, propriété, contributions, données, responsabilité, contact), viewport ≥900px shows two-column layout (240px aside + 1fr), viewport <900px shows single column with TOC before content.

### Implementation for User Story 1

- [X] T005 [P] [US1] Create `templates/legal/_id_card.html.twig`: renders `<div class="id-card">` with one `<div class="id-row">` per entry in the `rows` variable (array of `{key, value}` objects), with rounded-border and beige-background styling
- [X] T006 [P] [US1] Create `templates/legal/_callout.html.twig`: renders `<div class="callout">` containing a decorative triangle SVG with `aria-hidden="true"` and a `<p>` tag outputting the `content` variable (raw HTML string)
- [X] T007 [US1] Implement full page content in `templates/legal/mentions-legales.html.twig`: breadcrumb `<nav>` with ACCUEIL (`/`) > MENTIONS LÉGALES, `<h1>` title, last-updated display guarded by `{% if lastUpdated %}`, `<aside aria-label="Sommaire">` with `<ol id="toc-nav">` listing all 9 anchors (`#editeur` through `#contact`) with two-digit numbers, all 9 `<section class="doc-section" id="...">` elements with `<h2>` headings (styled with `<span class="num">NN</span>` prefix) and body content using `{% include 'legal/_id_card.html.twig' %}` and `{% include 'legal/_callout.html.twig' %}` where appropriate, `{% block title %}Mentions légales — La Collection{% endblock %}`, `{% block meta %}<meta name="description" content="...">{% endblock %}` (no noindex), and doc-layout grid CSS in a `<style>` block (`display: grid; grid-template-columns: 240px 1fr` at `@media (min-width: 900px)` with `max-width: 720px` on the content column)
- [X] T008 [P] [US1] Wire footer link in `templates/components/Layout/Footer.html.twig`: replace the existing `<a href="#">Mentions légales</a>` with `<a href="{{ path('app_mentions_legales') }}">Mentions légales</a>`

**Checkpoint**: User Story 1 fully functional — page accessible via footer, all content readable, responsive layout correct.

---

## Phase 4: User Story 2 — Naviguer via le Sommaire Interactif (Priority: P1)

**Goal**: A desktop visitor uses the sticky sidebar TOC to jump directly to any section and sees the active item update in real time as they scroll.

**Independent Test**: On a viewport ≥900px, click each TOC item and verify smooth scroll to the target section; scroll manually and verify the corresponding TOC item becomes visually active (bold, distinct color, lateral indicator); verify the TOC remains visible and fixed as the page scrolls; on viewport <900px, verify the TOC appears as a plain non-sticky list and no active-state highlighting occurs.

### Implementation for User Story 2

- [X] T009 [US2] Add to the `<style>` block in `templates/legal/mentions-legales.html.twig`: `html { scroll-behavior: smooth; }`, sticky positioning for `aside.toc` (`position: sticky; top: 88px; align-self: start`), and active-state visual styles for `.toc a.active` (font-weight bold, distinct brand color, left border/indicator) — active styles apply only inside the `@media (min-width: 900px)` rule
- [X] T010 [US2] Add inline `<script>` at the bottom of `templates/legal/mentions-legales.html.twig` (before `{% endblock %}`): (1) **unconditionally** on page load, set `aria-current="true"` plus Bootstrap's `active` class on the first `<a>` inside `#toc-nav`; (2) **conditionally** when `window.innerWidth >= 900`, initialize Bootstrap ScrollSpy via `new bootstrap.ScrollSpy(document.body, { target: '#toc-nav', rootMargin: '-88px 0px -65% 0px' })` — these are two separate operations; the initial active state is not guarded

**Checkpoint**: User Story 2 fully functional — sticky TOC, smooth scroll, live active-state tracking on desktop, plain list on mobile.

---

## Phase 5: User Story 3 — Accéder aux Liens Internes (Priority: P2)

**Goal**: A visitor reading the legal content can click inline links to navigate to referenced pages (contact, politique de confidentialité, charte communautaire).

**Independent Test**: Click the link to "page de contact" in the relevant section — verify it points to `/contact` (or `#` as acceptable placeholder); click the link to "politique de confidentialité" — verify it points to `/politique-de-confidentialite` (or `#`); click the link to "charte communautaire" — verify it points to `/charte-communautaire` (or `#`); all links must be visually identifiable (underlined or colored per design system).

### Implementation for User Story 3

- [X] T011 [US3] Add visually-identifiable inline links (underlined or colored, per existing design-system conventions) to the applicable sections in `templates/legal/mentions-legales.html.twig`: contact page → `{{ path('app_contact') }}` or `href="#"` if route not yet created, politique de confidentialité → `{{ path('app_confidentialite') }}` or `href="#"`, charte communautaire → `{{ path('app_charte') }}` or `href="#"`

**Checkpoint**: All inline links present and visually identifiable; routing correct or placeholder accepted.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Final validation and any cross-cutting cleanup.

- [X] T012 Run `php bin/phpunit tests/Functional/Controller/LegalControllerTest.php` and confirm the smoke test passes with HTTP 200

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — can start immediately; T001 and T002 run in parallel
- **Foundational (Phase 2)**: Depends on Phase 1 completion (controller references template; test targets the route) — T003 and T004 run in parallel
- **User Stories (Phase 3–5)**: All depend on Foundational phase completion
  - US1 (Phase 3) and US2 (Phase 4) are both P1 but US2 enhances the template built in US1 — implement US1 first
  - US3 (Phase 5) is P2 and only touches existing content — implement after US1
- **Polish (Phase 6)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Phase 2 — no dependency on US2 or US3
- **User Story 2 (P1)**: Enhances the template from US1 — start after T007 is complete
- **User Story 3 (P2)**: Adds links to template content from US1 — start after T007 is complete; independent of US2

### Within Each User Story

- **US1**: T005 and T006 (partials, different files) run in parallel → T007 uses both partials → T008 is independent of T007 (different file) and can run in parallel with T007
- **US2**: T009 and T010 both modify the same template file — run sequentially
- **US3**: T011 is a single task

### Parallel Opportunities

```bash
# Phase 1 — run together (different files):
T001  # config/services.yaml
T002  # src/Controller/LegalController.php

# Phase 2 — run together (different files):
T003  # templates/legal/mentions-legales.html.twig (skeleton)
T004  # tests/Functional/Controller/LegalControllerTest.php

# Phase 3 — US1 partials run together, then content:
T005  # templates/legal/_id_card.html.twig
T006  # templates/legal/_callout.html.twig
# — then sequentially —
T007  # templates/legal/mentions-legales.html.twig (full content)
T008  # templates/components/Layout/Footer.html.twig (runs in parallel with T007)
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (T001, T002)
2. Complete Phase 2: Foundational (T003, T004)
3. Complete Phase 3: User Story 1 (T005–T008)
4. **STOP and VALIDATE**: Navigate to `/mentions-legales`, check content and responsive layout, run smoke test
5. Deploy/demo if ready

### Incremental Delivery

1. Phase 1 + Phase 2 → Route exists, returns 200
2. Phase 3 (US1) → Full content page accessible from footer (MVP!)
3. Phase 4 (US2) → Interactive TOC with ScrollSpy
4. Phase 5 (US3) → Inline links wired
5. Phase 6 → Smoke test confirmed green

---

## Notes

- [P] tasks = different files, no conflicting writes — safe to run concurrently
- No new npm packages, no new Composer packages — Bootstrap 5.3 already present
- The 900px breakpoint is intentional and differs from Bootstrap `md` (768px)
- If `/contact`, `/politique-de-confidentialite`, or `/charte-communautaire` routes do not yet exist, use `href="#"` as placeholder (confirmed in clarifications)
- Date format is a pre-formatted French string — no `twig/intl-extra` needed
- Smoke test can be run at any time after Phase 2; it must pass before marking Phase 6 complete
