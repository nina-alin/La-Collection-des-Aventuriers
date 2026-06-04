# Data Model: Dashboard (018-home-dashboard)

## Modified Entities

### User (src/Entity/User.php)

Two new fields:

```php
#[ORM\Column(nullable: true)]
private ?\DateTimeImmutable $lastLoginAt = null;

#[ORM\Column(nullable: true)]
private ?\DateTimeImmutable $previousLoginAt = null;
```

**Rotation logic** (in `AuthenticationEventSubscriber::onLoginSuccess()`):
```
previousLoginAt ← lastLoginAt
lastLoginAt     ← now (UTC)
```

**Invariant**: For a first-time user both fields are `null`. After first login: `lastLoginAt = T1`, `previousLoginAt = null`. After second login: `lastLoginAt = T2`, `previousLoginAt = T1`.

**Dashboard usage**: `previousLoginAt` is passed to `DashboardService`. If `null`, the header subtitle shows a generic welcome message.

---

## New Entities

### ActivityEvent (src/Entity/ActivityEvent.php)

Stores community activity events for the "ACTIVITÉ" feed. Distinct from the personal `Notification` entity.

```
Table: activity_event
Retention: 30 days (purge via PurgeActivityEventsCommand, monthly cron)
```

**Fields**:

| Field | Type | Nullable | Notes |
|-------|------|----------|-------|
| `id` | `int` (auto) | no | PK |
| `type` | `ActivityEventType` (enum) | no | SOCIAL / CONTRIBUTION / MODERATION / PERSONAL |
| `actorUser` | `ManyToOne(User)` | no | `onDelete: CASCADE`; actor who triggered the event |
| `actorInitials` | `string(4)` | yes | Snapshot at write time (e.g., "AB"); null if user has no displayName |
| `actorPseudo` | `string(30)` | no | Snapshot at write time for display resilience |
| `bookTitle` | `string(255)` | yes | Snapshot of the book title (null for non-book events) |
| `bookSlug` | `string(255)` | yes | For routing to book detail page |
| `statusBadge` | `string(20)` | yes | e.g., "VALIDÉE", "EN ATTENTE", "REFUSÉE" (null when not a suggestion event) |
| `createdAt` | `DateTimeImmutable` | no | Set in constructor |

**Indexes**:
- `idx_activity_event_created_at` on `(created_at)` — for `ORDER BY created_at DESC LIMIT 10`
- `idx_activity_event_type_created_at` on `(type, created_at)` — optional, for filtered views

---

### ActivityEventType (src/Entity/Enum/ActivityEventType.php)

```php
enum ActivityEventType: string
{
    case SOCIAL       = 'social';       // Rating submitted
    case CONTRIBUTION = 'contribution'; // Book entry published
    case MODERATION   = 'moderation';   // Moderator validated/refused a suggestion
    case PERSONAL     = 'personal';     // User added book to wishlist
}
```

---

## New Repository Methods

### BookRepository

```php
// Count published (wiki-visible) books, respecting SoftDeleteable filter
public function countPublished(): int;

// Count PUBLISHED books with createdAt >= $since, for header subtitle [N] in FR-003
// Required by buildHeader() when previousLoginAt is not null
public function countPublishedSince(\DateTimeImmutable $since): int;

// 5 most recently updated published books (for "LES NOUVEAUTÉS")
// Returns Book[] with contributions and contributors eager-loaded
public function findRecentlyPublished(int $limit = 5): array;

// Average rating per book id, for a set of book ids
// Already exists: findAverageRatingsByIds(array $bookIds): array ✅
```

`findRecentlyPublished()` query skeleton:
```sql
SELECT b FROM Book b
  LEFT JOIN b.contributions contrib
  LEFT JOIN contrib.contributor contributor
  LEFT JOIN b.editor e
WHERE b.status = :published
ORDER BY b.updatedAt DESC
LIMIT :limit
```

### UserBookRepository

```php
// Total books owned by user
public function countOwnedByUser(User $user): int;

// Books added to owned in the last N days (sliding window, for "+X ce mois")
public function countOwnedAddedSince(User $user, \DateTimeImmutable $since): int;

// Books in reading pile (isToRead = true)
public function countToReadByUser(User $user): int;

// Books in wishlist/buy list (isToBuy = true)
public function countToBuyByUser(User $user): int;
```

### SuggestionRepository

```php
// Global count of PENDING suggestions across all users (for FR-003 and FR-012)
public function countGlobalPending(): int;

// User's suggestions validated in the last 24 h (for FR-006 KPI subtitle)
// Returns array of validated Suggestion with submittedAt (or validatedAt if tracked)
public function countRecentlyValidatedByUser(User $user, \DateTimeImmutable $since): int;

// Total suggestion count for this user (all statuses, for KPI main value)
// Already partially covered by countByStatus(); add:
public function countAllByUser(User $user): int;
```

### ContributorRepository

```php
// Existing countAll() returns ALL contributors regardless of book status — insufficient for FR-008.
// New: count contributors who have at least one PUBLISHED book contribution (for catalogueAuthorCount)
public function countWithPublishedBooks(): int;
```

---

### ActivityEventRepository

```php
// 10 most recent community events (for "ACTIVITÉ" feed, FR-017)
public function findRecentCommunity(int $limit = 10): array;

// Delete all events older than $before (for purge command)
public function deleteOlderThan(\DateTimeImmutable $before): int;
```

---

## New Domain Events (src/Event/)

These are dispatched by existing services and consumed by `ActivityEventListener`.

```php
// Dispatched by ReviewService after flush
final readonly class ReviewSubmittedEvent
{
    public function __construct(
        public User   $actor,
        public Book   $book,
    ) {}
}

// Dispatched by ModerationService when Book status → PUBLISHED
final readonly class BookPublishedEvent
{
    public function __construct(
        public User   $actor,    // moderator who approved
        public Book   $book,
    ) {}
}

// Dispatched by ModerationService after suggestion validated or refused
final readonly class SuggestionModeratedEvent
{
    public function __construct(
        public User            $actor,       // moderator
        public Suggestion      $suggestion,
        public SuggestionStatus $newStatus,  // VALIDATED or REFUSED
    ) {}
}

// Dispatched by UserBookService when isToBuy set to true
final readonly class BookAddedToWishlistEvent
{
    public function __construct(
        public User $actor,
        public Book $book,
    ) {}
}
```

---

## Dashboard DTO (src/Dto/DashboardData.php)

Internal contract between `DashboardService` and the Twig template.

```php
final readonly class DashboardData
{
    public function __construct(
        // Header
        public string                  $greeting,          // "SALUTATIONS, MARIUS."
        public string                  $formattedDate,     // "MARDI 15 MAI"
        public string                  $headerSubtitle,    // contextual or welcome
        // KPI blocks
        public int                     $collectionCount,
        public int                     $collectionDelta,   // +N last 30 days
        public int                     $toReadCount,
        public int                     $toBuyCount,
        public int                     $suggestionsTotal,
        public int                     $suggestionsPending,
        public int                     $suggestionsValidatedRecently, // last 24h
        // Quick access cards
        public int                     $catalogueBookCount,
        public int                     $catalogueAuthorCount,
        public int                     $libraryBookCount,   // same as collectionCount
        public int                     $libraryToReadCount, // same as toReadCount
        public int                     $wishlistCount,      // same as toBuyCount
        public int                     $globalPendingSuggestions, // for moderation card
        // Nouveautés
        public array                   $recentBooks,        // Book[] (max 5)
        public array                   $averageRatings,     // bookId → float|null
        // Activité
        public array                   $activityEvents,     // ActivityEvent[] (max 10)
        // User context
        public bool                    $isModerator,
    ) {}
}
```

**Error isolation**: `DashboardService` wraps each section in its own `try/catch`. On exception, a sentinel value is used (e.g., `recentBooks = []` with an `errors` array tracking which sections failed). The template renders an inline error block for failed sections.

---

## Migrations

Two Doctrine migrations will be generated:

1. **Add User login timestamp fields**
   ```sql
   ALTER TABLE "user" ADD last_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;
   ALTER TABLE "user" ADD previous_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;
   ```

2. **Create activity_event table**
   ```sql
   CREATE TABLE activity_event (
       id SERIAL PRIMARY KEY,
       actor_user_id UUID NOT NULL REFERENCES "user"(id) ON DELETE CASCADE,
       type VARCHAR(20) NOT NULL,
       actor_initials VARCHAR(4),
       actor_pseudo VARCHAR(30) NOT NULL,
       book_title VARCHAR(255),
       book_slug VARCHAR(255),
       status_badge VARCHAR(20),
       created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
   );
   CREATE INDEX idx_activity_event_created_at ON activity_event (created_at DESC);
   CREATE INDEX idx_activity_event_type_created_at ON activity_event (type, created_at DESC);
   ```
