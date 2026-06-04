# UI Contract: Dashboard (018-home-dashboard)

## Route Contract

| Method | Path | Controller | Route name | Auth |
|--------|------|-----------|------------|------|
| `GET` | `/` | `DashboardController::home()` | `home` | `ROLE_USER` (redirects to `/connexion` if unauthenticated) |

**Template**: `templates/home/index.html.twig`

---

## Section Inventory

The dashboard renders 6 sections in order. Each section is independently wrapped in a `try/catch` inside `DashboardService`; a failed section shows an inline error block without blocking the others.

### 1 — Header (`#dashboard-header`)

| Data | Source | Format |
|------|--------|--------|
| Formatted date | `DashboardData::$formattedDate` | "MARDI 15 MAI" — uppercase, day zero-padded, no year |
| Greeting | `DashboardData::$greeting` | "SALUTATIONS, {PSEUDO}." — pseudo in uppercase |
| Contextual subtitle | `DashboardData::$headerSubtitle` | Standard: "[N] nouvelle(s) fiche(s) depuis ta dernière visite" / Mod: "[N] nouvelle(s) fiche(s) · [M] suggestion(s) en attente" / First visit: generic welcome |

### 2 — KPI Blocks (`#kpi-blocks`)

Three blocks, always visible:

| Block | Main value | Sub-title logic |
|-------|-----------|-----------------|
| "MA COLLECTION" | `$collectionCount` | "+{$collectionDelta} ce mois" (hidden if delta = 0) |
| "À LIRE" | `$toReadCount` | "{$toBuyCount} en chasse" (hidden if count = 0) |
| "MES SUGGESTIONS" | `$suggestionsTotal` | "{$suggestionsPending} en attente[ · {N} validée(s) aujourd'hui/hier]" — "validée(s)" part only shown when `$suggestionsValidatedRecently > 0` |

**Edge cases**:
- Collection = 0 → main value "0", no sub-title
- Suggestions total = 0 → "0", no sub-title
- Validated recently = 0 → sub-title shows pending count only

### 3 — Quick Access Grid (`#quick-access-grid`)

**Always present** (4 cards):

| Card label | Destination | Sub-title |
|-----------|-------------|-----------|
| "PARCOURIR LE WIKI" | `/catalogue` | "{$catalogueBookCount} FICHES · {$catalogueAuthorCount} AUTEURS" |
| "MA BIBLIOTHÈQUE" | `/ma-bibliotheque` | "{$libraryBookCount} LIVRES · {$libraryToReadCount} À LIRE" |
| "FAIRE UNE SUGGESTION" | `/suggestions/nouveau` | (featured card — distinct visual style) |
| "LISTE D'ACHATS" | `/liste-achats` | "{$wishlistCount} LIVRES EN CHASSE" |

**Conditional** (ROLE_MODERATOR or ROLE_ADMIN only — absent from DOM for others):

| Card label | Destination | Sub-title |
|-----------|-------------|-----------|
| "ÉDITER UNE FICHE" | `/suggestions` | "{$globalPendingSuggestions} EN ATTENTE" |

**Role check**: performed inside `DashboardService` (via `Security::isGranted()`), result surfaced as `DashboardData::$isModerator`. Twig uses `{% if dashboardData.isModerator %}` — no `is_granted()` call in template.

### 4 — Nouveautés (`#nouveautes`)

- Lists up to 5 most recently updated published books (`$recentBooks`)
- Each row: cover thumbnail (placeholder on error/absent), title (bold), author, year, catalogue reference, star rating (0–5, half-star resolution), relative timestamp
- Rating rendered via `rating_stars` Twig filter (new, see `RatingExtension`)
- Relative timestamp rendered via existing `timeago` pattern (or new Twig filter)
- "TOUT VOIR ->" links to `/catalogue`
- Edge case: fewer than 5 books → shows available count

### 5 — Activité (`#activite`)

- Lists up to 10 most recent `ActivityEvent` records (`$activityEvents`)
- Each row:
  - Avatar: circle with `$actorInitials` (or placeholder if null)
  - Phrase: generated in Twig based on `event.type` and whether `event.actorUser == app.user`
  - Optional status badge: `$statusBadge` (e.g., "● VALIDÉE")
  - Timestamp: relative
- Phrase templates (Twig logic):

| Type | 3rd person (default) | 2nd person (actorUser == currentUser AND type == MODERATION) |
|------|---------------------|-------------------------------------------------------------|
| SOCIAL | "@{pseudo} a noté **{bookTitle}**" | — |
| CONTRIBUTION | "@{pseudo} a publié **{bookTitle}**" | — |
| MODERATION | "@{pseudo} a modéré une suggestion sur **{bookTitle}**" | "Tu as validé/refusé la suggestion sur **{bookTitle}**" |
| PERSONAL | "@{pseudo} a ajouté **{bookTitle}** à sa liste d'achats" | — |

- "MON FIL ->" links to `/activite` (future route placeholder — link present, route TBD)
- Edge case: empty feed → "Pas encore d'activité communautaire."

### 6 — Forum Banner (`#forum-banner`)

- Static block: "REJOINDRE LA TAVERNE DES AVENTURIERS"
- "Y aller ->" button links to forum URL (configured in `services.yaml` as `%app.forum_url%` parameter)

---

## Accessibility Contract (SC-008 — WCAG 2.1 AA)

- All cover images: `alt="{book.title} — couverture"` (placeholder: `alt="Couverture non disponible"`)
- All interactive cards: `<a>` elements with descriptive `aria-label`
- Star rating: `aria-label="{N}/5 étoiles"` on the rating container
- Keyboard navigation: all cards and links reachable via Tab
- Text contrast: rely on existing Bootstrap theme tokens (validated in design phase)
- Non-semantic components (avatar circles, badge chips): `role="img"` with `aria-label` if not wrapped in text

---

## Responsive / Mobile-First Contract (FR-021)

Breakpoint adaptations are defined in the design phase. The implementation MUST follow existing Bootstrap breakpoint conventions already used in the project. No new CSS framework is introduced.

Grid expectations:
- Mobile (`< md`): KPI blocks stack vertically; quick-access grid in 2×2 (or 2×3 with moderation card); nouveautés in single column
- Tablet (`md`): KPI blocks in row of 3; quick-access in 2 cols
- Desktop (`lg+`): KPI blocks in row of 3; quick-access in 4 cols (+ moderation); nouveautés in single column (horizontal card layout)
