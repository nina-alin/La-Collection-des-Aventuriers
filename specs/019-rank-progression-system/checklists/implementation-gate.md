# Implementation Gate Checklist: Système de Rangs et Progression

**Purpose**: Formal implementation gate — validate requirement quality, clarity, consistency, and completeness across all domains before planning
**Created**: 2026-06-05
**Feature**: [spec.md](../spec.md)
**Depth**: Comprehensive (all domains)
**Audience**: Author + reviewer, pre-planning

Legend: ✅ PASS · ⚠️ GAP · 🔴 BLOCKING · ❓ NEEDS ANSWER

---

## Backend Service Requirements (FR-001/002/003)

- [x] CHK001 — ✅ RESOLVED: FR-001 updated to enumerate exact status values (`SuggestionStatus::VALIDATED` for Suggestion, `'PUBLISHED'` for CorrectionProposal). FR-008 updated to mandate `ContributionValidatedEvent` dispatch for both entity types. Clarifications § 2026-06-05 documents the mismatch. [Clarity, FR-001, FR-002]
- [x] CHK002 — ⚠️ GAP: Aggregation strategy not specified in requirements — FR-001 says "agréger les deux compteurs" but does not define single JOIN vs. two queries summed. Implementable without this, but ambiguous. [Clarity, FR-001]
- [x] CHK003 — ✅ PASS: Assumptions § explicitly marks fixture values as authoritative and user story values as "illustratifs uniquement". [Clarity, Assumption §]
- [x] CHK004 — ⚠️ GAP: Fixture idempotency not defined. `ContributorLevelFixture::load()` has no upsert or duplicate guard — re-running creates duplicate ranks. No requirement covers this. [Edge Case, FR-003]
- [x] CHK005 — ✅ PASS: Edge Cases § defines behavior for empty ContributorLevel table: "Aucun rang ne s'affiche ; le bandeau suggestions n'affiche pas de delta." [Coverage, Edge Case §]

## UI Badge Rendering Requirements (FR-004/005)

- [x] CHK006 — ✅ RESOLVED: FR-004 updated with full 6-color mapping against design tokens: Novice=`--parchemin`, Apprenti=`--mousse`, Chroniqueur confirmé=`--encre`, Archiviste=`--ambre`, Érudit=`--or`, Grand Sage=`--cuir`. [Conflict resolved, FR-004]
- [x] CHK007 — ⚠️ GAP: "badge coloré/stylisé" not quantified. Clarifications add "small" but no measurable visual spec (size, positioning, typography). Spec-level requirement is underspecified. [Clarity, FR-004]
- [x] CHK008 — ⚠️ GAP: N+1 prohibition (FR-004) specifies list context only — "tous les utilisateurs visibles en un seul appel DB." Public profile page (single-user context) has no query strategy defined. Likely trivial but unspecified. [Clarity, FR-004]
- [x] CHK009 — ⚠️ PARTIAL: 5 identity zones enumerated in FR-004 but no per-zone specific requirements (layout, positioning, component reuse across zones). General badge rule applies globally but zone-specific edge cases unaddressed. [Completeness, FR-004]
- [x] CHK010 — ⚠️ GAP: Fallback for DB error (not empty-table scenario) undefined. Edge Cases § covers empty table only. [Edge Case, Gap]
- [x] CHK011 — ⚠️ PARTIAL: FR-004 applies the badge rule globally to all zones including comments, but no explicit consistency statement confirming comment zones use same component and query strategy as other list zones. [Consistency, FR-004]
- [x] CHK012 — ⚠️ PARTIAL: FR-005 masking stated globally — not enumerated per zone. Sufficient for implementation but creates ambiguity for zones where FR-005 + FR-004 interact (e.g., profile menu already shows no badge for any user — only title or role badge). [Completeness, FR-005]
- [x] CHK013 — ✅ PASS: Edge Cases § explicitly covers moderator-who-is-contributor: "Son rang est calculé normalement mais non affiché (règle RBAC)." Consistent with FR-005. [Consistency, FR-005, Edge Case §]

## Dashboard Banner Requirements (FR-006/007)

- [x] CHK014 — ⚠️ GAP: Delta message format is example-only in User Story 2. FR-006 says "afficher dynamiquement le nombre de validations manquantes" but provides no formal template, no localization requirement, no punctuation spec. [Clarity, FR-006]
- [x] CHK015 — ⚠️ GAP: "rang maximal" in FR-007 not formally defined. User Story 2 example uses "Érudit" as max rank — but the fixture's highest rank is "Grand Sage". FR-007 should define max rank as "ContributorLevel with highest threshold in table" to be fixture-driven. [Clarity, FR-007] → **❓ NEEDS ANSWER** (confirm intended definition)
- [x] CHK016 — ⚠️ PARTIAL: Zero-validation case defined in User Story 2 SC3 ("delta vers le premier rang non-initial") but FR-006 does not define "premier rang non-initial" as a concept. Requires implementer inference. [Coverage, User Story 2 §SC3]
- [x] CHK017 — ⚠️ GAP: Pluralization ("1 fiche" vs. "N fiches") not addressed anywhere in spec. [Clarity, Gap]

## Notification Flow Requirements (FR-008/009/010/011)

- [x] CHK018 — ✅ RESOLVED: FR-008 updated — only final rank notified on multi-threshold jump. Assumptions § updated to remove false "un seul rang" guarantee and document the accepted behavior. [Conflict resolved, FR-008, Assumption §]
- [x] CHK019 — ⚠️ PARTIAL: Edge Cases § states "une seule notification" for concurrent same-threshold validations. Mechanism undefined. Code uses `sourceId: 'rank_up:{userId}:{rankNumber}'` — deduplication depends on whether a unique constraint exists on `sourceId` in the Notification table, which is not specified. [Coverage, Edge Case §, SC-004]
- [x] CHK020 — ⚠️ GAP: Notification message format not formally specified. User Story 3 example: "Félicitations — tu viens d'atteindre le rang Chroniqueur." Actual code: `sprintf('Félicitations, tu as atteint le niveau %s !', ...)` — wording differs from spec example. No formal template in requirements. [Clarity, FR-009]
- [x] CHK021 — ⚠️ GAP: No requirement defines behavior when notification creation fails — whether validation is rolled back or completes independently. [Coverage, Exception Flow, Gap]
- [x] CHK022 — ✅ PASS: `NotificationPreference.rankUp` toggle exists in `templates/profile/_notification_preferences.html.twig` rendered in profile settings. UI surface is implemented (feature 017 scope). [Completeness, FR-011]

## Requirement Conflicts — BLOCKING

- [x] CHK023 — ✅ RESOLVED: FR-011 removed. Rank-up notification is always generated — no preference condition. SC-004 correct as written. SC-005 updated to remove preference clause. [Conflict resolved, SC-004, FR-011]
- [x] CHK024 — ✅ RESOLVED: Same root as CHK018. Assumptions § updated — false guarantee removed, accepted behavior (final rank only) documented. [Conflict resolved, Assumption §, FR-008]
- [x] CHK025 — ✅ RESOLVED: Assumptions § updated to explicitly state user story names AND thresholds are illustrative, and fixture values are authoritative (both names and thresholds). Name discrepancy acknowledged. [Conflict resolved, User Story §, Assumption §]
- [x] CHK026 — ✅ RESOLVED: FR-008 rewritten to explicitly require `ContributionValidatedEvent` dispatch for both Suggestion and CorrectionProposal via `ModerationService::approve()`. Scope note added to Assumptions §. [Conflict resolved, FR-001, FR-008]

## Scenario Coverage

- [x] CHK027 — ✅ RESOLVED: Same as CHK026. FR-008 now covers CorrectionProposal. [Coverage, FR-001, FR-008]
- [x] CHK028 — ✅ PASS: Regression scenario covered in Edge Cases §: "Le compteur descend mais aucune notification de régression n'est envoyée — l'affichage du rang se met simplement à jour." [Coverage, Edge Case §]
- [x] CHK029 — ⚠️ PARTIAL: No-op scenario covered in User Story 3 SC2 but not explicitly captured in FR-008 text. FR-008 implies no-op by only specifying action on threshold crossing, but the negative case is absent from functional requirements. [Completeness, FR-008]
- [x] CHK030 — ✅ PASS: First-time rank achievement (0→Apprenti) follows identical logic to subsequent rank-ups. Novice is default at 0 — no achievement event needed. Spec is silent but consistent. [Coverage]

## Non-Functional Requirements

- [x] CHK031 — ✅ PASS: N+1 prohibition quantified as "une requête agrégée unique (JOIN ou sous-requête) pour récupérer les compteurs de tous les utilisateurs visibles en un seul appel DB." Unit = list render. Sufficient. [Clarity, FR-004]
- [x] CHK032 — ⚠️ GAP: No performance requirements for on-the-fly rank computation under load (contributors list, 100+ users). Absence may be intentional given N+1 prohibition covers the main risk. [Gap, NFR]
- [x] CHK033 — ⚠️ GAP: No accessibility requirements for rank badges (ARIA labels, color-not-sole-indicator). [Gap, NFR]

## Dependencies & Assumptions

- [x] CHK034 — ✅ PASS: Feature 017 confirmed merged in git history (commit 3732de1). Notification infrastructure (`NotificationType::RANK_UP`, `NotificationPreference.rankUp`, `RankUpListener`, templates) all present in codebase. [Assumption §, Dependency]
- [x] CHK035 — ✅ PASS: All listed backend files confirmed present: `ContributorLevel.php`, `ContributorLevelRepository.php`, `ContributorLevelService.php`, `ContributorLevelFixture.php`, `ContributionValidatedListener.php`, `RankUpListener.php`, `NotificationType::RANK_UP`, `NotificationPreference.rankUp`. [Completeness, Assumption §]
- [x] CHK036 — ✅ PASS: FR-004 includes comment zones. Clarifications § resolves earlier ambiguity — comments UI exists, rank badge IS in scope. Consistent. [Consistency, FR-004, Clarifications §]
