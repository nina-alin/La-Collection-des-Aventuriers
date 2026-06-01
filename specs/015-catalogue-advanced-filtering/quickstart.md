# Quickstart: Catalogue Page & Advanced Filtering

**Branch**: `015-catalogue-advanced-filtering`

---

## What this feature delivers

- `/catalogue` → fully functional catalogue page matching `design/pages/catalogue.html`
- Two-column layout (desktop ≥ 880 px): collapsible filter rail left, results grid right
- Mobile (≤ 879 px): FAB "FILTRER & TRIER" (with active-filter badge) → full-screen filter modal
- Filter panel: sort, editor multi-select with server-side search, paragraph range slider + preset pills, collection status (authenticated users only)
- Results toolbar: result count, active filter chips (×-removable), sort dropdown (immediate), grid/list toggle
- In-page search bar: autocomplete dropdown grouped by books/authors (1-char minimum)
- Pagination: 24 books per page, deep-linkable via URL query params
- Book card marks: owned (green ✓), favourite (red ♥), wishlist (amber 🛒) — combinable

---

## Developer setup

1. Run the migration:
   ```bash
   php bin/console doctrine:migrations:migrate
   ```
   Creates the `user_book` table.

2. Load fixtures (append-safe):
   ```bash
   php bin/console doctrine:fixtures:load --append
   ```
   Adds sample `UserBook` entries for dev books.

3. Visit the catalogue:
   ```
   http://localhost/catalogue
   ```

---

## Key files

| File | Role |
|------|------|
| `src/Controller/CatalogueController.php` | Route handler; hydrates `ActiveFilterState` from URL params; passes to Twig |
| `src/Dto/ActiveFilterState.php` | Immutable DTO: URL params → typed filter state; serializes back to URL |
| `src/Twig/Components/Catalogue/FilterPanelComponent.php` | Live Component: draft state + COUNT query + editor search |
| `templates/catalogue/index.html.twig` | Main page template (session header, page head, panel, toolbar, grid, pagination) |
| `templates/components/Catalogue/FilterPanelComponent.html.twig` | Filter panel Twig template (Live Component) |
| `src/Entity/UserBook.php` | User↔book relationship (status enum + isFavorite) |
| `src/Entity/Enum/UserBookStatus.php` | `DANS_MA_COLLECTION`, `A_ACHETER`, `A_LIRE`, `LU`, `PAS_DANS_MA_COLLECTION` |
| `src/Repository/BookRepository.php` | Added: `findFilteredPaginated()`, `countFiltered()`, `findParagraphBounds()` |
| `src/Repository/EditorRepository.php` | Added: `findByNameSearch()`, `findWithBookCount()` |
| `src/Repository/UserBookRepository.php` | `findByUserAndBookIds()` for batch-loading card marks |
| `src/Service/CatalogueService.php` | Orchestrates filter query + bounds fetch + UserBook lookup |
| `assets/controllers/catalogue-fab_controller.js` | FAB ↔ full-screen modal, focus trap (Tab/Shift+Tab), Escape key |
| `assets/controllers/catalogue-view_controller.js` | Grid/list toggle + sessionStorage persistence |
| `assets/controllers/catalogue-search_controller.js` | In-page autocomplete: fetch `/catalogue/search-suggestions`, render dropdown |

---

## How filter apply works

```
User checks editor / moves slider → LiveProp update
  → Live Component server re-render (debounced 300 ms)
  → BookRepository::countFiltered() → "Appliquer — 186" button label updates

User clicks "Appliquer"
  → #[LiveAction] applyFilters()
  → builds URL from draft LiveProps
  → returns RedirectResponse to /catalogue?editors[]=3&paragraphMin=200&...
  → Turbo Drive navigates → CatalogueController::index() re-renders full page

User clicks chip "×"
  → GET form submit (removes one param from current URL)
  → Turbo Drive navigates → page re-renders with that filter removed

User changes sort dropdown (toolbar)
  → GET form submit with data-turbo-action="replace"
  → Turbo Drive replaces current history entry
```

---

## How book card marks work

`CatalogueController::index()` calls `CatalogueService::getUserBooksForPage(User $user, int[] $bookIds): array<int, UserBook>`.

The service batch-loads `UserBook` for the current page's 24 books via `UserBookRepository::findByUserAndBookIds()`. The controller passes the keyed map to Twig; the template passes `isFavorite`, `isOwned`, `isWishlist` props to each `Book/Card` component.

For unauthenticated users, the service returns an empty map; all card marks are false.

---

## Running tests

```bash
php bin/phpunit tests/Entity/UserBookTest.php
php bin/phpunit tests/Repository/BookRepositoryTest.php
php bin/phpunit tests/Controller/CatalogueControllerTest.php
```
