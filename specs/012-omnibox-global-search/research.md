# Research: Omnibox Global Search

**Phase 0 Output** | Branch: `012-omnibox-global-search`

---

## 1. "Souvent Consultés" data source

**Decision**: Proxy popularity via review count (Books) and book count (Collections/Contributors).

**Rationale**: No user navigation tracking system exists in the codebase. The spec allows "les entités globalement populaires de la plateforme en l'absence d'historique personnel". Using `Review` count for Books and book-count for Collections/Contributors is deterministic, requires no new infrastructure, and degrades gracefully if empty.

**Implementation**: `GlobalSearchService::findPopular()` runs three lightweight queries (max 4 results combined). Each repository adds a `findMostPopular(int $limit): array` method.

**Alternatives considered**:
- Redis hit counter — rejected (no Redis in current infra, Constitution requires .platform files update for new services)
- localStorage tracking — rejected (privacy concern, complicates GDPR surface)
- Random sample — rejected (non-deterministic, poor UX)

---

## 2. Stimulus controller vs. Twig/Turbo component

**Decision**: Vanilla Stimulus controller (`search_controller.js`) — no Live Component, no Turbo Streams.

**Rationale**: The omnibox is a pure frontend interaction. Live Component requires server round-trips for every keystroke, conflicting with the 300ms debounce and abort-on-new-keystroke requirement (FR-019). A Stimulus controller gives full control over AbortController, ARIA `aria-activedescendant`, and skeleton animation — all needed per spec.

**Pattern**: Mirrors the existing `suggestion-autocomplete_controller.js` (same debounce/abort pattern). The new controller is scoped to `<form class="sh-search">`.

**Alternatives considered**:
- UX Live Component — rejected (server round-trip per keystroke conflicts with debounce + abort requirement)
- Vanilla JS global script — rejected (no existing controller pattern in this project)

---

## 3. Search API endpoint design

**Decision**: Single GET endpoint `/api/search?q=:query` returning a JSON envelope with typed results.

**Rationale**: Mixed results (Books, Collections, Contributors) in one call reduces latency vs. three parallel requests. The 8-result cap is enforced server-side (FR-018). Response shape is a flat `results[]` array with a `type` discriminator field — allows the Stimulus controller to render each item variant without additional fetches.

**Endpoint**: `GET /api/search?q={query}` → `{"results": [...], "popular": [...]}`
- `results`: max 8 items (dynamic search by query)
- `popular`: max 4 items (pre-saisie "Souvent Consultés", fetched on panel open)

**Alternatives considered**:
- Separate `/api/search/books`, `/api/search/collections`, `/api/search/contributors` — rejected (3 fetches per keystroke vs. 1, no benefit for ≤8 results)
- GraphQL — rejected (no GraphQL in project, Constitution prohibits new frameworks)

---

## 4. ARIA combobox pattern

**Decision**: `role="combobox"` on `<input>`, `role="listbox"` on panel, `role="option"` on items, `aria-activedescendant` updated on ↑↓.

**Rationale**: FR-012 specifies this exact ARIA pattern. It matches WCAG 2.1 AA combobox pattern (APG). The focus stays in the input — keyboard navigation uses `aria-activedescendant` pointing to the highlighted option's `id`, not real DOM focus movement. This avoids losing typing context.

**Alternatives considered**:
- Roving tabindex — rejected (FR-012 explicitly requires `aria-activedescendant`)

---

## 5. Skeleton loading state

**Decision**: 3 skeleton `<div>` elements injected by the Stimulus controller, animated via CSS `@keyframes`.

**Rationale**: FR-020 requires skeleton placeholders reproducing result structure. CSS animation on existing design tokens (no new framework). 3 skeletons match a typical first-page response — enough to signal loading without false promise of result count.

**Alternatives considered**:
- Spinner — rejected (FR-020 explicitly requires skeleton placeholders)
- Turbo loading state — rejected (no Turbo frame wrapping the search panel)

---

## 6. Session history (Recherches Récentes)

**Decision**: JS array in Stimulus controller instance (`this._history = []`). FIFO, max 5, dedup-by-move-to-front.

**Rationale**: FR-023 + FR-003: session-only, no localStorage, no backend. Pure in-memory Stimulus state is the simplest correct implementation. History resets on page reload (expected behavior per spec).

**Alternatives considered**:
- SessionStorage — rejected (spec says "disparaît au rechargement", sessionStorage persists per tab — borderline compliant but inconsistent on tab reopen)
- Backend session — rejected (overkill, no spec requirement for server-side persistence)

---

## 7. CSS integration

**Decision**: Import `design/assets/search.css` into the main Webpack Encore `app.css` entry.

**Rationale**: The existing `app.scss` imports per-component CSS. `search.css` uses design tokens (`var(--*)`) already defined in `tokens.css`. No new framework or build tool change needed.

**Skeleton CSS**: Add `.search-skeleton` keyframe animation to `search.css` (or a new `assets/styles/components/search.css` that imports the design file).
