# Feature Specification: RBAC — Roles & Permissions

**Feature Branch**: `004-rbac-roles-permissions`

**Created**: 2026-05-24

**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Role-Based Access Enforcement (Priority: P1)

Standard user attempts to access the moderation dashboard. System blocks the request and returns an access-denied response. Moderator and administrator navigate to the same URL and gain access without friction.

**Why this priority**: Core security guarantee. All other stories depend on this boundary being enforced.

**Independent Test**: Create three test accounts (ROLE_USER, ROLE_MODERATOR, ROLE_ADMIN). Hit a moderation URL with each. Verify ROLE_USER is blocked, the other two succeed.

**Acceptance Scenarios**:

1. **Given** a logged-in user with ROLE_USER, **When** they navigate to a moderation route, **Then** they receive an Access Denied (403) response and are not shown any moderation content.
2. **Given** a logged-in user with ROLE_MODERATOR, **When** they navigate to a moderation route, **Then** they access it successfully.
3. **Given** a logged-in user with ROLE_ADMIN, **When** they navigate to any moderation or admin route, **Then** they access it successfully (role hierarchy inherited).
4. **Given** an unauthenticated visitor, **When** they navigate to any protected route, **Then** they are redirected to the login page.

---

### User Story 2 — Conditional Navigation Rendering (Priority: P2)

Navigation menu adapts to the connected user's role. Standard users see only personal collection links. Moderators see an additional moderation dashboard link. Administrators see moderation and administration links.

**Why this priority**: Prevents confusion and reduces accidental 403 encounters; directly impacts perceived quality of the application.

**Independent Test**: Log in with each role type and inspect the navigation bar. Verify correct items are shown or hidden per role.

**Acceptance Scenarios**:

1. **Given** a ROLE_USER session, **When** the navigation renders, **Then** the moderation dashboard link is absent.
2. **Given** a ROLE_MODERATOR session, **When** the navigation renders, **Then** the moderation dashboard link is visible and functional.
3. **Given** a ROLE_ADMIN session, **When** the navigation renders, **Then** both the moderation dashboard link and administration links are visible.

---

### User Story 3 — Forced PENDING Status on User Submissions (Priority: P2)

When a standard user submits a new work entry or proposes a correction, the submission is automatically set to PENDING status regardless of any payload manipulation. The change cannot be published directly by the submitting user.

**Why this priority**: Data integrity protection. Prevents bypassing the moderation workflow, which is the foundation of the collaborative database quality guarantee.

**Independent Test**: As ROLE_USER, POST to `/work-entries` with a valid title (including an attempt with `status: PUBLISHED` in the request body). Verify the saved WorkEntry has `status = PENDING` in the database. Also POST to `/work-entries/{id}/corrections` with valid `proposedContent`. Verify the CorrectionProposal has `status = PENDING`.

**Acceptance Scenarios**:

1. **Given** a ROLE_USER submitting a new work entry, **When** the form is submitted, **Then** the entry is saved with status PENDING regardless of any status value in the request.
2. **Given** a ROLE_USER submitting a correction proposal, **When** the form is submitted, **Then** the correction is saved with status PENDING.
3. **Given** a ROLE_MODERATOR reviewing a PENDING entry, **When** they approve it, **Then** the status transitions to PUBLISHED.

---

### User Story 4 — Moderation Dashboard (Priority: P2)

Moderators access a dedicated dashboard listing all PENDING submissions. They can read, approve (set to PUBLISHED), modify, or reject each entry. Actions are logged to a `moderation_log` DB table recording: moderator ID, action type (APPROVED/REJECTED/MODIFIED), target entity ID and type, and timestamp.

**Why this priority**: Delivers the core editorial workflow that gives ROLE_MODERATOR its purpose.

**Independent Test**: Log in as ROLE_MODERATOR, open the moderation dashboard, approve one entry and reject another. Verify status changes persist.

**Acceptance Scenarios**:

1. **Given** a ROLE_MODERATOR on the moderation dashboard, **When** the page loads, **Then** all PENDING submissions are listed with author and date.
2. **Given** a ROLE_MODERATOR viewing a PENDING entry, **When** they approve it, **Then** its status changes to PUBLISHED and is removed from the PENDING queue.
3. **Given** a ROLE_MODERATOR viewing a PENDING entry, **When** they reject it, **Then** its status changes to REJECTED and the submitting user can be notified.

---

### User Story 5 — Administrator User Management (Priority: P3)

Administrators manage user accounts: promote a standard user to moderator, ban a user, or delete an account.

**Why this priority**: Administrative capability is important but does not block the core moderation flow.

**Independent Test**: Log in as ROLE_ADMIN, promote a ROLE_USER to ROLE_MODERATOR. Verify the promoted user can now access moderation routes. Then ban a different user and verify they cannot log in.

**Acceptance Scenarios**:

1. **Given** a ROLE_ADMIN on the user management interface, **When** they promote a user to ROLE_MODERATOR, **Then** that user immediately gains moderator access.
2. **Given** a ROLE_ADMIN, **When** they ban a user, **Then** that user's account is locked and they cannot authenticate.
3. **Given** a ROLE_ADMIN, **When** they delete a user account, **Then** the account and associated personal data are removed.

---

### Edge Cases

- What happens when a user's role is changed while they have an active session? The new permissions take effect on the very next request. Mechanism: Symfony's `UserProvider::refreshUser` re-fetches the user entity from the database on every authenticated request, so no logout/login cycle is required.
- What happens if a ROLE_USER sends a direct POST request with `status: PUBLISHED` in the body? The application enforces PENDING regardless of the submitted value.
- What happens when two moderators act on the same PENDING entry concurrently? Last write wins; no locking mechanism applied given low expected moderation volume.
- What happens when the last administrator account is targeted for deletion or demotion? The system must prevent leaving the application without at least one active administrator.
- What happens when an administrator targets their own account for ban, deletion, or role demotion? The system blocks the action and returns an error; another administrator must perform it (FR-014).
- What happens to a moderator's in-flight request when their account is banned mid-session? The security event subscriber performs a DB status check on each authenticated request; the moderator's very next request is denied. Any action already committed to the database before the ban takes effect is not rolled back — the ban has no retroactive effect on completed actions.
- What happens when an administrator tries to demote the last ROLE_MODERATOR? If no ROLE_ADMIN accounts exist, the system blocks the demotion and returns an error (FR-015). If at least one ROLE_ADMIN exists, the demotion is permitted — administrators inherit moderation capability via role hierarchy.
- What happens when a banned user's session is still active at ban time? Their next request must be rejected. Enforcement mechanism: a Symfony security event subscriber performs a database status check on every authenticated request; if `status = banned`, the request is denied immediately without requiring session invalidation or a token blacklist.
- What happens when a soft-deleted user tries to authenticate? The `UserProvider` excludes soft-deleted users (throws `UsernameNotFoundException`); they cannot reach the banned-user subscriber. Banned = active account locked; deleted = account no longer exists.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST assign ROLE_USER to every new account created via registration.
- **FR-002**: System MUST define a role hierarchy where ROLE_ADMIN inherits ROLE_MODERATOR, and ROLE_MODERATOR inherits ROLE_USER.
- **FR-003**: System MUST return HTTP 403 for any authenticated user attempting to access a route above their role level.
- **FR-004**: System MUST redirect unauthenticated users attempting to access protected routes to the login page.
- **FR-005**: System MUST force all work-entry submissions and correction proposals created by ROLE_USER to status PENDING, ignoring any status value in the request payload.
- **FR-006**: System MUST expose a moderation dashboard accessible only to ROLE_MODERATOR and above.
- **FR-007**: Moderators MUST be able to transition a PENDING submission to PUBLISHED or REJECTED. On rejection, moderators MAY provide an optional free-text reason; the reason is stored as a nullable field in the `moderation_log` record (null when not provided). If a moderator edits the content of a pending entry before or without a status transition, a MODIFIED entry MUST be logged in `moderation_log` in addition to any subsequent APPROVED or REJECTED entry.
- **FR-008**: Navigation interface MUST conditionally show or hide moderation and administration links based on the connected user's role.
- **FR-009**: Administrators MUST be able to change the role of any other user (promote to ROLE_MODERATOR, demote to ROLE_USER).
- **FR-010**: Administrators MUST be able to ban a user account (preventing future authentication).
- **FR-011**: Administrators MUST be able to delete a user account. Deletion is a soft delete: the `email` and `display_name` fields are replaced with the literal string `[deleted]` and a `deleted_at` timestamp is set; the row is retained for audit trail purposes. WorkEntry and CorrectionProposal are the only content entity types that reference User in this feature's scope; all records authored by the deleted user retain their content but have their author reference set to null. No cascade deletion of content occurs.
- **FR-012**: System MUST prevent any action (ban, soft-delete, or role demotion) that would result in zero active administrator accounts. For the purposes of this requirement, an "active administrator" is a ROLE_ADMIN account that is neither banned nor soft-deleted.
- **FR-014**: System MUST prevent an administrator from banning, soft-deleting, or demoting their own account. The operation returns an error; another administrator must perform the action.
- **FR-016**: System MUST ensure that each user account holds exactly one primary role at any time. Role assignment always replaces the entire roles array; the `json` column MUST never contain more than one non-hierarchical role value simultaneously.
- **FR-015**: System MUST prevent role demotion that would result in zero accounts with moderation capability. The guard fires only when demoting the last account holding exactly ROLE_MODERATOR and no ROLE_ADMIN accounts exist; if at least one ROLE_ADMIN exists, the demotion is permitted because administrators inherit moderation capability.
- **FR-013**: System MUST protect a `/admin/settings` route accessible only to ROLE_ADMIN. For authorized callers, the route returns HTTP 200 with a placeholder JSON body (e.g. `{"message": "Settings UI coming soon"}`). Actual settings UI is out of scope and defined in a separate spec.
- **FR-017**: System MUST expose a `POST /work-entries` route accessible to any fully authenticated user (ROLE_USER and above). Request body MUST include `title` (string, required). The created WorkEntry is always persisted with `status = PENDING`; any `status` value in the request payload is silently ignored (see FR-005). On success, the route redirects to the work entries listing. The route MUST be protected by a CSRF token and `#[IsGranted('IS_AUTHENTICATED_FULLY')]`.
- **FR-018**: System MUST expose a `POST /work-entries/{id}/corrections` route accessible to any fully authenticated user (ROLE_USER and above). The `{id}` path parameter identifies the target WorkEntry. Request body MUST include `proposedContent` (text, required). The created CorrectionProposal is always persisted with `status = PENDING`; any `status` value in the request payload is silently ignored (see FR-005). On success, the route redirects to the work entry detail page. The route MUST be protected by a CSRF token and `#[IsGranted('IS_AUTHENTICATED_FULLY')]`.

### Key Entities

- **User**: An authenticated account with a role (ROLE_USER, ROLE_MODERATOR, ROLE_ADMIN), a status (active, banned), an email address, and a nullable `deleted_at` timestamp. Banned users are blocked by a security event subscriber. Soft-deleted users (non-null `deleted_at`) have their `email` and `display_name` replaced with the literal string `[deleted]` and are excluded by the `UserProvider` before authentication can proceed.
- **WorkEntry**: A catalogued item in the collaborative database with a publication status (PENDING, PUBLISHED, REJECTED) and an author reference. Allowed transitions: PENDING → PUBLISHED (moderator approval), PENDING → REJECTED (moderator rejection). PUBLISHED and REJECTED are terminal states; no further transitions are permitted and a new submission must be created to try again.
- **CorrectionProposal**: A user-submitted correction to an existing WorkEntry, also carrying a PENDING/PUBLISHED/REJECTED lifecycle. Allowed transitions mirror WorkEntry: PENDING → PUBLISHED, PENDING → REJECTED. REJECTED is terminal.
- **ModerationLog**: Immutable, append-only audit record. No record may be edited or deleted post-creation. Stores: moderator ID, action type (APPROVED / REJECTED / MODIFIED), `target_entity_type` (string, e.g. `"WorkEntry"`), `target_entity_id` (string, no FK constraint), timestamp, and a nullable free-text `reason` field (null when not provided; populated on REJECTED actions). A MODIFIED entry is created when a moderator edits the content of a pending entity, regardless of whether a status transition follows; if a subsequent APPROVED or REJECTED action occurs, a separate entry is created for that action. Orphaned log records (where the moderator account or target entity is later deleted) are retained as-is; `target_entity_type` and `target_entity_id` preserve the reference for audit purposes. No polymorphic ORM association; referential integrity not required.
- **Role Hierarchy**: Configuration mapping ROLE_ADMIN → ROLE_MODERATOR → ROLE_USER for access inheritance.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of attempts by ROLE_USER to reach moderation or administration routes result in a 403 response — zero unauthorized accesses.
- **SC-002**: 100% of work-entry and correction submissions by ROLE_USER are stored with status PENDING — zero direct PUBLISHED or REJECTED submissions. Verified by posting to `POST /work-entries` and `POST /work-entries/{id}/corrections` with a manipulated `status` payload and asserting the persisted status is always `PENDING`.
- **SC-003**: Navigation renders correctly for all three role levels on every page visit — no role-inappropriate menu items displayed. Expected items per role: ROLE_USER sees personal collection links only; ROLE_MODERATOR sees collection links plus the moderation dashboard link; ROLE_ADMIN sees collection links, the moderation dashboard link, and administration links.
- **SC-004**: Role promotion by an administrator takes effect on the very next request — no logout/login required. Enforced via Symfony `UserProvider::refreshUser` re-fetching user roles from DB on each authenticated request.
- **SC-005**: Moderation dashboard lists all PENDING submissions with no omissions — moderators can review the full queue at a glance.
- **SC-006**: 100% of attempts to perform any action (ban, soft-delete, role demotion) that would remove the last active administrator account are blocked with an error response — zero last-admin guard bypasses.
- **SC-007**: 100% of attempts by an administrator to ban, soft-delete, or demote their own account are rejected with an error response — zero self-action bypasses.

## Clarifications

### Session 2026-05-24

- Q: How should ban enforcement be implemented when a banned user has an active session? → A: DB status check on each request via Symfony security event subscriber; no session invalidation or token blacklist needed.
- Q: What happens to WorkEntry and CorrectionProposal records when the authoring user account is deleted? → A: Nullify author FK on all content records; content retained, personal data linkage removed.
- Q: What is the scope and storage mechanism for moderation action logging? → A: Structured DB audit table (`moderation_log`) storing moderator ID, action type, target entity ID, timestamp, and optional free-text `reason` (used on REJECTED actions).
- Q: Is a rejection reason captured when a moderator rejects a PENDING submission? → A: Optional free-text field in `moderation_log`; can be blank; not required to submit rejection.
- Q: How is `moderation_log` target entity reference stored? → A: Two string columns (`target_entity_type`, `target_entity_id`), no FK constraint; audit log is append-only, referential integrity not required.
- Q: What does a MODIFIED `moderation_log` entry capture beyond action type and target? → A: Action type + target only; no before/after diff stored. Current entity state is always queryable separately.
- Q: What is the scope of FR-013 (global application settings) in this feature? → A: Route protection only — `/admin/settings` gated to ROLE_ADMIN returning a stub response; no settings UI implemented here.
- Q: Is user account deletion a hard delete or soft delete? → A: Soft delete — anonymize personal data fields (email, display name), set `deleted_at` timestamp, retain row for audit trail.
- Q: Should the system prevent an admin from banning or soft-deleting their own account? → A: Yes — block both; system returns error; another admin must perform the action.
- Q: Is REJECTED a terminal state for WorkEntry and CorrectionProposal? → A: Yes — terminal; user must create a new submission to try again.
- Q: How do soft-deleted users get blocked from authenticating? → A: Separate UserProvider guard — throws `UsernameNotFoundException` for deleted users; banned users handled by security event subscriber.
- Q: What Doctrine column type stores roles on the User entity? → A: `json` column (e.g. `["ROLE_USER"]`); native DB JSON type, modern Symfony/Doctrine standard.
- Q: How are updated roles propagated to active sessions after a role change? → A: Symfony UserProvider `refreshUser` re-fetches user entity from DB on every request; no logout required, roles always current.
- Q: What happens when two moderators act on the same PENDING entry concurrently? → A: Last write wins; no optimistic or pessimistic lock required given low moderation volume.

### Session 2026-05-24 (gap-fill)

- Q: What exact fields are anonymized on soft-delete, and what placeholder replaces them? → A: `email` and `display_name` are replaced with the literal string `[deleted]`; no other fields are modified.
- Q: What triggers a MODIFIED log entry in `moderation_log`? → A: Any moderator-initiated content edit on a pending entity logs a MODIFIED entry. If the edit is followed by a status transition (APPROVED or REJECTED), a separate entry is also created for that action. The two entries are distinct records.
- Q: Is the `reason` field on `moderation_log` nullable or stored as an empty string when not provided? → A: Nullable — null is stored when no reason is provided; empty string is not a valid state.
- Q: Should the last-moderator demotion be guarded the same way as last-admin deletion? → A: Guarded (FR-015) — system blocks demotion if it would leave zero accounts with moderation capability; guard fires only when no ROLE_ADMIN exists to cover via inheritance.
- Q: Should FR-012 and FR-014 have dedicated success criteria? → A: Yes — SC-006 covers last-admin/moderator guard; SC-007 covers self-action prevention.
- Q: Does FR-014 self-action prevention extend to role self-demotion? → A: Yes — FR-014 covers ban, soft-delete, and self-demotion. SC-007 updated accordingly.
- Q: What HTTP response does the /admin/settings stub return for an authorized caller? → A: HTTP 200 with placeholder JSON body.
- Q: Should SC-003 enumerate expected nav items per role explicitly? → A: Yes — ROLE_USER: collection links only; ROLE_MODERATOR: + moderation dashboard; ROLE_ADMIN: + administration links.
- Q: Are 403 observability, ModerationLog retention/access, and nav accessibility in scope? → A: All three explicitly out of scope; deferred to separate specifications.
- Q: What is "active administrator" for FR-012 guard purposes? → A: ROLE_ADMIN account that is neither banned nor soft-deleted.
- Q: What happens to in-flight moderator actions when the moderator is banned mid-session? → A: Next request is denied by security event subscriber; completed actions already committed to DB are not rolled back.
- Q: Should the spec enforce that a user holds exactly one primary role at a time? → A: Yes — FR-016 added; role assignment replaces the entire roles array.

### Session 2026-05-24 (submission routes)

- Q: What routes allow ROLE_USER to create WorkEntry and CorrectionProposal records? → A: `POST /work-entries` for new work entries; `POST /work-entries/{id}/corrections` for correction proposals targeting an existing WorkEntry.
- Q: What fields does the WorkEntry submission route accept? → A: `title` (string, required). Status is always forced to PENDING regardless of any submitted value.
- Q: What fields does the CorrectionProposal submission route accept? → A: `proposedContent` (text, required). Status is always forced to PENDING.
- Q: What access level is required for submission routes? → A: `IS_AUTHENTICATED_FULLY` — any logged-in user regardless of role may submit. CSRF protection required per Constitution IV.
- Q: Are GET routes (listing, detail, creation form) in scope for this feature? → A: Only the POST submission routes (FR-017, FR-018) are in scope here; display and listing routes for WorkEntry are defined in a separate catalogue spec.

## Assumptions

- Existing user authentication (ROLE_USER base flow from spec 002-user-auth-oauth2) is already in place and operational. If the authentication system is unavailable, all protected routes fail closed — no access is granted.
- Roles are stored as a `json` Doctrine column on the User entity (e.g. `["ROLE_USER"]`); a single user holds one primary role at a time for this application.
- Banned users retain their data in the database but cannot authenticate. Account "deletion" is a soft delete: `email` and `display_name` are replaced with `[deleted]` and `deleted_at` is set; the row is retained for audit continuity.
- The moderation dashboard is a back-office web interface, not a public-facing page; no special performance requirements beyond standard web page load times.
- Email notifications to submitters upon rejection are considered out of scope for this feature and will be addressed in a separate notification specification.
- Global application settings (ROLE_ADMIN only) are out of scope for this feature. This feature only creates and protects the `/admin/settings` route stub; the settings UI and content are defined in a separate spec.
- 403 event observability and monitoring (e.g. logging unauthorized access attempts) are out of scope for this feature and will be addressed in a separate operations/observability specification.
- ModerationLog access control (who can query the audit table), data retention policy, and archiving are out of scope for this feature; the table is append-only within this scope.
- Accessibility requirements for conditionally rendered navigation elements are out of scope for this feature and will be addressed in a dedicated accessibility specification.
