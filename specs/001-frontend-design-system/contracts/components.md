# UI Component Contracts

**Branch**: `001-frontend-design-system` | **Date**: 2026-05-23

These contracts define the public interface for each Twig component: how to invoke it, what it renders, and what guarantees it makes. Implementations MUST satisfy every guarantee listed.

---

## Book:Card

**Usage**:
```twig
<twig:Book:Card
    title="Le Seigneur des Anneaux"
    coverUrl="/images/covers/lotr.jpg"
    author="J.R.R. Tolkien"
    :rating="4.5"
    :bookId="42"
/>
```

**Guarantees**:
- Renders a card element whose layout, typography, and colors match the Claude Design System book card specification (FR-007)
- When `title` is null or absent: displays the string `"Sans titre"` in the title position (FR-007)
- When `coverUrl` is null or absent: displays the placeholder graphic at `/images/placeholder-cover.svg` in the image position (FR-007)
- When `author` is null or absent: displays `"Auteur inconnu"` in the author position
- When `rating` is provided: renders a `Rating` subcomponent inside the card
- All text elements use semantic HTML (heading for title, appropriate element for author/rating)
- Card is keyboard-accessible if it contains an interactive element

**PHPUnit test surface** (`Book:Card`):
- Default props: renders "Sans titre", placeholder cover, "Auteur inconnu"
- Full props: renders provided values verbatim
- Rating presence: renders `Rating` subcomponent when `rating` provided, absent when not

---

## Author:Card

**Usage**:
```twig
<twig:Author:Card
    name="J.R.R. Tolkien"
    avatarUrl="/images/avatars/tolkien.jpg"
    :bookCount="12"
/>
```

**Guarantees**:
- Renders correctly styled per the Claude Design System author card specification (FR-008)
- When `name` is null or absent: displays `"Auteur inconnu"`
- When `avatarUrl` is null or absent: displays placeholder at `/images/placeholder-avatar.svg`
- `bookCount` is optional; renders if provided, absent if not

---

## Badge

**Usage**:
```twig
<twig:Badge label="Fantaisie" variant="primary" />
<twig:Badge label="Nouveau" variant="success" />
```

**Guarantees**:
- Renders a badge matching Claude Design System badge specification (FR-009)
- `variant` maps to a Bootstrap color context; unrecognized variants fall back to `primary`
- Uses `<span>` with appropriate ARIA role for non-interactive badges

---

## Rating

**Usage**:
```twig
<twig:Rating :value="4.5" :max="5" />
```

**Guarantees**:
- Renders a rating display matching Claude Design System rating specification (FR-009)
- `value` is clamped to `[0, max]`
- Renders a visually distinct state for each integer and half-integer step
- Includes `aria-label` with numeric value for screen reader accessibility (NFR-001)

---

## Modal

**Usage**:
```twig
<twig:Modal id="confirm-delete" title="Confirmer la suppression" size="md">
    {% block modal_body %}
        Êtes-vous sûr de vouloir supprimer cet élément ?
    {% endblock %}
    {% block modal_footer %}
        <button type="button" data-action="click->modal#close">Annuler</button>
        <button type="button" class="btn btn-danger">Supprimer</button>
    {% endblock %}
</twig:Modal>
```

**Guarantees**:
- Hidden by default (`is-open` CSS class absent on mount)
- Opens when `modal#open` action is dispatched
- Closes when `modal#close` action is dispatched, backdrop is clicked, or Escape key is pressed
- `document.body` scroll is locked while modal is open
- `role="dialog"`, `aria-modal="true"`, `aria-labelledby` pointing to title element (NFR-001)
- Does NOT use Bootstrap's JS modal (uses custom Stimulus `modal` controller to avoid focus-trap conflict)
- Traps focus within the modal while open; returns focus to trigger element on close
- Matches Claude Design System modal specification (FR-010)

---

## Toast

**Usage**:
```twig
{# Rendered automatically by Layout:FlashBag from session flash bag #}
{# Or manually: #}
<twig:Toast message="Livre ajouté à votre collection." type="success" :autoDismissMs="5000" />
```

**Guarantees**:
- Renders with visual style corresponding to `type`: green for `success`, red for `error`, yellow for `warning`, blue for `info` (FR-006, FR-010)
- Auto-dismisses after `autoDismissMs` milliseconds (default 5000) via Stimulus `toast` controller
- Provides a close button that dismisses immediately (FR-010)
- Notification height expands to accommodate long messages; no text truncation
- `role="alert"` for success/info; `role="alert" aria-live="assertive"` for error/warning (NFR-001)
- Matches Claude Design System toast/notification specification (FR-010)

---

## Layout:Navbar

**Usage**: Rendered unconditionally in `base.html.twig`. Not called directly by page templates.

**Guarantees**:
- Displays "La Collection" application name and primary nav links: Accueil, Catalogue, Suggestions (desktop) (FR-001)
- On desktop (`≥ md` breakpoint): standard horizontal navbar
- On mobile (`< md` breakpoint): top bar with logo + notification icon; fixed bottom nav with Accueil, Catalogue, Suggestions, Profile (FR-001)
- Active link state reflected when `currentRoute` prop matches link route name
- Uses `<nav>` element with `role="navigation"` and `aria-label="Navigation principale"` (NFR-001, NFR-002)
- Keyboard navigable; all links reachable via Tab; focus indicators visible (NFR-001)
- Navbar collapse (mobile toggler) uses Bootstrap native `data-bs-toggle="collapse"` with `aria-expanded` state management

---

## Layout:Footer

**Usage**: Rendered unconditionally in `base.html.twig`. Not called directly by page templates.

**Guarantees**:
- Visible on desktop (`d-none d-md-block`); hidden on mobile where bottom nav replaces it (FR-002)
- Uses `<footer>` element (NFR-002)
- Contains consistent branding matching Claude Design System footer specification
- Color contrast of all footer text ≥ 4.5:1 against footer background (NFR-001)

---

## Layout:FlashBag

**Usage**: Rendered unconditionally in `base.html.twig`. Reads session flash bag via `app.flashes()`.

**Guarantees**:
- Reads all flash messages from the Symfony session flash bag
- Renders one `Toast` component per flash message, passing `type` and `message` props
- Renders no markup when the flash bag is empty
- Container element has Stimulus `data-controller="toast-container"` applied
- Toast stack: maximum 3 visible; newest prepended; oldest removed when limit exceeded (FR-010)
