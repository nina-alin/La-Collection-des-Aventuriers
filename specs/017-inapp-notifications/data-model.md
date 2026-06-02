# Data Model: Système de Notifications In-App

## New Entity: Notification

**Table**: `notification`

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | integer | no | autoincrement | PK |
| user_id | uuid | no | — | FK → user.id ON DELETE CASCADE |
| type | varchar(50) | no | — | NotificationType enum value |
| message | text | no | — | Pre-formatted display message |
| target_url | varchar(1024) | yes | null | Resolved at creation; null = no redirect |
| is_read | boolean | no | false | |
| created_at | datetime_immutable | no | — | Set on construction (UTC) |
| source_id | varchar(255) | no | — | Idempotence key e.g. `contribution_validated:uuid` |

**Constraints**:
- `UNIQUE (user_id, source_id)` — idempotence guard (silently swallowed on duplicate insert)
- `INDEX (user_id, is_read, created_at)` — drives unread count query and panel fetch
- `INDEX (user_id, created_at)` — drives history page query

**Invariants**:
- `source_id` format: `"{type_value}:{entity_id}"` where entity_id is the triggering entity UUID or int
- `message` is stored pre-rendered (no dynamic template expansion at read time)
- `target_url` is an internal absolute path (e.g., `/suggestions/018b3c2d`) resolved at creation

**PHP Entity Shape**:

```php
#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
#[ORM\UniqueConstraint(name: 'uniq_notification_user_source', columns: ['user_id', 'source_id'])]
#[ORM\Index(columns: ['user_id', 'is_read', 'created_at'], name: 'idx_notification_user_read_date')]
#[ORM\Index(columns: ['user_id', 'created_at'], name: 'idx_notification_user_date')]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 50, enumType: NotificationType::class)]
    private NotificationType $type;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $targetUrl = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 255)]
    private string $sourceId;

    public function __construct(User $user, NotificationType $type, string $message, string $sourceId, ?string $targetUrl = null)
    {
        $this->user      = $user;
        $this->type      = $type;
        $this->message   = $message;
        $this->sourceId  = $sourceId;
        $this->targetUrl = $targetUrl;
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
```

---

## New Entity: NotificationPreference

**Table**: `notification_preference`

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | integer | no | autoincrement | PK |
| user_id | uuid | no | — | FK → user.id ON DELETE CASCADE, UNIQUE |
| contribution_validated | boolean | no | true | |
| book_activity | boolean | no | true | |
| moderation_pending | boolean | no | true | |
| rank_up | boolean | no | true | |

**Constraints**:
- `UNIQUE (user_id)` — one preference row per user (OneToOne semantic)

**Creation rule**: Created with all preferences = true when a `User` is registered (via `UserRegistrationService` or `UserGoogleVerifiedListener`).

**PHP Entity Shape**:

```php
#[ORM\Entity(repositoryClass: NotificationPreferenceRepository::class)]
#[ORM\Table(name: 'notification_preference')]
class NotificationPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(options: ['default' => true])]
    private bool $contributionValidated = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $bookActivity = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $moderationPending = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $rankUp = true;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function isEnabled(NotificationType $type): bool
    {
        return match ($type) {
            NotificationType::CONTRIBUTION_VALIDATED => $this->contributionValidated,
            NotificationType::BOOK_ACTIVITY          => $this->bookActivity,
            NotificationType::MODERATION_PENDING     => $this->moderationPending,
            NotificationType::RANK_UP                => $this->rankUp,
        };
    }
}
```

---

## New Entity: UserCollectionSubscription

**Table**: `user_collection_subscription`

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | integer | no | autoincrement | PK |
| user_id | uuid | no | — | FK → user.id ON DELETE CASCADE |
| collection_id | uuid | no | — | FK → collection.id ON DELETE CASCADE |
| created_at | datetime_immutable | no | — | |

**Constraints**:
- `UNIQUE (user_id, collection_id)` — one subscription per user-collection pair
- `INDEX (collection_id)` — drives subscriber lookup during `BookAddedToCollectionEvent` fan-out

**Usage**: `NotificationRepository` queries this table to find subscribers when a book is added to a collection.

**PHP Entity Shape**:

```php
#[ORM\Entity(repositoryClass: UserCollectionSubscriptionRepository::class)]
#[ORM\Table(name: 'user_collection_subscription')]
#[ORM\UniqueConstraint(name: 'uniq_user_collection_sub', columns: ['user_id', 'collection_id'])]
#[ORM\Index(columns: ['collection_id'], name: 'idx_collection_sub_collection')]
class UserCollectionSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Collection::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Collection $collection;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, Collection $collection)
    {
        $this->user       = $user;
        $this->collection = $collection;
        $this->createdAt  = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
```

---

## New Enum: NotificationType

**File**: `src/Entity/Enum/NotificationType.php`

```php
enum NotificationType: string
{
    case CONTRIBUTION_VALIDATED = 'contribution_validated';
    case BOOK_ACTIVITY          = 'book_activity';
    case MODERATION_PENDING     = 'moderation_pending';
    case RANK_UP                = 'rank_up';
}
```

---

## Modified Entity: User

**Change**: Add `timezone` field

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| timezone | varchar(50) | yes | null | IANA tz identifier e.g. `Europe/Paris`; null → UTC |

```php
#[ORM\Column(length: 50, nullable: true)]
private ?string $timezone = null;

public function getTimezone(): ?string { return $this->timezone; }
public function setTimezone(?string $timezone): static { $this->timezone = $timezone; return $this; }
```

---

## Messenger DTO: NotificationMessage

**File**: `src/Messenger/Message/NotificationMessage.php`

```php
final readonly class NotificationMessage
{
    public function __construct(
        public string $userId,       // User UUID string
        public string $type,         // NotificationType->value
        public string $message,      // Pre-rendered display string
        public string $sourceId,     // "{type}:{entity_id}" idempotence key
        public ?string $targetUrl = null,
    ) {}
}
```

---

## Repository: NotificationRepository

Key query methods:

```php
// Panel: latest 20, unread first, then read
findRecentForUser(User $user, int $limit = 20): array

// History page with pagination
findPaginatedForUser(User $user, int $page, int $perPage = 20): Paginator

// TwigExtension global
countUnreadForUser(User $user): int

// Mark actions
markReadById(User $user, int $id): void
markAllReadForUser(User $user): void

// Preference disable (FR-019)
deleteUnreadByTypeForUser(User $user, NotificationType $type): void

// Pruning cap
countForUser(User $user): int
deleteOldestForUser(User $user, int $count): void
```

---

## Domain Events

**File locations**: `src/Event/`

```php
// Dispatched from ModerationService::approve(WorkEntry) after flush
class ContributionValidatedEvent
{
    public function __construct(
        public readonly WorkEntry $workEntry,
        public readonly User $recipient,  // WorkEntry::getAuthor()
    ) {}
}

// Dispatched from CollectionService (write path TBD) when Book added to Collection
class BookAddedToCollectionEvent
{
    public function __construct(
        public readonly Book $book,
        public readonly Collection $collection,
        public readonly bool $isBatch = false,  // true when N books added at once
        public readonly int $batchCount = 1,
    ) {}
}

// Dispatched from SuggestionService::submit() after flush
class ModerationPendingEvent
{
    public function __construct(
        public readonly Suggestion $suggestion,
    ) {}
}

// Dispatched from ContributionValidatedListener when rank changes
class RankUpEvent
{
    public function __construct(
        public readonly User $user,
        public readonly ContributorLevel $newLevel,
    ) {}
}
```

---

## Migration Plan

Single migration covers all new tables and the User.timezone column:

```sql
-- 1. user.timezone
ALTER TABLE "user" ADD COLUMN timezone VARCHAR(50) DEFAULT NULL;

-- 2. notification
CREATE TABLE notification (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES "user"(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    target_url VARCHAR(1024) DEFAULT NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    source_id VARCHAR(255) NOT NULL,
    CONSTRAINT uniq_notification_user_source UNIQUE (user_id, source_id)
);
CREATE INDEX idx_notification_user_read_date ON notification (user_id, is_read, created_at);
CREATE INDEX idx_notification_user_date ON notification (user_id, created_at);

-- 3. notification_preference
CREATE TABLE notification_preference (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL UNIQUE REFERENCES "user"(id) ON DELETE CASCADE,
    contribution_validated BOOLEAN NOT NULL DEFAULT TRUE,
    book_activity BOOLEAN NOT NULL DEFAULT TRUE,
    moderation_pending BOOLEAN NOT NULL DEFAULT TRUE,
    rank_up BOOLEAN NOT NULL DEFAULT TRUE
);

-- 4. user_collection_subscription
CREATE TABLE user_collection_subscription (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES "user"(id) ON DELETE CASCADE,
    collection_id UUID NOT NULL REFERENCES collection(id) ON DELETE CASCADE,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    CONSTRAINT uniq_user_collection_sub UNIQUE (user_id, collection_id)
);
CREATE INDEX idx_collection_sub_collection ON user_collection_subscription (collection_id);

-- 5. Messenger transport table (created automatically by messenger:setup-transports)
-- No manual SQL needed
```

---

## Constitution Check Post-Design

All five principles verified against the data model:

| Principle | Verdict |
|-----------|---------|
| I. Complémentarité | ✅ All entities are internal (user-scoped notifications) |
| II. Symfony LTS | ✅ Doctrine ORM entities, DI everywhere, no service locator. Infrastructure: worker added to `.platform.app.yaml` same commit as Messenger config |
| III. Validation Workflow | ✅ Notifications not editorial content; `NotificationPreference` has no PENDING state |
| IV. RBAC | ✅ All mutating routes `#[IsGranted('ROLE_USER')]`; `moderation_pending` filtered by role in repository query and component |
| V. Tests | ✅ Plan requires PHPUnit coverage for all entities, handler, listeners |
