# RBAC Requirements Quality Checklist: Roles & Permissions

**Purpose**: Deep requirements quality review — security, data integrity, workflow, and user management
**Created**: 2026-05-24
**Feature**: [spec.md](../spec.md)
**Audience**: Author self-review before /speckit-plan
**Depth**: Thorough

---

## Security & Access Control Requirements

- [x] CHK001 — Are route protection requirements enumerated for all protected route categories (moderation, admin, `/admin/settings`)? [Completeness, Spec §FR-006, §FR-013] ✓ FR-006 (moderation), FR-009 (admin user mgmt), FR-013 (/admin/settings) cover all three categories
- [x] CHK002 — Is the 403 response format specified — body content, headers, or redirect behavior on a 403? [Clarity, Spec §FR-003] ✓ Framework default; 403 vs redirect distinction explicit in FR-003/FR-004; format is implementation detail
- [x] CHK003 — Is the distinction between a 403 (authenticated but unauthorized) and a redirect-to-login (unauthenticated) response explicitly documented? [Clarity, Spec §FR-003, §FR-004] ✓ FR-003 (403) and FR-004 (redirect) are separate requirements
- [x] CHK004 — Is multi-hop role inheritance (ROLE_ADMIN → ROLE_MODERATOR → ROLE_USER) specified with all inherited permissions enumerated, not just the chain? [Clarity, Spec §FR-002] ✓ Inheritance chain in FR-002; inherited permissions = full set of lower role's permissions by definition
- [x] CHK005 — Are requirements for the banned-user enforcement mechanism scoped to what the system must guarantee, or do they describe an implementation approach that could change? [Ambiguity, Spec §Edge Cases] ✓ FR-010 states the guarantee ("preventing future authentication"); implementation in Clarifications
- [x] CHK006 — Is the session-refresh behavior (roles take effect on the next request) expressed as a requirement or as an implementation note? If the latter, should it be a testable requirement? [Ambiguity, Spec §SC-004] ✓ SC-004 makes it a measurable outcome
- [x] CHK007 — Are requirements defined for the race condition where a ban/role-change is issued while the user is processing a concurrent request? [Coverage, Edge Case] ✓ "Next request" model means in-flight request completes; ban takes effect on subsequent request — documented in Edge Cases
- [x] CHK008 — Is the `/admin/settings` stub response fully specified — HTTP status code, response body, and whether it differs between authenticated-and-unauthorized vs. unauthenticated callers? [Clarity, Spec §FR-013] ✓ HTTP 200 + placeholder JSON for authorized; 403/redirect handled by FR-003/FR-004
- [x] CHK009 — Are security requirements in the Edge Cases section consistent with and non-contradicting FR-003, FR-004, FR-010, and FR-014? [Consistency, Spec §FR-003, §FR-010, §FR-014, §Edge Cases] ✓ Edge cases reference FRs; no contradictions detected

---

## Data Integrity & Submission Workflow Requirements

- [x] CHK010 — Is the PENDING enforcement requirement specified for every submission entry point (both WorkEntry creation and CorrectionProposal creation), not just generic "submissions"? [Completeness, Spec §FR-005] ✓ FR-005 names both entity types explicitly
- [x] CHK011 — Is "ignoring any status value in the request payload" sufficient as a requirement, or does it need to specify at which layer enforcement occurs (e.g., domain model vs. API layer)? [Clarity, Spec §FR-005] ✓ Layer is implementation detail; requirement correctly states the outcome guarantee
- [x] CHK012 — Are all allowed status transitions explicitly enumerated (e.g., PENDING→PUBLISHED, PENDING→REJECTED) and are disallowed transitions documented? [Completeness, Gap] ✓ Added to Key Entities §WorkEntry/§CorrectionProposal
- [x] CHK013 — Is REJECTED as a terminal state sufficient — or should requirements define whether an author is notified and whether they can view the rejection reason? [Coverage, Spec §FR-007] ✓ Notification explicitly out of scope (separate spec); terminal state is sufficient for this feature
- [x] CHK014 — Are requirements for what constitutes a "MODIFIED" moderation action distinct and unambiguous — what specific change triggers a MODIFIED log entry vs. an APPROVED or REJECTED? [Ambiguity, Spec §FR-007, §Key Entities §ModerationLog] ✓ Defined in FR-007 and §ModerationLog
- [x] CHK015 — Is the optional rejection reason field constrained — maximum length, nullable vs. empty-string storage, and whether it is displayed to the submitting user? [Clarity, Spec §FR-007] ✓ Nullable specified; display out of scope (notification spec)
- [x] CHK016 — Are ModerationLog immutability requirements specified — is there a requirement that log records cannot be edited or deleted post-creation? [Completeness, Spec §Key Entities §ModerationLog] ✓ Explicit in §ModerationLog
- [x] CHK017 — Are requirements defined for ModerationLog entries that reference a soft-deleted moderator or a deleted target entity — orphaned log record behavior? [Coverage, Edge Case, Gap] ✓ Orphaned records retained as-is
- [x] CHK018 — Is concurrent moderation (last-write-wins) documented as a deliberate, accepted requirement or merely as an implementation decision not subject to product review? [Clarity, Spec §Edge Cases] ✓ Documented as deliberate decision in Edge Cases and Clarifications with explicit rationale

---

## User Management Requirements

- [x] CHK019 — Does FR-012 (prevent zero active administrators) cover all paths that reduce admin count: soft-delete, ban, and role demotion? [Completeness, Spec §FR-012] ✓ FR-012 updated to enumerate all three paths
- [x] CHK020 — Is "active administrator" defined — does it exclude banned admins, soft-deleted admins, or both? [Clarity, Spec §FR-012] ✓ FR-012 now defines "active administrator" as ROLE_ADMIN, not banned, not soft-deleted
- [x] CHK021 — Are the soft-delete anonymization requirements complete — which specific fields are anonymized, what placeholder values replace them, and is the email address cleared or replaced with a token? [Clarity, Spec §FR-011] ✓ `email` + `display_name` → `[deleted]` specified in FR-011 and §User
- [x] CHK022 — Is the author-reference nullification requirement (WorkEntry, CorrectionProposal on soft-delete) specified for all authored content types — are there other entity types that may reference User? [Completeness, Spec §FR-011] ✓ FR-011 now explicitly states these are the only two content types referencing User in this feature's scope
- [x] CHK023 — Is the self-action prevention requirement (FR-014) consistent across banning and soft-deletion — does it also apply to role demotion of one's own account? [Consistency, Spec §FR-014, §Edge Cases] ✓ FR-014 and SC-007 extended to cover self-demotion; Edge Cases updated
- [x] CHK024 — Are requirements defined for what happens to in-progress or queued actions by a moderator whose account is subsequently banned mid-session? [Coverage, Gap] ✓ Edge Cases: in-flight request completes; ban takes effect on next request; no retroactive rollback
- [x] CHK025 — Is "immediately gains moderator access after promotion" measurable — is "immediately" defined in terms of session behavior (next request, no re-authentication needed)? [Clarity, Spec §US-5, §SC-004] ✓ SC-004 defines "very next request" as the measurable boundary
- [x] CHK026 — Are requirements defined for the scenario where the last ROLE_MODERATOR is demoted to ROLE_USER — is this allowed or guarded? [Coverage, Edge Case, Gap] ✓ FR-015 added; edge case documented

---

## Acceptance Criteria Quality

- [x] CHK027 — Can SC-001 ("100% of attempts by ROLE_USER result in 403") be verified without knowing all existing and future protected routes — is the scope bounded? [Measurability, Spec §SC-001] ✓ Scope bounded to routes defined in this spec (FR-006 moderation dashboard, FR-009 admin user mgmt, FR-013 /admin/settings)
- [x] CHK028 — Can SC-003 ("navigation renders correctly for all three role levels on every page visit") be objectively measured — what constitutes "correctly" and which pages are in scope? [Ambiguity, Spec §SC-003] ✓ SC-003 now enumerates exact nav items per role
- [x] CHK029 — Is SC-004 measurable independently — can "takes effect on the very next request" be verified without a live application, or does it implicitly require an integration test definition? [Measurability, Spec §SC-004] ✓ Integration test required; SC-004 states the observable boundary ("very next request") which is testable
- [x] CHK030 — Are success criteria defined for FR-012 (last-admin guard) and FR-014 (self-action prevention) — no SC currently covers these? [Gap, Spec §SC] ✓ SC-006 and SC-007 added

---

## Non-Functional Requirements

- [x] CHK031 — Are performance requirements defined for the security event subscriber (executes on every authenticated request) — acceptable latency overhead? [Gap, NFR] ✓ Covered by existing assumption: "no special performance requirements beyond standard web page load times"
- [x] CHK032 — Are audit/observability requirements specified for unauthorized access attempts — are 403 events required to be logged or monitored? [Gap, NFR] ✓ Explicitly out of scope; deferred to separate observability specification (Assumptions)
- [x] CHK033 — Are retention or access-control requirements defined for the ModerationLog — who can query it, for how long is it retained, is it in scope for this feature? [Completeness, Gap] ✓ Explicitly out of scope; table is append-only within this feature's scope (Assumptions)
- [x] CHK034 — Are accessibility requirements specified for conditionally rendered navigation elements (role-gated links hidden or shown)? [Coverage, Gap] ✓ Explicitly out of scope; deferred to dedicated accessibility specification (Assumptions)

---

## Dependencies & Assumptions

- [x] CHK035 — Is the assumption that OAuth2 auth (spec 002) is fully operational documented as a hard dependency — what is the failure mode if it is not? [Assumption, Spec §Assumptions] ✓ Failure mode added: all protected routes fail closed when auth system unavailable
- [x] CHK036 — Is the assumption that email notifications are out of scope explicitly bounded — does it leave a gap in the rejection UX requirements (user never learns why they were rejected)? [Assumption, Spec §Assumptions] ✓ Bounded in Assumptions; acknowledged gap — user has no notification path in this feature (deferred to notification spec)
- [x] CHK037 — Is the assumption that a user holds exactly one primary role at a time sufficient — are there edge cases where the json `roles` column could contain multiple non-hierarchical values? [Ambiguity, Spec §Assumptions, §Key Entities §User] ✓ FR-016 added: role assignment replaces entire array; single-role invariant is now a requirement

---

## Notes

- Items marked `[Gap]` indicate requirements that appear to be missing from the spec entirely.
- Items marked `[Ambiguity]` indicate requirements that exist but need clarification.
- Items marked `[Consistency]` indicate requirements that may conflict with each other.
- Resolve before running `/speckit-plan` to avoid gaps propagating into the implementation plan.
