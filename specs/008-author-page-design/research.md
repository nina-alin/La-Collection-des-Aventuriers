# Research: Intégration du Design de la Page Auteur

**Feature**: `008-author-page-design` | **Date**: 2026-05-26

## R-001 — Slugification (Twig + PHP)

**Decision**: Symfony's `SluggerInterface` (AsciiSlugger) injected into `ContributorRepository`. Saga slugs pre-computed in controller and passed as `sagaGroups` to template.

**Rationale**: `AsciiSlugger` handles accented characters (e.g. "Légendes" → "legendes") reliably. Pre-computing in controller avoids a custom Twig extension. Consistent between PHP (repository filter) and Twig (pill URLs).

**Slug format**: `AsciiSlugger::slug($saga)->lower()` → "loup-solitaire", "legendes-de-magnamund".

**Alternatives considered**:
- Custom Twig `|slugify` filter — adds a file (`TwigExtension`) for a one-line concern. Not worth it.
- `LOWER(REPLACE(b.saga, ' ', '-'))` in DQL — breaks on accented chars, fragile.
- `strtolower(str_replace(' ', '-', $saga))` — simple but misses accents (è→è, not è→e).

## R-002 — Age Calculation

**Decision**: Compute in controller using `\DateTimeInterface::diff()`. Pass `contributorAge` (int|null) and `contributorAgeAtDeath` (int|null) as template variables.

**Implementation**:
```php
$birthDate = $contributor->getBirthDate();
$deathDate = $contributor->getDeathDate();
$age = $birthDate ? $birthDate->diff(new \DateTimeImmutable())->y : null;
$ageAtDeath = ($birthDate && $deathDate) ? $birthDate->diff($deathDate)->y : null;
```

**Rationale**: `diff()->y` returns full years — the standard "age" calculation. Cleaner than Twig date arithmetic. The spec assumption "sans logique PHP supplémentaire" is relaxed: this is view-preparation (2 lines), not business logic.

**Alternatives considered**:
- Full Twig date arithmetic: `(("now"|date("U") - birthDate|date("U")) / 31557600)|round` — complex, timezone-sensitive.
- `getAge()` method on `Contributor` entity — would work but adds a method to an entity for a single view concern.

## R-003 — Filtered/Sorted Contributions Query Strategy

**Decision**: Single DQL JOIN query loads all author contributions. PHP-side `array_filter` + `usort` handles saga filtering and sorting. Repository method returns a structured array (not just `?Contributor`).

**Query**:
```dql
SELECT c, contrib, b, e
FROM App\Entity\Contributor c
INNER JOIN c.contributions contrib
INNER JOIN contrib.book b
INNER JOIN b.editor e
WHERE c.slug = :slug
  AND contrib.role = :role
```
*No WHERE on saga — saga filter applied in PHP after load.*

**PHP filtering**:
```php
$slugger = $this->slugger; // AsciiSlugger
$filtered = array_filter($contributions, fn($c) =>
    $slugger->slug($c->getBook()->getSaga() ?? '')->lower()->toString() === $sagaFilter
);
if (empty($filtered)) {
    // Unknown saga → return all (spec requirement)
    $filtered = $contributions;
}
```

**PHP sorting**:
```php
usort($filtered, fn($a, $b) => $sortOrder === 'alpha'
    ? strcmp($a->getBook()->getTitle(), $b->getBook()->getTitle())
    : ($a->getBook()->getFrenchPublicationYear() ?? PHP_INT_MAX)
      <=> ($b->getBook()->getFrenchPublicationYear() ?? PHP_INT_MAX)
      ?: strcmp($a->getBook()->getTitle(), $b->getBook()->getTitle())
);
```

**Saga groups computation** (for pills, also in repository):
```php
$sagaGroups = [];
foreach ($allContributions as $c) {
    $saga = $c->getBook()->getSaga() ?? '';
    $slug = $slugger->slug($saga)->lower()->toString();
    if (!isset($sagaGroups[$slug])) {
        $sagaGroups[$slug] = ['slug' => $slug, 'name' => $saga, 'count' => 0];
    }
    $sagaGroups[$slug]['count']++;
}
```

**Rationale**: ≤50 contributions per author; PHP-side processing is negligible. DQL cannot reliably slugify accented strings. Saga groups must be computed from the full (unfiltered) list so pill counts are always accurate.

**Alternatives considered**:
- DQL WITH clause + LOWER/REPLACE: fragile for "Légendes", rejected.
- Separate query per saga: N+1 problem, rejected.
- Return `?Contributor` only: forces controller to re-do filtering, violates "logic in repo" principle.

## R-004 — Book Card "Vue · Liste" Toggle

**Decision**: Pure client-side CSS class toggle via inline JavaScript. No Stimulus controller, no new JS file.

**Implementation** (in template):
```html
<button id="btn-list-view" 
        onclick="document.querySelector('.books-grid').classList.toggle('is-list')"
        aria-pressed="false">
  Vue · Liste
</button>
```

CSS in `_auteur.scss`:
```scss
.books-grid.is-list {
  grid-template-columns: 1fr;
  
  .book-card {
    flex-direction: row;
    // compact horizontal row styles
  }
}
```

**Rationale**: Spec says "toggle JavaScript inline (ajout/suppression de la classe CSS)". No Stimulus, no external dependency. State is not persisted across navigation (acceptable per spec).

## R-005 — Biography Collapse Toggle (Mobile)

**Decision**: Inline JavaScript matching the mockup exactly. `.bio-body` starts with `.is-collapsed` class on mobile (via Twig, always rendered with the class). JS toggles it.

**Implementation** (from design/pages/auteur.html):
```html
<div class="bio-body is-collapsed" id="bio-body">…</div>
<button class="bio-toggle" id="bio-toggle"
        onclick="var b=document.getElementById('bio-body'),t=document.getElementById('bio-toggle');
                 b.classList.toggle('is-collapsed');
                 t.classList.toggle('is-open');
                 t.querySelector('span').textContent=b.classList.contains('is-collapsed')?'Lire la suite':'Replier'">
  <span>Lire la suite</span>
  <svg>…chevron…</svg>
</button>
```

CSS hides button at ≥1100px (`.bio-toggle { display: none; }` at `@media (min-width: 1100px)`).

**Rationale**: Spec says "JavaScript inline présent dans la maquette, sans dépendance externe". This is copy-from-mockup.

## R-006 — Editor Relation on Book

**Discovery**: `Book.editor` is a `ManyToOne` to `Editor`. The existing DQL query must also JOIN the editor to avoid N+1 when template accesses `contribution.book.editor.name`. Confirmed in `Book.php` line 85.

**Action**: Add `INNER JOIN b.editor e` to the repository query (already included in R-003 decision above).
