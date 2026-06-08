# Data Model: Page Mentions Légales

**Branch**: `021-mentions-legales-page`

## Overview

This feature introduces no new database entities. All data is static (hardcoded in the Twig template) with one externalized configuration parameter for the last-updated date.

---

## LegalPageConfig

**Type**: Symfony parameter (not a Doctrine entity)
**Location**: `config/services.yaml`

| Parameter | Type | Example | Notes |
|---|---|---|---|
| `app.legal.last_updated` | `string` | `"3 juin 2026"` | Pre-formatted French date string. If absent or empty, the template outputs nothing for the date field. |

**Controller injection**: The `LegalController` receives the parameter as a constructor argument `$legalLastUpdated` (Symfony DI via `bind` or explicit wiring in `services.yaml`). It passes it to the template as `lastUpdated`.

---

## Section

**Type**: Template concept (no database backing)

Each section is a `<section class="doc-section" id="...">` element in the Twig template.

| Field | Description |
|---|---|
| `id` | Anchor slug used by TOC links (e.g., `editeur`, `publication`, `hebergeur`) |
| `number` | Two-digit display number (e.g., `01`, `02`) |
| `title` | Section heading text in French |
| `body` | HTML content (paragraphs, IdCard blocks, Callout blocks) |

**Sections** (9 total):
1. `#editeur` — Éditeur du site
2. `#publication` — Direction de la publication
3. `#hebergeur` — Hébergement
4. `#nature` — Nature du projet
5. `#propriete` — Propriété intellectuelle
6. `#contributions` — Contributions des membres
7. `#donnees` — Données personnelles
8. `#responsabilite` — Limitation de responsabilité
9. `#contact` — Contact

---

## IdCard (TableauKeyValue partial)

**Type**: Twig include partial
**File**: `templates/legal/_id_card.html.twig`
**Usage**: `{% include 'legal/_id_card.html.twig' with { rows: [...] } %}`

| Variable | Type | Description |
|---|---|---|
| `rows` | `array<{key: string, value: string}>` | List of key-value pairs to display |

Rendered as a `<div class="id-card">` block with one `<div class="id-row">` per pair.

---

## Callout (AlertBlock partial)

**Type**: Twig include partial
**File**: `templates/legal/_callout.html.twig`
**Usage**: `{% include 'legal/_callout.html.twig' with { content: '...' } %}`

| Variable | Type | Description |
|---|---|---|
| `content` | `string` | Raw HTML text content of the callout body |

Rendered as a `<div class="callout">` with a decorative triangle SVG icon (`aria-hidden="true"`) and the content in a `<p>` tag.
