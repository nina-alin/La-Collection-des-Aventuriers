# Contract: profile-menu Stimulus Controller

## Identifier

`profile-menu` → `assets/controllers/profile_menu_controller.js`

## Values

| Name | Type | Default | Purpose |
|---|---|---------|---|
| `open` | Boolean | `false` | Tracks open/closed state; drives ARIA and CSS classes |

## Targets

| Name | Element | Purpose |
|---|---|---|
| `trigger` | `<button>` | Avatar button — receives `aria-expanded` updates and focus-restore |
| `card` | `.menu-card` | Menu panel — receives `is-open` class, touch event listeners |
| `backdrop` | `.menu-backdrop` | Overlay — receives `is-open` class |

## Actions

| Event | Method | Trigger |
|---|---|---|
| `click` on trigger | `toggle()` | Opens or closes the menu |
| `click` on backdrop | `close()` | Closes the menu |
| `keydown` on document | `onKeydown(e)` | Escape → close; Tab/Arrows → focus trap |

## Methods

### `toggle()`
If closed → `open()`; if open → `close()`.

### `open()`
1. Set `openValue = true`
2. Add `is-open` to card and backdrop
3. Set `triggerTarget.ariaExpanded = 'true'`
4. Build `focusableElements` list from card; focus first item
5. Bind `touchstart`/`touchmove`/`touchend` on card for swipe detection

### `close()`
1. Set `openValue = false`
2. Remove `is-open` from card and backdrop
3. Set `triggerTarget.ariaExpanded = 'false'`
4. Restore focus to `triggerTarget`
5. Unbind touch listeners

### `onKeydown(e)`
- `Escape` → `close()`
- `Tab` (no Shift) → advance focus within `focusableElements`, wrap around; `e.preventDefault()`
- `Shift+Tab` → retreat focus within `focusableElements`, wrap around; `e.preventDefault()`
- `ArrowDown` → advance focus among `role="menuitem"` elements
- `ArrowUp` → retreat focus among `role="menuitem"` elements

### Swipe detection (touch events on card)
- `touchstart`: store `startY = e.touches[0].clientY`
- `touchmove`: no-op (just track)
- `touchend`: if `(e.changedTouches[0].clientY - startY) > 80` → `close()`

## CSS classes toggled

| Class | Element | When |
|---|---|---|
| `is-open` | `.menu-card` | Menu open |
| `is-open` | `.menu-backdrop` | Menu open |

## openValueChanged callback

```js
openValueChanged(isOpen) {
  this.cardTarget.classList.toggle('is-open', isOpen);
  this.backdropTarget.classList.toggle('is-open', isOpen);
  this.triggerTarget.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
}
```
