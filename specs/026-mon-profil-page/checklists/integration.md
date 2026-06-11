# Integration & RGPD Compliance Checklist: Page "Mon Profil"

**Purpose**: Validate cross-feature dependency contracts and RGPD compliance requirements quality — formal PR review gate
**Created**: 2026-06-11
**Feature**: [spec.md](../spec.md)

---

## Cross-Feature Contracts (General)

- [x] CHK001 — Are interface assumptions for all consumed features (017, 019, 004) explicitly documented rather than implied? [Completeness, Gap]
  > **Resolved (partial).** 026 Assumptions section names all three features. However, "feature 017 est disponible pour les toasts" conflates 017 (notification bell panel) with the toast UI component — these may be two separate systems. Accepted as good-enough for requirements purposes; implementation will clarify.

- [x] CHK002 — Is degradation behavior defined for each consumed feature when it is unavailable or returns unexpected data? [Coverage, Gap]
  > **Resolved — spec updated (FR-011).** Gamification zero-state defined: if `ContributorLevelService` returns null or table is empty, display "Aucune donnée de rang disponible" with no progression indicator. Streak KPI displays 0 when `loginStreak` is 0 (migration default) — a valid initial state, not an error.

- [x] CHK003 — Are version or compatibility requirements stated for features 017, 019, and 004 as consumed dependencies? [Clarity, Gap]
  > **Closed — N/A.** Single-binary Symfony monolith; cross-feature versioning does not apply.

## Feature 017 — Notifications Contract

- [x] CHK004 — Are toast notification requirements (content, duration, position) specified for every 026 action that triggers one (list toggle, unfollow, account deletion)? [Completeness, Spec §FR-005, §FR-008]
  > **Resolved — spec updated (FR-005, FR-008, FR-009).** Toggle success: "Visibilité mise à jour". Unfollow success: "Désabonnement confirmé". Avatar upload success: "Avatar mis à jour". Account deletion: no toast (session ends + redirect). Duration/position: design-system default, not a requirements concern.

- [x] CHK005 — Is the distinction between success toasts and error toasts explicitly defined for each triggering action? [Clarity, Spec §FR-005]
  > **Resolved — spec updated (FR-005, FR-008, FR-009).** Toggle error: "Erreur de mise à jour — veuillez réessayer" + visual rollback of toggle state. Unfollow error: "Erreur lors du désabonnement — veuillez réessayer" + card stays visible. Avatar error: inline message in upload component (contextual, no toast). Email error: inline in modal/field (already in Edge Cases).

- [x] CHK006 — Is fallback behavior defined if the feature 017 notification system is unavailable at runtime? [Edge Case, Gap]
  > **Closed — N/A.** Toasts used in 026 (list toggle, unfollow) are client-side JS feedback components from the design system, not Notification entities from feature 017. Feature 017 creates bell-panel notifications; the 026 Assumption conflating the two is imprecise but the runtime risk is absent.

## Feature 019 — Gamification Contract

- [x] CHK007 — Are gamification rank data structures (rank names, thresholds, progression metric) documented as an explicit consumed interface, not assumed implicit knowledge? [Completeness, Spec §FR-011, Assumption]
  > **Resolved.** Fully documented in 019 spec: `ContributorLevel` entity (nom, numéro d'ordre, seuil), `ContributorLevelService::computeRank()`, exact rank matrix (Novice/0, Apprenti/5, Chroniqueur confirmé/15, Archiviste/30, Érudit/60, Grand Sage/100) via fixtures. 026 Assumptions reference this as already implemented.

- [x] CHK008 — Is the data source for the "suggestions validées" KPI (FR-003) explicitly linked to feature 019's data model, or left as an ambiguous cross-model join? [Clarity, Spec §FR-003, Ambiguity]
  > **Resolved — spec updated (FR-003).** KPI counts `Suggestion` (VALIDATED) only — excludes `CorrectionProposal`. Intentionally diverges from 019's rank counter which includes both. Numbers will differ for users who have submitted corrections; this is accepted.

- [x] CHK009 — Are requirements consistent between FR-003 (KPI: suggestions validées + taux d'acceptation) and FR-011 (rank progression consuming same metric) — same data, same count? [Consistency, Spec §FR-003, §FR-011]
  > **Resolved (pending CHK008).** Structurally consistent — both draw from the same `ContributorLevelService` counter once CHK008 is clarified. No conflicting logic once the scope of "contributions validées" is agreed.

- [x] CHK010 — Is behavior defined when gamification data is absent for a user (feature 019 not yet processed a new account)? [Edge Case, Gap]
  > **Resolved — spec updated (FR-011).** Same fix as CHK002: "Aucune donnée de rang disponible" displayed with no progression indicator when `ContributorLevelService` returns null or table is empty.

## Feature 004 — RBAC Contract

- [x] CHK011 — Is the set {ROLE_USER, ROLE_MODERATOR, ROLE_ADMIN} confirmed as exhaustive for conditional rendering in 026, or could additional roles exist? [Completeness, Spec §FR-002, §FR-011]
  > **Resolved.** Confirmed exhaustive by 004 FR-002 (role hierarchy) and FR-016 (exactly one primary role per user). No other roles exist in the system.

- [x] CHK012 — Are requirements defined for rendering behavior if an unknown or future role is present on a User? [Edge Case, Gap]
  > **Closed — N/A.** 004 FR-016 enforces exactly one role from a fixed set; unknown roles cannot exist in normal operation.

- [x] CHK013 — Is the permission list for ROLE_MODERATOR and ROLE_ADMIN explicitly enumerated in this spec, or assumed derivable from feature 004 alone? [Clarity, Spec §FR-011, Gap]
  > **Resolved — spec updated (FR-011).** ROLE_MODERATOR: defined in design (8 permissions). ROLE_ADMIN: "Niveau 3 · sur 3", title "Administrateur de la guilde", 9 permissions all granted (none denied): catalogue, notes, suggestions, validation, corrections, signalement, bannissement, gestion des rôles, paramètres d'administration.

## Login / Streak Integration

- [x] CHK014 — Is the exact authentication event that triggers the streak update identified (which login handler, which code path)? [Clarity, Spec §FR-003, Assumption]
  > **Resolved.** Requirement "à chaque authentification réussie" is a clear requirements-level statement. Implementation choice (event subscriber vs login controller) is not a requirements concern.

- [x] CHK015 — Are requirements defined for streak initialization on a user's first-ever login (loginStreak = 1, lastLoginDate = today)? [Edge Case, Gap]
  > **Resolved — spec updated.** Added to Assumptions: first login (lastLoginDate = null) → loginStreak = 1, lastLoginDate = today (UTC).

- [x] CHK016 — Is the UTC timezone assumption for streak calculation a hard requirement, and is behavior defined for users whose local midnight differs from UTC midnight? [Clarity, Spec Assumption]
  > **Resolved — spec updated (Assumptions).** Changed from "Règle UTC" to `User.timezone` with fallback UTC. Consistent with feature 017 FR-004. Original Session 2026-06-11 clarification entry annotated as revised.

- [x] CHK017 — Are data migration requirements defined for existing users whose loginStreak/lastLoginDate fields will be null after the schema migration? [Completeness, Gap]
  > **Resolved — spec updated.** Added to Assumptions: existing users get loginStreak = 0 and lastLoginDate = null after migration; streak initializes to 1 on next login.

## Cross-Feature Data Integrity

- [x] CHK018 — Is the scope of "contributions validées" in FR-003 explicitly bounded — does it cover only UserSuggestion or other contribution types from other features as well? [Clarity, Spec §FR-003, Ambiguity]
  > **Resolved — spec updated (FR-003).** Same resolution as CHK008: KPI counts `Suggestion` (VALIDATED) only.

- [x] CHK019 — Is the denominator for the "taux d'acceptation" KPI precisely defined — all submissions, or only finalized/reviewed ones? [Clarity, Spec §FR-003, Ambiguity]
  > **Resolved — spec updated (FR-003).** Denominator = `VALIDATED + REJECTED` (finalized only). `PENDING` suggestions excluded — rate reflects completed review decisions only.

- [x] CHK020 — Are requirements defined for UserSuggestion reassignment to GhostUser when those suggestions involve cross-feature relationships (e.g. linked catalog entries from other workflows)? [Completeness, Spec §FR-013]
  > **Resolved — spec updated (FR-013).** GhostUser reassignment covers both `Suggestion` (VALIDATED) and `CorrectionProposal` (PUBLISHED). FR-013 explicitly supersedes 004 FR-011's null-author approach for self-service deletion. Note: 004 FR-011 should be updated separately for consistency.

## RGPD Compliance Gates *(mandatory)*

- [x] CHK021 — Is the full enumeration of PII fields to null on soft delete complete — does it cover email, avatar, pseudonyme, AND the new token fields (pendingEmail, emailChangeToken, emailTokenExpiresAt)? [Completeness, Spec §FR-013, Gap]
  > **Resolved — spec updated (FR-013).** Strategy: `[deleted]` string for `email` and `pseudo`/`displayName` (consistent with 004 FR-011). `avatarUrl` and `googleId` nulled. `password`, `pendingEmail`, `emailChangeToken`, `emailTokenExpiresAt` cleared. `googleId` released to allow Google account re-use on a new registration.

- [x] CHK022 — Are requirements defined for handling a pending email-change request (pendingEmail + token) that exists at the moment of account deletion? [Edge Case, Spec §FR-013, Gap]
  > **Resolved — spec updated.** FR-013 updated to explicitly include clearing pendingEmail, emailChangeToken, emailTokenExpiresAt as part of the anonymization on deletion.

- [x] CHK023 — Is the legal basis for retaining the soft-deleted User row (vs. hard delete) documented in requirements or assumptions? [Compliance, Gap]
  > **Resolved — spec updated (Assumptions).** Legal basis: RGPD Art. 6(1)(f) — intérêt légitime de traçabilité des actions de modération et d'intégrité du catalogue. Row is fully anonymized (no PII remains) so retention does not conflict with Art. 17.

- [x] CHK024 — Is the GhostUser's own RGPD status defined — can it be subject to deletion requests, and are there requirements preventing accidental modification or deletion? [Completeness, Spec §FR-013, Gap]
  > **Resolved — spec updated (FR-015).** Added: GhostUser (ghost@deleted.local) must be protected from all user-facing destructive operations including RGPD deletion requests.

- [x] CHK025 — Are data retention period requirements defined for soft-deleted User rows before permanent erasure (RGPD Article 17 compliance window)? [Compliance, Gap]
  > **Resolved — spec updated (Assumptions).** Retention period: 30 days, then permanent erasure by a scheduled task. Purge task itself is out of scope for feature 026 (separate spec).

- [x] CHK026 — Is the right to data portability (RGPD Article 20 — data export before deletion) explicitly addressed or explicitly out of scope? [Coverage, Gap]
  > **Resolved — spec updated (Assumptions).** Explicitly out of scope for feature 026.

- [x] CHK027 — Are audit/logging requirements defined for the account deletion event (actor, timestamp, action) for compliance traceability? [Completeness, Gap]
  > **Resolved — spec updated (FR-016).** Deletion event logged to `moderation_log` with action = `ACCOUNT_DELETED`, target_entity_type = `User`, target_entity_id = deleted user UUID, moderator_id = null (self-initiated). Record survives the 30-day purge of the User row.

- [x] CHK028 — Are requirements defined to prevent a deleted (soft-deleted) User from authenticating again, including via OAuth re-linking with the same Google account? [Security, Coverage, Gap]
  > **Resolved.** Two-layer coverage: (1) 004 UserProvider throws UsernameNotFoundException for any user with `deleted_at` set — blocks password/email re-auth. (2) FR-013 nulls `googleId` — releases the unique constraint, so a new account can register with the same Google account cleanly. No re-auth to the deleted account is possible via any path.
