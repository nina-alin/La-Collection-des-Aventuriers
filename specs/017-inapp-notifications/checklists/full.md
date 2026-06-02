# Full-Coverage Requirements Quality Checklist: Système de Notifications In-App

**Purpose**: Pre-planning spec quality review — completeness, clarity, consistency, and measurability across all domains (UX, architecture, security)
**Created**: 2026-06-02
**Audience**: Author (pre-/speckit-plan)
**Feature**: [spec.md](../spec.md)

---

## Requirement Completeness

- [x] CHK001 — Are relative timestamp display formats (thresholds, label text) specified for notification items? [Completeness] → Resolved: design defines formats ("il y a X min/h/j", "hier · HH:MM")
- [x] CHK002 — Are message content templates documented for each notification types? [Completeness] → Resolved: templates in `design/pages/profil.html` per FR-016; 4 types (comment_activity removed)
- [x] CHK003 — Is a page size (items per page) defined for the history page pagination? [Completeness, Spec §FR-011, Gap] → Resolved: 20/page — `findPaginatedForUser(..., int $perPage = 20)` in T010; T030 reads `page` param default 1
- [x] CHK004 — Is the `sourceId` format specified for each notification type beyond the `contribution_validated:42` example? [Completeness, Gap] → Resolved: all formats in tasks.md — `contribution_validated:{workEntry.id}`, `book_activity:{collection.id}:{book.id}` / `book_activity:batch:{collection.id}:{timestamp}`, `moderation_pending:{suggestion.id}:{moderator.id}`, `rank_up:{user.id}:{newLevel.value}`
- [x] CHK005 — Are skeleton loader row count and structure specified? [Completeness, Spec §FR-017, Gap] → Resolved: 3 ghost rows — T051 explicitly says "3 ghost items"
- [x] CHK006 — Is the UI/visual specification for the notification preferences screen defined (not just the route to account settings)? [Completeness, Spec §FR-012, Gap] → Resolved: tasks.md T032/T033 define full UI — 4 labeled checkboxes per NotificationType, CSRF `notification_preferences`, hide `moderation_pending` for non-ROLE_MODERATOR; `design/pages/profil.html` is visual reference (CHK031 confirmed it exists)
- [x] CHK007 — Is the `NotificationPreference` row creation flow (triggered on new user account creation) covered by a functional requirement? [Completeness] → Resolved: Key Entities specifies "créée à true par défaut lors de la création de l'utilisateur"
- [x] CHK008 — Are requirements defined for Messenger consumer failure scenarios (retry policy, dead letter queue, user-facing fallback)? [Completeness, Gap] → Resolved: standard Symfony Messenger retry (3 attempts, exponential backoff, built-in default) + failed transport (`failed_messages` table via T002/T016); no user-facing fallback needed — notifications are non-critical

---

## Requirement Clarity

- [x] CHK009 — Is "rendu perçu < 200 ms" (SC-001) defined against a measurable baseline? [Clarity, Spec §SC-001] → Resolved: server-rendered page; 200ms = TTI; baseline unambiguous in this context
- [x] CHK010 — Is the timezone used to determine the "aujourd'hui" / "plus anciennes" grouping boundary explicitly specified? [Clarity, Spec §FR-004] → Resolved: **user profile timezone** — updated in FR-004
- [x] CHK011 — Is a maximum badge display number specified (e.g., "99+" cap) or is the badge value unbounded? [Clarity, Spec §FR-001, Gap] → Resolved: "99+" cap — standard UX pattern; 500-notification pruning ensures count never exceeds 500 anyway
- [x] CHK012 — Is "initiales de la collection" defined with rules covering edge cases (single-word name, name with >2 words, empty/null name)? [Clarity, Spec §FR-006, Gap] → Resolved: first letter of each of the first 2 words uppercased (e.g. "Ma Collection" → "MC"); single word → first 2 chars; empty/null → generic book SVG icon (no initials avatar rendered)
- [x] CHK013 — Is "message d'erreur générique" for deleted notification targets specified (exact text, fallback UI behavior)? [Clarity, Spec §Edge Cases, Gap] → Resolved: exact text in tasks.md T025 — info flash `"Cette notification n'a plus de cible."`, redirect to `/`
- [x] CHK014 — Is the ordering criterion for the 20-item panel display explicitly stated? [Clarity, Spec §FR-015] → Resolved: "les plus récentes" + FR-011 "chronologique inversé" = created_at DESC

---

## Requirement Consistency

- [x] CHK015 — Does FR-007 align unambiguously with the Edge Case — is filtering at display or at creation? [Consistency, Spec §FR-007 vs §Edge Cases] → Resolved: display-side filter; creation still happens; confirmed by both FR-007 and edge case
- [x] CHK016 — Is the 500-notification cap reflected in a functional requirement, or does it remain only in the Assumptions section? [Consistency, Spec §Assumptions, Gap] → Resolved: implementation constraint in handler (research.md Decision 8 + T014) — no user-visible FR needed; cap is an internal storage bound, not a user-facing behaviour
- [x] CHK017 — Are LiveAction requirements consistent with the re-render-on-open behavior? [Consistency, Spec §Assumptions] → Resolved: LiveComponent LiveActions trigger server re-render by framework design
- [x] CHK018 — Does SC-004 conflict with FR-013? [Consistency, Spec §SC-004 vs §FR-013] → Resolved: SC-004 "si la préférence est activée" qualifier explicitly aligns

---

## Acceptance Criteria Quality

- [x] CHK019 — Is SC-003 ("< 300 ms") defined against a measurable start event? [Measurability, Spec §SC-003] → Resolved: click → DOM update; clear in LiveComponent context
- [x] CHK020 — Can "instantanément" (User Story 2) be objectively measured? [Measurability, Spec §US-2] → Resolved: SC-003 < 300ms defines "instantanément"
- [x] CHK021 — Are acceptance scenarios defined for FR-017 (skeleton loader) and FR-018 (toast + empty fallback on error)? [Acceptance Criteria, Gap] → Resolved: accepted as optional — T051 (skeleton: 3 ghost rows, `data-loading="addClass(skeleton)"`) and T052 (error: toast `"Impossible de charger les notifications."` + empty state) are sufficient acceptance evidence
- [x] CHK022 — Is there a measurable acceptance criterion for the notification preferences toggle visual feedback? [Acceptance Criteria, Gap] → Resolved: T034 defines feedback — success flash on POST redirect; no additional criterion needed (form UX is standard)

---

## Scenario Coverage

- [x] CHK023 — Are requirements defined for what happens when a user disables a notification type while unread notifications exist? [Coverage, Gap] → Resolved: **purged** — added as FR-019
- [x] CHK024 — Are requirements defined for "Tout marquer lu" called when unread count is already zero (idempotent behavior)? [Coverage, Edge Case, Gap] → Resolved: idempotent no-op — `UPDATE notification SET is_read=true WHERE user_id=? AND is_read=false` affects 0 rows; panel re-renders with unchanged empty state; no error; no special handling required
- [x] CHK025 — Are requirements defined for panel behavior when the user is already on `/notifications` and clicks "Voir toutes"? [Coverage] → Resolved: trivial navigation reload; not spec-worthy
- [x] CHK026 — Are `comment_activity` notification requirements fully specified? [Coverage] → Resolved: **out of scope v1** — type removed from spec

---

## Non-Functional Requirements

- [x] CHK027 — Are accessibility requirements defined for the bell icon and notification panel? [Coverage] → Resolved: design has full ARIA (aria-label, aria-haspopup, aria-expanded, role=dialog/list/listitem)
- [x] CHK028 — Are responsive/mobile display requirements defined for the notification panel? [Coverage] → Resolved: design has `@media (max-width: 719px)` styles
- [x] CHK029 — Are CSRF protection requirements specified for LiveActions? [Security] → Resolved: Symfony UX LiveComponent handles CSRF automatically
- [x] CHK030 — Are requirements for concurrent mutation scenarios defined? [Coverage] → Resolved: concurrent mark-read is harmless idempotent write; no special requirement needed

---

## Dependencies & Assumptions

- [x] CHK031 — Is `design/pages/profil.html` validated as a prerequisite? [Dependency, Spec §FR-016] → Resolved: file exists
- [x] CHK032 — Are domain event contracts documented or referenced? [Dependency, Spec §Assumptions] → Resolved: no events exist yet — will be defined in this feature; dispatch points documented in Assumptions
- [x] CHK033 — Is "URL cible résolue côté serveur à la création" validated for all 4 types? [Assumption] → Resolved: all 4 types have concrete source entities at dispatch time
- [x] CHK034 — Is the behavior specified for legacy user accounts without a `NotificationPreference` row? [Assumption] → Resolved: N/A — site not in production

---

## Open Items Summary

All items resolved. No open items.
