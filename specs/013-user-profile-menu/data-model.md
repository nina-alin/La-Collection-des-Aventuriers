# Data Model: Menu Profil Utilisateur Responsive

## No new entities

This feature introduces no new Doctrine entities or database migrations. All data comes from existing entities via existing repositories.

---

## Data Aggregated by ProfileMenuService

`ProfileMenuService::getMenuData(User $user): ProfileMenuDto`

### Source mapping

| Display element | Source | Method |
|---|---|---|
| Pseudo / display name | `User::getPseudo()` / `User::getDisplayName()` | Direct |
| Avatar URL | `User::getAvatarUrl()` | Direct (null → initials fallback) |
| Role (standard / mod / admin) | `User::getRoles()` | Array check for `ROLE_MODERATOR`, `ROLE_ADMIN` |
| Rank/titre ("Aventurier") | `ContributorLevelService::computeRank($user)` → `ContributorLevel::getName()` | Via service |
| Validated suggestion count | `SuggestionRepository::countByStatus($user, SuggestionStatus::VALIDATED)` | Via service |
| Pending moderation count | `WorkEntryRepository::countPending() + CorrectionProposalRepository::countPending()` | Two new COUNT queries |

### ProfileMenuDto (read-only value object)

```php
// src/Dto/ProfileMenuDto.php
final class ProfileMenuDto
{
    public function __construct(
        public readonly string  $pseudo,
        public readonly ?string $displayName,
        public readonly ?string $avatarUrl,
        public readonly string  $highestRole,       // 'ROLE_ADMIN' | 'ROLE_MODERATOR' | 'ROLE_USER'
        public readonly ?string $rankName,           // null → display "—"
        public readonly int     $validatedCount,
        public readonly int     $pendingModerationCount,
    ) {}
}
```

### Repository additions

**`WorkEntryRepository::countPending(): int`**
```php
return (int) $this->createQueryBuilder('w')
    ->select('COUNT(w.id)')
    ->where('w.status = :status')
    ->setParameter('status', 'PENDING')
    ->getQuery()
    ->getSingleScalarResult();
```

**`CorrectionProposalRepository::countPending(): int`**
```php
return (int) $this->createQueryBuilder('c')
    ->select('COUNT(c.id)')
    ->where('c.status = :status')
    ->setParameter('status', 'PENDING')
    ->getQuery()
    ->getSingleScalarResult();
```

---

## Existing entities used (read-only)

| Entity | Fields read | Purpose |
|---|---|---|
| `User` | `pseudo`, `displayName`, `avatarUrl`, `roles` | Identity header |
| `ContributorLevel` | `name` | Rank display in "Mon Profil" meta |
| `Suggestion` (via repo) | `status = VALIDATED`, count | "Mes Suggestions" counter |
| `WorkEntry` (via repo) | `status = PENDING`, count | Moderation counter (partial) |
| `CorrectionProposal` (via repo) | `status = PENDING`, count | Moderation counter (partial) |
