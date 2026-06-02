# Contract: UserBookService

## Class

`App\Service\UserBookService`

## Dependencies (injected)

- `UserBookRepository` — fetch/find existing record
- `EntityManagerInterface` — persist, remove, flush

## Public Interface

### toggleOwned(User, Book): array

Toggles `isOwned` for the (user, book) pair.

- If no record: creates one with `isOwned = true`
- If `isOwned` was false → set `true`, and **set `isToBuy = false`**
- If `isOwned` was true → set `false`
- If all flags false after toggle → delete record
- Returns `['newValue' => bool, 'affected' => ['isToBuy']]` when side-effect occurs

### toggleToRead(User, Book): array

Toggles `isToRead` for the (user, book) pair. No side effects.

- Returns `['newValue' => bool, 'affected' => []]`

### toggleToBuy(User, Book): array

Toggles `isToBuy` for the (user, book) pair.

- If `isToBuy` was false → set `true`, and **set `isOwned = false`**
- If `isToBuy` was true → set `false`
- If all flags false after toggle → delete record
- Returns `['newValue' => bool, 'affected' => ['isOwned']]` when side-effect occurs

### toggleFavorite(User, Book): array

Toggles `isFavorite` for the (user, book) pair. No side effects.

- Returns `['newValue' => bool, 'affected' => []]`

## Return Shape

```php
[
    'newValue' => bool,      // the new value of the toggled flag
    'affected' => string[],  // field names auto-modified (e.g. ['isToBuy']); empty if no side-effect
]
```

## Error Handling

- Any Doctrine exception propagates up (component catches and dispatches error toast)
- No silent swallowing of exceptions

## Idempotence

Calling `toggleOwned(user, book)` twice in a row returns to the original state (true toggle). The service reads DB state before toggling, so concurrent calls are safe under DB-level unique constraint.

## Invariants Enforced

1. `isOwned` and `isToBuy` are never both true for the same (user, book)
2. A `UserBook` record with all 4 booleans false is immediately deleted
3. All mutations are flushed in a single `flush()` call (atomic)
