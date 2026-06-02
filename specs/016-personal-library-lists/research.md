# Research: Personal Library Lists

## Decision 1 — UserBook Schema Migration

**Decision**: Replace the `status` enum column with 3 new boolean columns (`is_owned`, `is_to_read`, `is_to_buy`). Keep `is_favorite` (already exists). Delete `UserBookStatus` enum class.

**Rationale**: The current enum is mutually exclusive — a book can only have one status. The spec requires independent, coexisting statuses (e.g., owned AND reading-list simultaneously). Four independent booleans represent this cleanly. A record with all 4 booleans false is deleted (absence = no statuses).

**Migration strategy**:
- `DANS_MA_COLLECTION` → `is_owned = true`
- `A_ACHETER` → `is_to_buy = true`
- `A_LIRE` → `is_to_read = true`
- `LU` → DELETE (clean start, confirmed in spec clarifications)
- `PAS_DANS_MA_COLLECTION` → DELETE (clean start)

**Alternatives considered**:
- Keep enum + add booleans: Redundant columns, complex invariants to maintain
- Separate UserBookList join table per list type: Over-engineered, 4× more rows, complex queries

---

## Decision 2 — Live Component Architecture

**Decision**: New `LibraryActionsComponent` in `src/Twig/Components/Book/`, extending `AbstractController`, with single `#[LiveProp]` `Book $book`. Four `#[LiveAction]` methods: `toggleOwned()`, `toggleToRead()`, `toggleToBuy()`, `toggleFavorite()`. Uses `ComponentToolsTrait` for `dispatchBrowserEvent`.

**Rationale**: Matches the existing project pattern (see `FilterPanelComponent`). Scope is minimal — only the 4 buttons re-render on each action. No endpoint REST needed; Symfony UX handles CSRF automatically. `AbstractController` gives access to `getUser()` and `denyAccessUnlessGranted()`.

**Alternatives considered**:
- Custom REST endpoints (POST /livres/{slug}/toggle): Would need explicit CSRF tokens, custom JS, more infrastructure
- Turbo Streams: Overkill for 4 button states; Live Component re-render is simpler

**Security**: Each `#[LiveAction]` carries `#[IsGranted('ROLE_USER')]`. If the user is not authenticated, Symfony returns a 403/redirect before the method body executes.

---

## Decision 3 — Toast System Integration

**Decision**: Use `$this->dispatchBrowserEvent('toast', ['message' => '...', 'type' => 'success'])` in the component (via `ComponentToolsTrait`). Enhance `toast-container_controller.js` to listen for the `toast` custom event on `document` and dynamically inject a toast element into the `toast-rail`.

**Rationale**: The existing `toast-container_controller.js` only handles overflow. The `ComponentToolsTrait::dispatchBrowserEvent()` dispatches a native browser CustomEvent on the component's DOM element — the event bubbles to `document`. Adding a document-level listener to the container controller bridges these two systems without touching the Flash/Twig pipeline.

**Toast event contract**:
```js
// Event dispatched by Live Component:
// CustomEvent('toast', { detail: { message: '...', type: 'success'|'error'|'info' }, bubbles: true })
```

**Injected HTML structure** (matches existing `templates/components/Toast.html.twig`):
```html
<div data-controller="toast" data-toast-auto-dismiss-ms-value="5000" class="toast toast--{type}" role="status">
  <button class="toast-close" data-action="toast#close" type="button" aria-label="Fermer">...</button>
  <span class="toast-type-label">{type}</span>
  <span class="toast-message">{message}</span>
</div>
```

**Alternatives considered**:
- Flash messages + redirect: Would require page reload, violating FR-002
- `$this->emit()` (Live Component events): Only works between components, not with Stimulus controllers
- Separate AJAX toast endpoint: Extra round-trip, more JS, no benefit

---

## Decision 4 — UserBookService Business Logic

**Decision**: Service class `App\Service\UserBookService` with 4 public methods: `toggleOwned()`, `toggleToRead()`, `toggleToBuy()`, `toggleFavorite()`. Each method:
1. Fetches or creates the `UserBook` record for the (user, book) pair
2. Toggles the relevant boolean
3. Applies auto-coherence: `toggleOwned(true)` sets `isToBuy = false`; `toggleToBuy(true)` sets `isOwned = false`
4. If all 4 booleans are false after toggle → deletes the record
5. Persists and flushes

**Rationale**: Encapsulates all business logic outside the component (Constitution II). The component becomes a thin delegator. The service is easily unit-testable.

**Return type**: Each toggle method returns a `UserBookToggleResult` DTO or simple array `['status' => bool, 'affected' => string[]]` for toast message construction. Simple array chosen to avoid adding a new DTO file for trivial data.

**Alternatives considered**:
- Logic in entity methods (domain model): Would put persistence responsibility in entity; Doctrine discourages entity-managed EM
- Logic in Live Component: Violates Constitution II

---

## Decision 5 — Double-click Protection (FR-008)

**Decision**: The Live Component automatically disables re-entrant actions during an in-flight request via Symfony UX's built-in action debouncing. No extra JS needed. Idempotence is guaranteed at the service level (toggle is always deterministic from current DB state).

**Rationale**: Symfony UX Live Component queues or drops duplicate action calls on the same component while a request is in flight. This satisfies FR-008 without additional code.

---

## Decision 6 — Error Rollback (FR-009)

**Decision**: Symfony UX Live Component automatically reverts optimistic UI state if the server action throws an exception. No explicit rollback code needed in the component — the server-side state (the `#[LiveProp]` computed state) is always authoritative. A failed action triggers an error re-render from the last known good state.

**Rationale**: The `is-active` state of buttons is computed from DB state on each render, not stored as a writable `#[LiveProp]`. So any server error leaves the DB unchanged and the next render shows the correct pre-action state.
