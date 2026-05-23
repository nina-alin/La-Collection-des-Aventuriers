# Data Model: Frontend Design System Foundation

**Branch**: `001-frontend-design-system` | **Date**: 2026-05-23

This feature is frontend infrastructure — no database entities. The "entities" below are the PHP value objects and component classes that model the design system's concepts in code.

---

## Entity 1: DesignToken (SCSS variable / CSS custom property)

**What it is**: A named, globally accessible design value from the Claude Design System. Exists as an SCSS variable in `assets/styles/tokens/` and (after Bootstrap's `_root.scss` processes it) as a CSS custom property in `:root`.

**Fields**:
| Field | Type | Notes |
|-------|------|-------|
| `name` | string | SCSS variable: `$primary`; CSS custom property: `--bs-primary` |
| `value` | string | Exact value from Claude Design System (hex, rem, px, etc.) |
| `category` | enum | `color` \| `typography` \| `spacing` \| `radius` \| `effect` |

**Categories → files**:
| Category | SCSS file |
|----------|-----------|
| color | `assets/styles/tokens/_colors.scss` |
| typography | `assets/styles/tokens/_typography.scss` |
| spacing / radius | `assets/styles/tokens/_spacing.scss` |
| effect (shadow, transition) | `assets/styles/tokens/_effects.scss` |

**Validation**: All values must exactly match the fetched Claude Design System document (SC-001, FR-012). No invented or approximated values permitted.

**State transitions**: N/A (compile-time constants)

---

## Entity 2: BaseLayout (Twig template)

**What it is**: The shared page structure inherited by all application pages. Implemented as `templates/base.html.twig`.

**Fields** (Twig blocks):
| Block name | Required | Notes |
|-----------|----------|-------|
| `title` | Yes | `<title>` content; each page overrides |
| `body` | Yes | Main page content (`<main>`) |
| `stylesheets` | No | Per-page additional CSS |
| `javascripts` | No | Per-page additional JS |

**Fixed regions** (not overridable, rendered unconditionally):
| Region | Component | Visibility |
|--------|-----------|------------|
| Top navigation | `Layout:Navbar` | Always visible |
| Flash notification zone | `Layout:FlashBag` | Always visible; hidden when empty |
| Footer | `Layout:Footer` | Desktop only (`d-none d-md-block`) |
| Mobile bottom nav | Inside `Layout:Navbar` | Mobile only (`d-block d-md-none`) |

**Inheritance rule**: All application page templates MUST use `{% extends 'base.html.twig' %}`. No other base template exists.

---

## Entity 3: UIComponent (PHP class + Twig template pair)

**What it is**: A reusable, self-contained visual building block. Implemented as a Symfony UX Twig Component (typed PHP class + Twig template).

**All components**:
| Component name | PHP class | Template | Category |
|---------------|-----------|----------|----------|
| `Layout:Navbar` | `src/Twig/Components/Layout/Navbar.php` | `templates/components/Layout/Navbar.html.twig` | Layout |
| `Layout:Footer` | `src/Twig/Components/Layout/Footer.php` | `templates/components/Layout/Footer.html.twig` | Layout |
| `Layout:FlashBag` | `src/Twig/Components/Layout/FlashBag.php` | `templates/components/Layout/FlashBag.html.twig` | Layout |
| `Book:Card` | `src/Twig/Components/Book/Card.php` | `templates/components/Book/Card.html.twig` | Content |
| `Author:Card` | `src/Twig/Components/Author/Card.php` | `templates/components/Author/Card.html.twig` | Content |
| `Badge` | `src/Twig/Components/Badge.php` | `templates/components/Badge.html.twig` | Display |
| `Rating` | `src/Twig/Components/Rating.php` | `templates/components/Rating.html.twig` | Display |
| `Modal` | `src/Twig/Components/Modal.php` | `templates/components/Modal.html.twig` | Overlay |
| `Toast` | `src/Twig/Components/Toast.php` | `templates/components/Toast.html.twig` | Feedback |

**Component props**:

### Layout:Navbar
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| `currentRoute` | string\|null | No | null | Highlights active nav link |

### Layout:Footer
No props — static content.

### Layout:FlashBag
No props — reads `app.flashes()` from Twig global.

### Book:Card
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| `title` | string\|null | No | `'Sans titre'` | Fallback applied in `#[PostMount]` |
| `coverUrl` | string\|null | No | `'/images/placeholder-cover.svg'` | Fallback applied in `#[PostMount]` |
| `author` | string\|null | No | `'Auteur inconnu'` | Fallback applied in `#[PostMount]` |
| `rating` | float\|null | No | null | If provided, renders `Rating` subcomponent |
| `bookId` | int\|null | No | null | For link generation |

### Author:Card
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| `name` | string\|null | No | `'Auteur inconnu'` | Fallback applied in `#[PostMount]` |
| `avatarUrl` | string\|null | No | `'/images/placeholder-avatar.svg'` | Fallback applied in `#[PostMount]` |
| `bookCount` | int\|null | No | null | Optional secondary info |

### Badge
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| `label` | string | Yes | — | Badge text |
| `variant` | string | No | `'primary'` | Maps to Bootstrap color variant |

### Rating
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| `value` | float | Yes | — | 0.0–5.0 |
| `max` | int | No | `5` | Maximum rating value |

### Modal
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| `id` | string | Yes | — | HTML id for ARIA and JS targeting |
| `title` | string | Yes | — | Modal header title |
| `size` | string | No | `'md'` | `sm` \| `md` \| `lg` \| `xl` |

### Toast
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| `message` | string | Yes | — | Notification text |
| `type` | string | No | `'info'` | `success` \| `error` \| `warning` \| `info` |
| `autoDismissMs` | int | No | `5000` | Auto-dismiss timeout in milliseconds |

---

## Entity 4: FlashNotification (runtime value)

**What it is**: A transient system message tied to a user action. Stored in the Symfony session flash bag (`RequestStack`). Rendered by `Layout:FlashBag` into `Toast` components.

**Fields**:
| Field | Type | Validation |
|-------|------|-----------|
| `type` | enum | `success` \| `error` \| `warning` \| `info` |
| `message` | string | Any length; notification height expands, no truncation |

**Type → visual style mapping**:
| Type | Bootstrap context | Primary color |
|------|------------------|--------------|
| `success` | `success` | Green (from Design System) |
| `error` | `danger` | Red (from Design System) |
| `warning` | `warning` | Yellow/amber (from Design System) |
| `info` | `info` | Blue (from Design System) |

**State transitions**:
1. `STORED` — flash added to session bag via `addFlash()`
2. `RENDERED` — `FlashBag` component reads and renders on next request; flash consumed from session (one-time display)
3. `AUTO_DISMISSED` — after 5000ms (Stimulus `toast` controller `setTimeout`)
4. `MANUALLY_DISMISSED` — user clicks close button (Stimulus `toast#dismiss` action)

**Stack invariant**: Maximum 3 toasts visible simultaneously. When a 4th arrives, the oldest (`lastElementChild` of the container) is removed by the `toast-container` Stimulus controller.
