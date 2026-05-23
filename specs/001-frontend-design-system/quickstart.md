# Quickstart: Creating a New Page

**Branch**: `001-frontend-design-system` | **Date**: 2026-05-23

How to create a fully styled new application page using the design system. Target: under 30 minutes with no custom styling required (SC-002).

---

## 1. Create the Twig template

```twig
{# templates/catalogue/index.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Catalogue — La Collection{% endblock %}

{% block body %}
<div class="container py-4">
    <h1>Catalogue</h1>

    <div class="row g-4">
        {% for book in books %}
            <div class="col-sm-6 col-md-4 col-lg-3">
                <twig:Book:Card
                    :title="book.title"
                    :coverUrl="book.coverUrl"
                    :author="book.author.name"
                    :rating="book.averageRating"
                    :bookId="book.id"
                />
            </div>
        {% endfor %}
    </div>
</div>
{% endblock %}
```

That's all that's required. The page automatically inherits:
- Navbar (desktop + mobile)
- Footer (desktop)
- Flash notification zone
- All design tokens (colors, typography, spacing)
- Compiled CSS + JS assets

---

## 2. Create the controller

```php
// src/Controller/CatalogueController.php
#[Route('/catalogue', name: 'catalogue_index')]
public function index(): Response
{
    return $this->render('catalogue/index.html.twig', [
        'books' => [], // your data here
    ]);
}
```

---

## 3. Trigger a flash notification

```php
// In any controller action:
$this->addFlash('success', 'Livre ajouté à votre collection.');
return $this->redirectToRoute('catalogue_index');
```

The flash is automatically rendered on the next page load as a styled, auto-dismissing toast notification.

---

## 4. Use design tokens in custom CSS (if needed)

Tokens are available as CSS custom properties:

```scss
// In a new assets/styles/components/_my-component.scss
.my-component {
    color: var(--bs-primary);
    font-size: var(--bs-body-font-size);
    padding: var(--bs-spacer);
    border-radius: var(--bs-border-radius);
}
```

Import this file in `assets/styles/app.scss`:
```scss
@import "components/my-component";
```

---

## 5. Add interactivity with Stimulus (if needed)

```js
// assets/controllers/my_feature_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        // runs when element mounts
    }
}
```

In your Twig template:
```twig
<div {{ stimulus_controller('my-feature') }}>
    ...
</div>
```

The controller is auto-discovered by `startStimulusApp()` — no registration needed.

---

## Available Components Reference

| Component | Usage |
|-----------|-------|
| `<twig:Book:Card />` | Book card with cover, title, author, rating |
| `<twig:Author:Card />` | Author card with avatar, name, book count |
| `<twig:Badge />` | Colored badge/tag |
| `<twig:Rating />` | Star/numeric rating display |
| `<twig:Modal />` | Dialog modal with open/close via Stimulus |
| `<twig:Toast />` | Inline toast (prefer flash bag for page-level feedback) |

See [contracts/components.md](contracts/components.md) for full prop reference.

---

## Design System Reference

All token values are in `design/assets/tokens.css`. Bootstrap SCSS variable mapping is in `design/pages/01-couleurs.html` §1.3. Component HTML patterns are in `design/pages/05-cards.html`, `07-navigation.html`, `08-feedback.html`.
