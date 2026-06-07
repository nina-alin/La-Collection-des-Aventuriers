# Research: Système de Rangs et Progression

## Decision 1: ContributionValidatedEvent refactoring

**Decision**: Replace `public WorkEntry $workEntry` with `public string $title` in `ContributionValidatedEvent`.

**Rationale**: The event is dispatched for both `WorkEntry` and `CorrectionProposal` approvals. The only use of `$workEntry` in listeners is `$event->workEntry->getTitle()`. Passing the title string directly removes the cross-entity import from the event class and keeps the event focused on its data contract.

**Alternatives considered**:
- Union type `WorkEntry|CorrectionProposal $contribution`: requires listener to branch on type to extract title. More coupling.
- Shared interface `TitledContribution`: introduces an interface just for this; over-engineered for one use case.

---

## Decision 2: CorrectionProposal status value

**Decision**: `countPublishedByUser()` queries with raw string `'PUBLISHED'`, not an enum constant.

**Rationale**: `CorrectionProposal::$status` is a plain `string` column with no PHP enum. Using `SuggestionStatus::VALIDATED->value` for it would be wrong and misleading. FR-001 explicitly calls this out.

**Alternatives considered**: None — dictated by existing schema.

---

## Decision 3: Rank-up notification always fires (FR-011)

**Decision**: `RankUpListener` removes the `!$preference->isEnabled(NotificationType::RANK_UP)` guard entirely. `ContributionValidatedListener` moves rank detection and `RankUpEvent` dispatch _outside_ the `isEnabled(CONTRIBUTION_VALIDATED)` block.

**Rationale**: FR-011 (updated in clarifications 2026-06-05) removes any preference condition for rank-up. The existing `NotificationPreference::rankUp` field is retained in the DB and settings UI for future use but is ignored by the listener.

**Alternatives considered**:
- Keep preference check in `RankUpListener`: violates FR-011.
- Keep rank detection inside `ContributionValidatedListener`'s preference block: rank-up would silently not fire when contribution_validated preference is disabled.

---

## Decision 4: Batch rank computation for multi-user lists

**Decision**: Two aggregate queries (one for `Suggestion` VALIDATED, one for `CorrectionProposal` PUBLISHED) return `[userId => count]` maps. Ranks computed in PHP by iterating sorted levels. Single `findAllSortedByThreshold()` call per request.

**Rationale**: Single JOIN approach across two unrelated tables would require a UNION or complex subquery with no clear performance benefit over two simple aggregate queries. PHP merge is O(n) where n = visible users (small). No N+1 — queries are constant per page load.

**Alternatives considered**:
- Single SQL UNION query: more complex, harder to maintain, negligible performance gain at this scale.
- One query per user (N+1): explicitly forbidden per spec clarification.

---

## Decision 5: Rank badge CSS approach

**Decision**: New SCSS file `assets/styles/components/_rank-badge.scss` with classes `.badge-rank-1` through `.badge-rank-6`, each using `background`, `color`, and `border-color` from existing color tokens (`--parchemin-*`, `--mousse-*`, etc.). Twig macro in `templates/components/_rank_badge.html.twig`.

**Rationale**: Consistent with existing `.badge-role-admin`, `.badge-role-mod`, `.badge-status-*` patterns in the codebase. Token-based colors respect the design system.

**Token mapping** (confirmed from `assets/styles/tokens/_colors.scss`):
- Rank 1 Novice → parchemin (neutral, earthy)
- Rank 2 Apprenti → mousse (green, growth)
- Rank 3 Chroniqueur confirmé → encre (blue, knowledge)
- Rank 4 Archiviste → ambre (amber, mastery)
- Rank 5 Érudit → or (gold, excellence)
- Rank 6 Grand Sage → cuir (brand primary, apex)

**Alternatives considered**:
- Inline `style=` on badge elements: no reuse, hard to theme.
- New CSS framework utility: violates Constitution frontend rules.

---

## Decision 6: Public profile route

**Decision**: `GET /profil/{pseudo}` — public, no `#[IsGranted]`. Shows display name, rank badge (if ROLE_USER), validated count, join date. No collection/wishlist data.

**Rationale**: Simple identity page. Public access enables linking from activity feed, notifications, etc. Rank hidden for ROLE_MODERATOR/ROLE_ADMIN (FR-005). Minimal data to keep scope bounded.

**Alternatives considered**:
- Auth-required profile: less useful for linking from notifications.
- Full stats page: out of scope for this feature.

---

## Decision 7: Identity zones covered

**Confirmed zones** (all have existing server-rendered Twig templates with user identity):

| Zone | File | Strategy |
|------|------|----------|
| Profile menu | `components/Layout/ProfileMenu.html.twig` | Already wired (text title via `menuData.rankName`) |
| Suggestion dashboard banner | `suggestion/index.html.twig` | Already rendered |
| Moderation queue rows | `moderation/dashboard.html.twig` | Batch query in ModerationController, inject badge per row |
| Review/comment items | `livre/_review_item.html.twig` | Batch query in BookController, pass `ranksByUserId` map |
| Public profile | `profile/show.html.twig` | NEW — single user rank query |
| Admin users list | `admin/users.html.twig` | Batch query in AdminController, show badge for ROLE_USER rows |

**Not covered** (out of scope or not server-rendered):
- Home activity feed actors: rendered by Twig but rank would require additional batch query in DashboardService; deferred — the feed already has complex query assembly and this feature doesn't list it as a primary zone.
- Suggestion polling cards: rendered client-side in JS; would require API change; deferred.
