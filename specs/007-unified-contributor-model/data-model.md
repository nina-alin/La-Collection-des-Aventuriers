# Data Model: Unified Contributor Model

## New Entities

### Contributor

**Table**: `contributor`

| Field | Type | Constraints |
|---|---|---|
| id | UUID | PK (generated in constructor via `Uuid::v7()`) |
| firstName | VARCHAR(100) | NOT NULL |
| lastName | VARCHAR(100) | NOT NULL |
| pseudo | VARCHAR(100) | NULL |
| slug | VARCHAR(255) | NOT NULL, UNIQUE |
| biography | TEXT | NULL |
| nationality | VARCHAR(2) | NULL — ISO 3166-1 alpha-2; validated at app level |
| birthDate | DATE | NULL |
| deathDate | DATE | NULL — no cross-field validation; historical data may be incomplete |
| portraitImage | VARCHAR(255) | NULL — relative file path |
| deletedAt | DATETIME | NULL — Gedmo SoftDeleteable |

**Index**: `idx_contributor_slug` (UNIQUE)

**Relations**:
- `contributions` OneToMany → Contribution (cascade: ['remove'], orphanRemoval: false)

**Services**:
- Slug set by `ContributorListener` on prePersist/preUpdate via `ContributorSlugger`
- Slug source: `pseudo` if non-null, else `firstName . ' ' . lastName`

---

### Contribution

**Table**: `contribution`

| Field | Type | Constraints |
|---|---|---|
| id | UUID | PK (generated in constructor via `Uuid::v7()`) |
| contributor_id | UUID | FK → contributor.id, ON DELETE CASCADE, NOT NULL |
| book_id | INT | FK → book.id, ON DELETE CASCADE, NOT NULL |
| role | VARCHAR(20) | NOT NULL — ContributionRole enum |
| details | VARCHAR(255) | NULL |
| deletedAt | DATETIME | NULL — Gedmo SoftDeleteable (cascade from Book only) |

**Unique constraint**: `uq_contribution_contributor_book_role` on (contributor_id, book_id, role)

**Relations**:
- `contributor` ManyToOne → Contributor (nullable: false)
- `book` ManyToOne → Book (nullable: false)

---

### ContributionRole Enum

**File**: `src/Entity/Enum/ContributionRole.php`

```
ContributionRole::Author       = 'Author'
ContributionRole::Illustrator  = 'Illustrator'
ContributionRole::Traductor    = 'Traductor'
```

---

## Modified Entities

### Book

**Removed fields**:
| Field | Was |
|---|---|
| authors | ManyToMany → Author (join table: book_author) |
| illustrators | ManyToMany → Illustrator (join table: book_illustrator) |
| translator | ManyToOne → Translator (column: translator_id) |

**Added fields**:
| Field | Type | Notes |
|---|---|---|
| contributions | OneToMany → Contribution | orphanRemoval: false — Contribution table FK handles cascade |
| deletedAt | DATETIME NULL | Gedmo SoftDeleteable |

**Cascade soft-delete**: `BookSoftDeleteListener` EntityListener detects `deletedAt` null→non-null and soft-deletes all linked Contributions (FR-007).

---

## Removed Entities

| Entity | Table | Join Tables |
|---|---|---|
| Author | author | book_author |
| Illustrator | illustrator | book_illustrator |
| Translator | translator | — (book.translator_id column) |

---

## New Services

### ContributorSlugger

**File**: `src/Service/ContributorSlugger.php`

```
input: pseudo ?? (firstName . ' ' . lastName)
→ SluggerInterface::slug(input)->lower()->toString() = base
→ if base unused: return base
→ else: try base-2, base-3, … until unique in contributor table
```

### ContributorListener (EntityListener)

**File**: `src/EntityListener/ContributorListener.php`

- `prePersist(Contributor)`: generate and set slug
- `preUpdate(Contributor, PreUpdateEventArgs)`: regenerate if pseudo, firstName, or lastName changed

### BookSoftDeleteListener (EntityListener)

**File**: `src/EntityListener/BookSoftDeleteListener.php`

- `preUpdate(Book, PreUpdateEventArgs)`: if `deletedAt` changed from null → non-null, set `deletedAt` on all related Contribution rows

---

## New Repository

### ContributorRepository

**File**: `src/Repository/ContributorRepository.php`

#### `findBySlugAndRole(string $slug, ContributionRole $role): ?Contributor`

Returns Contributor with `contributions` (role-filtered) and nested `book` pre-loaded. Returns `null` if slug unknown OR contributor has no contributions of the requested role (both cases = 404).

```dql
SELECT c, contrib, b
FROM App\Entity\Contributor c
INNER JOIN c.contributions contrib
INNER JOIN contrib.book b
WHERE c.slug = :slug
  AND contrib.role = :role
ORDER BY COALESCE(b.frenchPublicationYear, 9999) ASC, b.title ASC
```

---

## Stof/Gedmo Configuration Changes

**File**: `config/packages/stof_doctrine_extensions.yaml`

Add to `orm.default`:
```yaml
softdeleteable: true
```

---

## Symfony Config Changes

No new infrastructure services required. No Platform.sh config changes (no new managed service).
