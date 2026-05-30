# Data Model — 011 Page Contributeur Suggestions

**Date**: 2026-05-30 | **Branch**: `011-contributor-page`

## New Entities

---

### `Suggestion`

Core entity representing a user's contribution submission.

| Field | Type | Nullable | Notes |
|-------|------|----------|-------|
| `id` | `Uuid` (v7) | no | PK |
| `user` | `ManyToOne → User` | no | submitter; `onDelete: CASCADE` |
| `entityType` | `SuggestionEntityType` enum | no | BOOK \| AUTHOR \| ILLUSTRATOR \| TRADUCTOR \| EDITOR \| COLLECTION |
| `mode` | `SuggestionMode` enum | no | NEW_ENTRY \| CORRECTION |
| `sourceEntityId` | `Uuid` nullable | yes | FK to source entity when mode=CORRECTION (polymorphic by entityType) |
| `sourceEntityType` | `string` nullable | yes | redundant type hint for polymorphic lookup (e.g., "App\\Entity\\Book") |
| `formData` | `json` | no | serialized wizard form data (all steps) |
| `status` | `SuggestionStatus` enum | no | default: PENDING |
| `coverImagePath` | `string(255)` nullable | yes | relative path to processed cover image |
| `submittedAt` | `DateTimeImmutable` | no | auto-set on persist |
| `refusal` | `OneToOne → SuggestionRefusal` mappedBy | yes | populated when status = REFUSED |

**Indexes**: `idx_suggestion_user_status` on `(user_id, status)` — supports quota check and panel query.

**Validation rules**:
- `user` not null
- `entityType` must be valid enum value
- `mode` must be valid enum value
- `formData` not empty array
- `status` default PENDING on create (never set by user)

---

### `SuggestionRefusal`

Populated by moderator when a Suggestion is refused.

| Field | Type | Nullable | Notes |
|-------|------|----------|-------|
| `id` | `Uuid` (v7) | no | PK |
| `suggestion` | `OneToOne → Suggestion` | no | `onDelete: CASCADE`; `inversedBy: refusal` |
| `moderator` | `ManyToOne → User` | yes | the moderator who refused; `onDelete: SET NULL` — nullable required so FK survives moderator account deletion |
| `reason` | `text` | no | free-text refusal explanation |
| `actions` | `json` (array of `SuggestionRefusalAction` values) | no | default: `[]`; e.g., `["VOIR_FICHE", "MASQUER"]` |
| `refusedAt` | `DateTimeImmutable` | no | auto-set on persist |

**Validation rules**:
- Unknown action keys ignored at display time (FR-045) — stored as-is; Twig renders only recognized enum cases
- `reason` not blank

---

### `ContributorLevel`

Defines the gamification rank thresholds. Seeded via fixtures.

| Field | Type | Nullable | Notes |
|-------|------|----------|-------|
| `id` | `int` (auto-increment) | no | PK |
| `name` | `string(100)` | no | e.g., "Chroniqueur confirmé" |
| `rankNumber` | `smallint` | no | ordinal (1–N), unique |
| `threshold` | `int` | no | number of validated suggestions needed to reach this rank |

**Seed data** (finalized — matches T013 fixture values):

| rankNumber | name | threshold |
|------------|------|-----------|
| 1 | Novice | 0 |
| 2 | Apprenti | 5 |
| 3 | Chroniqueur confirmé | 15 |
| 4 | Archiviste | 30 |
| 5 | Érudit | 60 |
| 6 | Grand Sage | 100 |

---

## New Enums

### `SuggestionStatus`
```php
enum SuggestionStatus: string {
    case PENDING   = 'PENDING';
    case VALIDATED = 'VALIDATED';
    case REFUSED   = 'REFUSED';
}
```

### `SuggestionEntityType`
```php
enum SuggestionEntityType: string {
    case BOOK        = 'BOOK';
    case AUTHOR      = 'AUTHOR';
    case ILLUSTRATOR = 'ILLUSTRATOR';
    case TRADUCTOR   = 'TRADUCTOR';
    case EDITOR      = 'EDITOR';
    case COLLECTION  = 'COLLECTION';
}
```

### `SuggestionMode`
```php
enum SuggestionMode: string {
    case NEW_ENTRY  = 'NEW_ENTRY';
    case CORRECTION = 'CORRECTION';
}
```

### `SuggestionRefusalAction`
```php
enum SuggestionRefusalAction: string {
    case VOIR_FICHE = 'VOIR_FICHE';
    case MASQUER    = 'MASQUER';
}
```

---

## Relationships to Existing Entities

| Existing Entity | Relation | Notes |
|-----------------|----------|-------|
| `User` | `OneToMany → Suggestion` (via `user_id`) | User submits suggestions |
| `User` | `OneToMany → SuggestionRefusal` (via `moderator_id`) | Moderator refuses suggestions |
| `Book` | None (direct FK) | Source entity referenced via `Suggestion.sourceEntityId` + `sourceEntityType` = `App\Entity\Book` — polymorphic, no ORM relation |

---

## State Transitions

```
Suggestion lifecycle:
  [Create] → PENDING
  PENDING → VALIDATED  (by ROLE_MODERATOR via ModerationController)
  PENDING → REFUSED    (by ROLE_MODERATOR; triggers SuggestionRefusal creation)
  VALIDATED → (terminal)
  REFUSED   → (terminal, but user can submit a new Suggestion independently)
```

---

## Database Migration

New tables: `suggestion`, `suggestion_refusal`, `contributor_level`

Key constraints:
- `suggestion.user_id` → `user.id` CASCADE DELETE
- `suggestion_refusal.suggestion_id` → `suggestion.id` CASCADE DELETE
- `suggestion_refusal.moderator_id` → `user.id` SET NULL on delete
- `suggestion.status` default `'PENDING'`
- Composite index `idx_suggestion_user_status` on `suggestion(user_id, status)`

---

## `WizardComponent` State (LiveComponent — not persisted)

The LiveComponent holds wizard state in PHP object properties during the page session:

| Property | Type | Purpose |
|----------|------|---------|
| `$step` | `int` (1–4) | current active step |
| `$mode` | `?string` | 'new_entry' \| 'correction' |
| `$entityType` | `?string` | SuggestionEntityType value |
| `$sourceEntityId` | `?string` | UUID of source entity (correction mode) |
| `$originalData` | `array` | snapshot of source entity data at selection time |
| `$formData` | `array` | current form field values |
| `$coverImageTempPath` | `?string` | temp path after server-side processing |
| `$errors` | `array` | field-keyed validation errors |
| `$isSubmitting` | `bool` | disables submit button during POST |
| `$pendingCount` | `int` | re-checked from DB on each render cycle |

None of these properties are persisted to DB. They exist only for the duration of the page session.
