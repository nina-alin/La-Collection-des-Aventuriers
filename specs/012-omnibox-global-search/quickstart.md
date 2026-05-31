# Quickstart: Omnibox Global Search

**Branch**: `012-omnibox-global-search`

---

## Prerequisites

- Docker Compose running (`make up` or `docker compose up -d`)
- Database migrated (`make migrate` or `php bin/console doctrine:migrations:migrate`)
- Assets compiled (`npm run watch` or `npm run dev`)
- At least a few Book/Collection/Contributor fixtures loaded

---

## Running the app

```bash
# Start Docker services
docker compose up -d

# Compile assets (watch mode for development)
npm run watch

# Check Symfony logs
docker compose exec php tail -f var/log/dev.log
```

---

## Testing the feature manually

1. Open `http://localhost` (or configured local URL)
2. Log in with any `ROLE_USER` account
3. Click the search field in the navigation bar
4. Verify:
   - Panel opens with "Recherches Récentes" and "Souvent Consultés" sections
   - Header shows "COMMENCE À ÉCRIRE_" with ↑↓ ESC legend
5. Type at least 1 character (e.g. "ste")
   - Skeleton placeholders appear after ~0ms
   - Results appear after ~300ms (debounce) + API latency
   - Pre-saisie sections disappear
6. Use ↑/↓ to navigate results, Enter to navigate, Escape to close
7. Clear the field → panel returns to pre-saisie state
8. Click outside → panel closes

---

## Running automated tests

```bash
# All tests
php bin/phpunit

# Only search-related tests
php bin/phpunit --filter Search

# Specific test file
php bin/phpunit tests/Unit/Service/GlobalSearchServiceTest.php
php bin/phpunit tests/Functional/Controller/SearchControllerTest.php
```

---

## Key files

| File | Purpose |
|------|---------|
| `src/Controller/SearchController.php` | API endpoints `/api/search` and `/api/search/popular` |
| `src/Service/GlobalSearchService.php` | Business logic: query + merge + cap results |
| `src/Dto/Search/SearchResultItem.php` | Result shape DTO |
| `assets/controllers/search_controller.js` | Stimulus controller (panel, keyboard nav, debounce, history) |
| `templates/components/Layout/Navbar.html.twig` | Search form added to navbar |
| `assets/styles/components/search.css` | Imports `design/assets/search.css` + skeleton animation |

---

## API endpoints (manual testing)

```bash
# Dynamic search (requires authenticated session cookie)
curl -b 'PHPSESSID=...' 'http://localhost/api/search?q=steve'

# Popular items
curl -b 'PHPSESSID=...' 'http://localhost/api/search/popular'
```

---

## Troubleshooting

**Panel doesn't open**: Check browser console for Stimulus controller errors. Verify `data-controller="search"` is on the `<form class="sh-search">` element.

**No results**: Check network tab — the API request should fire at `/api/search?q=...`. Ensure DB has PUBLISHED books/contributors.

**Skeleton shows but results never appear**: Check for 5000ms API timeout. Look for CORS or session issues in browser console.

**CSS not applied**: Run `npm run dev` to recompile assets. Verify `search.css` is imported in `app.scss`.
