# Contract: BookLibraryActionsComponent (Live Component)

## Class

`App\Twig\Components\Book\LibraryActionsComponent`

## Twig Template

`templates/components/Book/LibraryActionsComponent.html.twig`

## Usage in show.html.twig

```twig
{% if app.user %}
    <twig:Book:LibraryActionsComponent :book="book" />
{% endif %}
```

## Props (LiveProp)

| Prop | Type | Writable | Description |
|------|------|----------|-------------|
| `book` | `Book` | no | The book entity; passed from the parent Twig template |

## Computed State (getters, not stored)

| Getter | Returns | Description |
|--------|---------|-------------|
| `isOwned()` | `bool` | True if user has `UserBook.isOwned = true` for this book |
| `isToRead()` | `bool` | True if user has `UserBook.isToRead = true` |
| `isToBuy()` | `bool` | True if user has `UserBook.isToBuy = true` |
| `isFavorite()` | `bool` | True if user has `UserBook.isFavorite = true` |

These are computed on every render from the DB state via `UserBookRepository::findByUserAndBook()`. Not stored as `#[LiveProp]` — this guarantees rollback-on-error (FR-009).

## Actions (LiveAction)

All actions require `ROLE_USER`. Each dispatches a `toast` browser event on success or error.

| Action | Triggers | Auto-coherence side-effect |
|--------|----------|---------------------------|
| `toggleOwned()` | Click "Ma Collection" | If activating: `isToBuy = false` |
| `toggleToRead()` | Click "À lire" | None |
| `toggleToBuy()` | Click "À acheter" | If activating: `isOwned = false` |
| `toggleFavorite()` | Click "Favori" | None |

## Toast Events Dispatched

```php
// Success:
$this->dispatchBrowserEvent('toast', [
    'message' => 'Ajouté à votre collection',  // varies per action + direction
    'type'    => 'success',
]);

// Error:
$this->dispatchBrowserEvent('toast', [
    'message' => 'Une erreur est survenue. Veuillez réessayer.',
    'type'    => 'error',
]);
```

## Toast Messages Matrix

| Action | Direction | Message |
|--------|-----------|---------|
| toggleOwned | add | "Ajouté à votre collection" |
| toggleOwned | remove | "Retiré de votre collection" |
| toggleToRead | add | "Ajouté à la liste À lire" |
| toggleToRead | remove | "Retiré de la liste À lire" |
| toggleToBuy | add | "Ajouté à la liste À acheter" |
| toggleToBuy | remove | "Retiré de la liste À acheter" |
| toggleFavorite | add | "Ajouté à vos favoris" |
| toggleFavorite | remove | "Retiré de vos favoris" |

## Template Contract

Button active state: `class="action-toggle{% if isOwned %} is-active{% endif %}"` (per button).

Button disabled state during request: Handled automatically by Symfony UX (adds `data-loading` attributes).

```twig
<div class="actions-grid" role="group" aria-label="Listes personnelles">
    <button class="action-toggle{% if isOwned %} is-active{% endif %}"
            data-action="live#action"
            data-live-action-param="toggleOwned"
            type="button">
        <!-- SVG icon -->
        Ma Collection
    </button>
    <!-- repeat for À lire, À acheter, Favori -->
</div>
```

## Security

- Component mounted only inside `{% if app.user %}` in the parent template (visibility guard)
- Each `#[LiveAction]` carries `#[IsGranted('ROLE_USER')]` (mutation guard)
- CSRF: Symfony UX Live Component includes a CSRF token in every action request automatically. This protection applies only to requests sent through the Live Component mechanism; direct HTTP calls bypassing the component receive no CSRF token but are rejected by `#[IsGranted]` regardless.
- Unauthenticated direct HTTP calls → 302 redirect to login (or 401 for API clients); authenticated calls without ROLE_USER → 403
- Session expiry during action: Symfony UX receives a 302 redirect response; browser follows redirect to login page. No toast is dispatched from the component (no component re-render occurs on redirect). Actual behavior is a full-page redirect to login — spec Edge Case note about a toast for session expiry is aspirational and not achievable via `dispatchBrowserEvent` in this scenario.

## Active Button Visual State

`is-active` CSS class is used to represent the active state per button (see Template Contract). Visual appearance is delegated to the project's existing CSS design system — no new visual spec required; the class already exists.
