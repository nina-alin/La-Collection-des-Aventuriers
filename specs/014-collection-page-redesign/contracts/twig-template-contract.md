# Twig Template Contract: `collection/show.html.twig`

## Variables injected by `CollectionController::show()`

| Variable | Type | Description |
|----------|------|-------------|
| `collection` | `Collection` | The collection entity |
| `books` | `Paginator<Book>` | Current page's books, sorted by volumeNumber ASC |
| `currentPage` | `int` | Current page number (≥ 1) |
| `totalPages` | `int` | Total pages (≥ 1) |
| `totalBooks` | `int` | Total book count across all pages |
| `heroMeta` | `HeroMeta` | yearMin, yearMax, averageRating |
| `recurringContributors` | `RecurringContributorsResult` | uniqueCount + pills[] |
| `publishingHistory` | `CollectionPublishingHistory[]` | Sorted by startYear ASC, id ASC |

## Sections Rendered

### 1. Hero (`.coll-hero`)

- Emblem: `collection.imageLogo` → `asset('uploads/collections/' ~ logo)` or placeholder SVG
- Title: `collection.nom`
- V.O.: `collection.nomOriginal` (conditional, with "V.O." label)
- Meta pills: total tomes (`totalBooks`), year range, avg rating, status (`collection.statut.value`)
- "Ajouter aux favoris": button, no action, `aria-pressed="false"`
- "+ Suggérer un tome manquant": `<a href="#">`

### 2. Completion Section (`.progress-panel`)

- **Static values**: 42,8 %, 12/28, 28 ticks (12 colored)
- Dynamic: `{{ collection.nom }}` in body text only
- ARIA: `progressbar` with `aria-valuemin="0" aria-valuemax="28" aria-valuenow="12"`

### 3. Books Grid (`.tomes-section`)

- Header: "LES TOMES" + `totalBooks ~ " VOLUMES"`
- Filter pills: "Tous" (active, `data-kind="all"`), "Possédés" (`disabled`, `data-kind="owned"`), "Manquants" (`disabled`, `data-kind="missing"`)
- Sort control (`.seg`): "Numéro" (`aria-pressed="true"`) + "Note"
- Stimulus: `data-controller="collection-sort"` on `.tomes-grid`
- Each `.tome` element:
  - `data-hue="<hue>"` — 6-value palette
  - `data-volume="<n>"` — for sort (0 if null)
  - `data-rating="<f|>"` — for sort (empty string if no reviews)
  - Status indicator: always `?` (unknown) static
  - Body meta: ref code (`volumeNumber` + frenchPublicationYear), paragraphs, title, rating, author names

### 4. Publishing History (`.panel`) — conditional

- Rendered only if `publishingHistory|length > 1`
- Timeline rows: period, editor tag (or "(éditeur inconnu)" if null), editionName, details

### 5. Contributors (`.panel`)

- Count: `recurringContributors.uniqueCount ~ " CONTRIBUTEURS"`
- Pills: all pills, sorted by count DESC, each showing initials, name, role label, count badge

## Stimulus Controller Contract

**Name**: `collection-sort`
**File**: `assets/controllers/collection-sort_controller.js`

| Action | Trigger | Behavior |
|--------|---------|----------|
| `sortByVolume` | click on "Numéro" button | Stable sort `.tome` elements by `data-volume` ASC |
| `sortByRating` | click on "Note" button | Stable sort `.tome` elements by `data-rating` DESC (empty string treated as -Infinity) |

**Targets**: `grid` (the `.tomes-grid` element)

**Values**: none needed (sort key determined by action method called)
