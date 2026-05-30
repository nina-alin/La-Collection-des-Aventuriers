# Data Model: Système de Notation et Commentaires

**Branch**: `009-book-review-rating`

---

## New Entity: Review

**Table**: `review`

### Fields

| Field | Type | Constraints | Notes |
|-------|------|-------------|-------|
| `id` | `INT` | PK, auto-generated | |
| `score` | `SMALLINT` | NOT NULL, CHECK 1–10 | Validated by `Range(min:1, max:10)` |
| `comment` | `TEXT` | nullable | Empty string `""` normalized to `NULL` before persist |
| `created_at` | `DATETIME_IMMUTABLE` | NOT NULL | Set once on construction |
| `updated_at` | `DATETIME_IMMUTABLE` | NOT NULL | Updated on every change (Doctrine lifecycle callback) |
| `book_id` | `INT` | FK → `book.id`, NOT NULL | Cascade delete: if Book deleted → Reviews deleted |
| `user_id` | `UUID` | FK → `"user".id`, nullable | SET NULL: if User deleted → `user_id` becomes `NULL` |

### Unique Constraint

```sql
UNIQUE (user_id, book_id)
```

One review per user per book. `user_id` is nullable to support anonymized reviews.

### Indexes

```sql
INDEX idx_review_book_id ON review (book_id)
INDEX idx_review_book_updated_at ON review (book_id, updated_at DESC)
```

### Doctrine Mapping (PHP attributes)

```php
#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_review_user_book', columns: ['user_id', 'book_id'])]
#[ORM\HasLifecycleCallbacks]
class Review
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'smallint')]
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 10)]
    private int $score;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 1000)]
    private ?string $comment = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne(targetEntity: Book::class, inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Book $book;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;
}
```

### Lifecycle

```
ORM\PrePersist  → set createdAt + updatedAt = now()
ORM\PreUpdate   → set updatedAt = now()
```

### Comment Normalization

```php
public function setComment(?string $comment): static
{
    $this->comment = ($comment === '' || $comment === null) ? null : $comment;
    return $this;
}
```

---

## Modified Entity: Book

Add reverse side of the `OneToMany → Review` relationship:

```php
/** @var Collection<int, Review> */
#[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'book', cascade: ['remove'], orphanRemoval: true)]
private Collection $reviews;
```

Cascade `remove` ensures all reviews deleted when Book is deleted (mirrors `ON DELETE CASCADE`).

---

## Modified Entity: User

Add reverse side (no cascade — SET NULL handled at DB level):

```php
/** @var Collection<int, Review> */
#[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'user')]
private Collection $reviews;
```

---

## Read Model: ReviewStats (readonly DTO, not persisted)

```php
readonly class ReviewStats
{
    public function __construct(
        public float $averageScore,      // round(AVG(score), 1, PHP_ROUND_HALF_UP)
        public int $totalCount,
        public array $distribution,      // int[10]: index 0 = score 1, index 9 = score 10
        public array $histogramHeights,  // float[10]: 0.0–100.0 (count/max_count * 100)
        public array $lastEvaluators,    // User|null[], max 4, ordered by updatedAt DESC
    ) {}
}
```

### Computed by `ReviewRepository::getStatsForBook(Book $book): ReviewStats`

```sql
SELECT score, COUNT(*) as cnt FROM review WHERE book_id = :id GROUP BY score
SELECT AVG(score) FROM review WHERE book_id = :id
SELECT user_id FROM review WHERE book_id = :id ORDER BY updated_at DESC LIMIT 4
```

---

## State Transitions

```
[No review] --submit--> [Review created]  (createdAt = updatedAt = now)
[Review exists] --submit--> [Review updated]  (updatedAt = now, score/comment replaced)
[Review exists] --delete (author)--> [Review deleted]  (4 Turbo Stream targets updated)
[Review exists] --delete (mod/admin)--> [Review deleted]  (4 Turbo Stream targets updated)
[User deleted] --cascade--> review.user_id = NULL  (review persists, anonymized)
[Book deleted] --cascade--> all reviews deleted
```

---

## Validation Rules Summary

| Field | Rule | Error |
|-------|------|-------|
| score | NotBlank | "La note est obligatoire" |
| score | Range(1–10) | "La note doit être entre 1 et 10" |
| comment | Length(max: 1000) | "Le commentaire ne peut dépasser 1 000 caractères" |
| user | authenticated | Redirect to login (handled by `#[IsGranted('IS_AUTHENTICATED_FULLY')]`) |
