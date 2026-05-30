# Pre-Plan Specification Quality Checklist: Système de Notation et Commentaires

**Purpose**: Author self-review — validate specification completeness, clarity, and coverage before proceeding to `/speckit-plan`
**Created**: 2026-05-27
**Reviewed**: 2026-05-30
**Feature**: [spec.md](../spec.md)
**Scope**: Full coverage — UX/interaction, data model, real-time updates, exception recovery
**Audience**: Spec author, before planning phase

## Requirement Completeness

- [x] CHK001 — Are the ordering criteria for "4 derniers utilisateurs" (FR-006) explicitly specified — most recent by `createdAt` or `updatedAt`? If a user updates their review (upsert), do they move to the "most recent" slot? → `updatedAt` DESC. Upsert moves user to front. Added to FR-006.
- [x] CHK002 — Are moderation/admin permissions to delete other users' reviews specified, or is deletion limited to the review author only? → FR-019 added: modérateur + admin can delete any review.
- [x] CHK003 — Are requirements defined for visual differentiation between "create new review" and "edit existing review" form states beyond the pre-fill behavior of FR-017? → FR-017 updated: "Publier" vs "Modifier" button + delete button in edit state only.
- [x] CHK004 — Is the derivation logic for user initials formalized as a functional requirement, or left only in Assumptions? → FR-020 added: prénom[0]+nom[0] uppercase, fallback = fixed placeholder image.
- [x] CHK005 — Are pagination sort requirements specified per filter? → All filters `updatedAt` DESC. Added to FR-008 and FR-016.
- [x] CHK006 — Is there a requirement covering admin/moderator ability to suppress or moderate published reviews? → FR-019 added (covered with CHK002).

## Requirement Clarity

- [x] CHK007 — Is the term "active" in FR-003 clarified — does it imply soft-delete? → "Active" removed from FR-003. No soft-delete on Review entity.
- [x] CHK008 — Is the rounding rule for average score in FR-004 specified? → Standard mathematical rounding added to FR-004 and SC-001 acceptance scenario.
- [x] CHK009 — Is "proportionnelle" in FR-007 quantified? → Linear scale confirmed: height = count / max_count × 100%. Added to FR-007.
- [x] CHK010 — Is "immédiatement" in SC-002 quantified with a latency threshold? → Intentional perceived-immediacy. No numeric SLA. SC-002 updated to reflect this.
- [x] CHK011 — Is "reflète fidèlement" in SC-006 measurable? → Guaranteed by Turbo Stream synchronous updates. No staleness budget needed. SC-006 updated.
- [x] CHK012 — Is the definition of a review "AVEC COMMENTAIRE" specified — is an empty string treated as no comment? → Empty string = NULL = no comment. Added to FR-002 and FR-008.
- [x] CHK013 — Is "date relative" formatting schema defined or left to implementation? → Left to implementation. No spec change required.

## Requirement Consistency

- [x] CHK014 — Are the Turbo Stream targets after submission consistent across all FRs? → Submission = 4 targets (stats header + histogram + review list + form). Clarification updated. FR-018 and US1 scenario 1 updated.
- [x] CHK015 — Does the histogram update requirement after deletion (FR-018) match FR-007? → Consistent. No change needed.
- [x] CHK016 — Is the moderator/admin badge requirement consistent across FR-009 and US3? → Consistent. Exhaustive role list added: modérateur + admin only.
- [x] CHK017 — Are FR-003 (upsert) and FR-018 (delete + form reset) mutually consistent for delete then resubmit? → Consistent. No change needed.

## Acceptance Criteria Quality

- [x] CHK018 — Is SC-001 ("en moins de 60 secondes") a UX completion time or a system response time — is it testable without user-study methodology? → Kept as UX goal (non-gating). SC-001 annotated accordingly.
- [x] CHK019 — Can SC-006 be objectively verified — is there a staleness budget? → Guaranteed by architecture. SC-006 updated.
- [x] CHK020 — Are success criteria defined for the delete flow (FR-018)? → SC-007 added: Turbo Stream updates 4 targets without full reload.
- [x] CHK021 — Is there a measurable acceptance criterion for pre-fill (FR-017)? → Covered by FR-017 as functional requirement. No SC needed.

## Scenario Coverage

- [x] CHK022 — Is there an acceptance scenario covering pagination boundary behavior when filtered results ≤ 10? → Pagination controls hidden. Added to FR-016.
- [x] CHK023 — Is there a scenario for what the list shows when a filter returns zero results? → "Aucune évaluation pour l'instant". Added to FR-008 and US3 scenario 3.
- [x] CHK024 — Is there a scenario covering badge display after moderator deletes their own review? → Handled by Turbo Stream review list update. No additional requirement.
- [x] CHK025 — Is there a requirement that filter param persists in pagination links? → Added to FR-008. US3 scenario 5 added.
- [x] CHK026 — Is there an acceptance scenario for the 0→1 review transition via Turbo Stream? → US2 scenario 4 added.

## Edge Case Coverage

- [x] CHK027 — Is the behavior defined when page N becomes empty after a deletion? → Page N renders with "Aucune évaluation pour l'instant". Added to FR-016 and Edge Cases.
- [x] CHK028 — Is the behavior defined for empty string vs NULL in "AVEC COMMENTAIRE"? → Empty string = NULL = excluded. Added to FR-002 and FR-008.
- [x] CHK029 — Are requirements defined for incomplete user profile (no prénom/nom)? → Fixed placeholder image. Added to FR-020.
- [x] CHK030 — Is the behavior specified for simultaneous submissions from two different users to a 0-review book? → Acceptable eventual consistency. No spec change needed.

## Non-Functional Requirements

- [x] CHK031 — Are keyboard navigation requirements defined for the shield selector? → FR-021 added: arrow keys, Enter/Space.
- [x] CHK032 — Are ARIA requirements specified for the shield selector? → FR-021 added: `role="radiogroup"` + `aria-label` per shield.
- [x] CHK033 — Are responsive/mobile layout requirements specified beyond "fidèle au design source"? → Defined by `design/pages/livre.html` (finalized). No additional spec requirement.
- [x] CHK034 — Is CSRF protection mentioned as a security requirement? → Handled by Symfony built-in. Added to Assumptions.
- [x] CHK035 — Is rate limiting specified? → Explicitly out of scope. Added to Out of Scope section.
- [x] CHK036 — Is timezone handling for relative dates specified? → Browser timezone. Added to FR-009.

## Exception & Recovery Requirements

- [x] CHK037 — Are requirements defined for Turbo Stream response failure after submission? → FR-022 added: form state preserved + flash error.
- [x] CHK038 — Is the behavior specified when review deletion request fails? → FR-018 and FR-022 updated: form state preserved + flash error.
- [x] CHK039 — Are requirements defined for session expiry mid-form-fill? → FR-014 updated: redirect to login on expired session submission.
- [x] CHK040 — Is the behavior specified when DB uniqueness constraint is violated at persistence layer? → 409 Conflict + error message. FR-003 updated, Edge Cases updated.
- [x] CHK041 — Are requirements defined for partial Turbo Stream update failure? → Acceptable risk — full page refresh resolves. No spec change needed.

## Dependencies & Assumptions

- [x] CHK042 — Is `design/pages/livre.html` stable and finalized? → Confirmed finalized. Assumptions updated.
- [x] CHK043 — Is cascade delete behavior formally documented as FR or only as assumption? → Kept in Key Entities (sufficient for planning).
- [x] CHK044 — Is the out-of-scope boundary for "Sidebar Contributeurs" in an explicit out-of-scope section? → Out of Scope section added.
- [x] CHK045 — Are there requirements covering what happens to reviews if User is soft-deleted vs hard-deleted? → User deletion = anonymize reviews (author → NULL). Key Entities and FR updated.
