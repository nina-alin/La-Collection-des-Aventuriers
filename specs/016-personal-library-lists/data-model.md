# Data Model: Personal Library Lists

## Entity: UserBook (MODIFIED)

**Table**: `user_book`

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | integer | no | autoincrement | PK |
| user_id | integer | no | — | FK → user.id ON DELETE CASCADE |
| book_id | integer | no | — | FK → book.id ON DELETE CASCADE |
| is_owned | boolean | no | false | "Dans ma collection" |
| is_to_read | boolean | no | false | "À lire" |
| is_to_buy | boolean | no | false | "À acheter" |
| is_favorite | boolean | no | false | "Favori" (already existed) |
| created_at | timestamp | no | — | Set on construction |
| updated_at | timestamp | no | — | Updated via PreUpdate lifecycle |

**Removed column**: `status` (varchar/enum `UserBookStatus`)

**Constraints**:
- `UNIQUE (user_id, book_id)` — one record per user-book pair
- `INDEX (user_id)` — fast per-user queries
- `INDEX (book_id)` — fast per-book queries

**Invariant**: A `UserBook` row MUST NOT exist if all 4 boolean fields are false. The service layer enforces deletion when the last flag is toggled off.

**Auto-coherence rule** (applied in service, not at DB level):
- `is_owned = true` → `is_to_buy = false` (symmetric)
- `is_to_buy = true` → `is_owned = false` (symmetric)
- `is_to_read` and `is_favorite` are fully independent of each other and of the above pair

## Removed: UserBookStatus Enum

`src/Entity/Enum/UserBookStatus.php` is deleted. No references remain after migration.

## Migration Plan

```sql
-- Step 1: add new columns
ALTER TABLE user_book
  ADD COLUMN is_owned    BOOLEAN NOT NULL DEFAULT FALSE,
  ADD COLUMN is_to_read  BOOLEAN NOT NULL DEFAULT FALSE,
  ADD COLUMN is_to_buy   BOOLEAN NOT NULL DEFAULT FALSE;

-- Step 2: migrate existing data
UPDATE user_book SET is_owned   = TRUE WHERE status = 'dans-ma-collection';
UPDATE user_book SET is_to_buy  = TRUE WHERE status = 'a-acheter';
UPDATE user_book SET is_to_read = TRUE WHERE status = 'a-lire';
-- status = 'lu' and 'pas-dans-ma-collection' → deleted (clean start)
DELETE FROM user_book WHERE status IN ('lu', 'pas-dans-ma-collection');

-- Step 3: drop old column
ALTER TABLE user_book DROP COLUMN status;
```

## New PHP Entity Shape

```php
class UserBook
{
    // ... id, user, book, createdAt, updatedAt unchanged

    #[ORM\Column(options: ['default' => false])]
    private bool $isOwned = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isToRead = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isToBuy = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isFavorite = false;   // already existed

    public function isAllInactive(): bool
    {
        return !$this->isOwned && !$this->isToRead && !$this->isToBuy && !$this->isFavorite;
    }
}
```

## State Transition Diagram

```
[No UserBook record] ←── isAllInactive() ──→ [UserBook record deleted]
        │
        │ first toggle
        ▼
[UserBook created]
  isOwned / isToRead / isToBuy / isFavorite = true (whichever triggered)

Toggle: isOwned = true  ──→  auto: isToBuy = false
Toggle: isToBuy = true  ──→  auto: isOwned = false
Toggle: isToRead        ──→  independent (no cascades)
Toggle: isFavorite      ──→  independent (no cascades)
```

## Related Entities (unchanged)

- **User**: `src/Entity/User.php` — no changes
- **Book**: `src/Entity/Book.php` — no changes (has `inversedBy: 'userBooks'` collection but not read by this feature)

## Repository: UserBookRepository (MODIFIED)

Add method:
```php
public function findByUserAndBook(User $user, Book $book): ?UserBook
```
Used by `UserBookService` to fetch the record before toggling.
