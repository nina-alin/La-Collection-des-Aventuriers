# Pre-Planning Checklist: Système de Suivi — Créateurs & Collections

**Purpose**: Validate specification completeness and quality across all domains (UX, Notifications, Data/API) before planning
**Created**: 2026-06-10
**Feature**: [spec.md](../spec.md)

## Requirement Completeness — UX Interactions

- [x] CHK001 - Are "instantanément" and "immédiatement" in US1/US2 quantified with a measurable threshold? → SC-001 defines <100ms; FR-003 updated to cross-reference SC-001. [Clarity, Spec §SC-001, §FR-003]
- [x] CHK002 - SC-001 defines <100ms for optimistic visual feedback — is this threshold also referenced in FR-003 to close the traceability gap? → FR-003 now references SC-001 explicitly. [Consistency, Spec §FR-003, §SC-001]
- [x] CHK003 - Is the exact visual difference between "♡ SUIVRE" and "♥ SUIVI" states fully defined? → Delegated to existing mockups per Assumptions §6 ("interface déjà présente visuellement dans les maquettes"). [Clarity, Spec §Assumptions]
- [x] CHK004 - Are requirements defined for the error toast message content and display duration after optimistic update rollback? → FR-003 updated: toast text "Une erreur est survenue. Votre action n'a pas été enregistrée." / 4s duration. [Completeness, Spec §FR-003]
- [x] CHK005 - Is the authentication modal content fully specified (title, body text, CTA label, redirect behavior after login)? → US1 scenario 3 updated: CTA "Se connecter" added. US2 scenario 3 updated: full message + CTA added. Redirect covered by Edge Cases. [Clarity, Spec §US1, §US2]
- [x] CHK006 - Are redirect-after-login requirements defined for the unauthenticated follow attempt (return to original page)? → Already specified in Edge Cases: "retour prévu vers la page d'origine après connexion". [Completeness, Spec §Edge Cases]
- [x] CHK007 - Is the empty state for the "Uniquement ceux que je suis" toggle defined for Collections as well as Créateurs? → Edge Cases updated with Collections CTA: "Vous ne suivez encore aucune collection. Découvrez les collections !". US4 scenario 4 added. [Completeness, Spec §Edge Cases, §US4]
- [x] CHK008 - Are the toggle visual state and its position in the UI explicitly specified? → FR-012 updated: position "dans la barre latérale" explicit. Visual active/inactive states delegated to mockups. [Clarity, Spec §FR-012]
- [x] CHK009 - Are requirements defined for toggle persistence — does it reset on page reload or persist in session/URL? → FR-012 updated: state persisted via URL param `?followed=true`. [Completeness, Spec §FR-012]
- [x] CHK010 - Is follow button behavior defined when the API call is in-flight (double-click protection, disabled state, or spinner)? → FR-003 updated: button disabled during in-flight request. [Coverage, Spec §FR-003]

## Requirement Completeness — Notifications

- [x] CHK011 - Is a notification delivery latency SLA defined? → Explicitly excluded: new Assumption added — no delivery SLA in scope for this version. [Completeness, Spec §Assumptions]
- [x] CHK012 - Is the Créateur notification template fully specified? → US3 scenario 1 defines exact message: "Le créateur [Nom] que tu suis a été ajouté à une nouvelle fiche." Max length deferred to feature-017 constraints. [Clarity, Spec §US3]
- [x] CHK013 - Is the Collection notification template fully specified? → US3 scenario 2 defines exact message: "Une nouvelle fiche vient d'enrichir la collection [Nom] que tu suis." Max length deferred to feature-017 constraints. [Clarity, Spec §US3]
- [x] CHK014 - Is the Créateur-over-Collection priority rule defined for all dedup cases including multiple followed Créateurs? → FR-010 updated: role hierarchy Auteur > Illustrateur > Traducteur when multiple Créateurs followed. ✅ Confirmed. [Completeness, Spec §FR-010]
- [x] CHK015 - Are requirements defined for notification behavior when multiple followed Créateurs are linked to the same book? → Resolved by FR-010 role hierarchy update (Auteur > Illustrateur > Traducteur). [Coverage, Spec §FR-010]
- [x] CHK016 - Is it specified which Créateur name appears in the template when multiple followed Créateurs are linked? → Resolved by FR-010 role hierarchy: first eligible Créateur by role order used in message. [Clarity, Spec §FR-010]
- [x] CHK017 - Are Dead Letter Queue monitoring and alerting requirements defined? → New Assumption: monitoring via `messenger:failed`; no automated alerting in scope. [Completeness, Spec §Assumptions]
- [x] CHK018 - Is the retry policy (3 attempts) quantified with inter-retry delay and backoff strategy? → New Assumption: delay/backoff configurable in transport config, exact value deferred to plan.md. [Clarity, Spec §Assumptions]
- [x] CHK019 - Is the acknowledged risk (missed notifications on failure) documented as an explicit accepted tradeoff? → Already documented in FR-013: "Conséquence acceptée". [Clarity, Spec §FR-013]
- [x] CHK020 - Are requirements defined for what happens to `followNotificationSentAt` after a partial job failure? → FR-013 and Edge Cases updated: flag is permanent by design, no reset mechanism in scope. [Coverage, Spec §FR-013, §Edge Cases]
- [x] CHK021 - Does FR-009 reference the feature-017 notification system contract explicitly enough? → FR-009 updated with "cf. feature 017" cross-reference. [Traceability, Spec §FR-009]
- [x] CHK022 - Are requirements defined for notification delivery scope? → Key Entities updated: Notification scoped to "in-app uniquement — email et push hors scope". [Completeness, Spec §Key Entities]

## Requirement Completeness — Data Model & API

- [x] CHK023 - Are the routes (URLs, HTTP methods) for follow/unfollow actions explicitly specified? → URL patterns correctly deferred to plan.md (implementation concern); spec defines behavior, not URLs. [Completeness, Spec §Assumptions]
- [x] CHK024 - Is idempotency behavior defined for duplicate follow requests? → New Assumption: follow/unfollow are idempotent — duplicate call returns success silently; UNIQUE constraint as safety net. [Clarity, Spec §Assumptions]
- [x] CHK025 - Is cascade behavior on Collection deletion defined for `UserFollowedCollections`? → Edge Cases and Key Entities updated: ON DELETE CASCADE added for Collection deletion. [Completeness, Spec §Edge Cases, §Key Entities]
- [x] CHK026 - Are authorization requirements defined for follow/unfollow beyond "utilisateur connecté"? → Symfony security context handles role-based access; spec specifies "utilisateur connecté" as the only requirement — banned/unverified user handling deferred to existing security layer. [Completeness, Spec §Assumptions]
- [x] CHK027 - Is the `followedAt` timestamp specified as timezone-aware (UTC)? → Key Entities updated: both join entities now specify "date d'abonnement (UTC)". [Clarity, Spec §Key Entities]
- [x] CHK028 - Are requirements defined for a potential admin reset path for `followNotificationSentAt`? → FR-013 updated: flag permanent, no reset mechanism in scope. [Coverage, Spec §FR-013]

## Requirement Consistency

- [x] CHK029 - Is the "Suivre / Suivi" label consistent across all spec sections? → Verified: consistent across §US1, §US2, §FR-001, §FR-002, §FR-003, §FR-012 following clarification of 2026-06-10. [Consistency]
- [x] CHK030 - Does SC-002 ("100% des utilisateurs") conflict with FR-013's acknowledged risk of missed notifications? → SC-002 updated with qualifier "sous réserve du bon fonctionnement du système Messenger (hors panne de job — cf. FR-013 tradeoff accepté)". [Conflict resolved, Spec §SC-002]
- [x] CHK031 - Is FR-003 (optimistic update + rollback) referenced consistently in both US1 and US2 acceptance scenarios? → Error rollback scenario added to both US1 (scenario 4) and US2 (scenario 4), both referencing FR-003. [Consistency, Spec §US1, §US2]

## Acceptance Criteria Quality

- [x] CHK032 - Can SC-001 (<100ms visual feedback) be objectively measured? → Measurement method: browser performance timing (click event timestamp vs DOM update timestamp), verifiable via E2E test (e.g., Playwright). No spec change needed — testing method is an implementation concern. [Measurability, Spec §SC-001]
- [x] CHK033 - Can SC-003 (0% doublon) be objectively verified? → Verification method: query Notification entity count per user per Book publication event. No spec change needed — audit method is an implementation concern. [Measurability, Spec §SC-003]
- [x] CHK034 - Are acceptance scenarios for FR-012 (toggle) defined in Given/When/Then format? → User Story 4 added with 5 full Given/When/Then scenarios covering both Créateurs and Collections lists, empty states, and guest visibility. [Completeness, Spec §US4]

---

All 34 items resolved. Spec ready for `/speckit-plan`.
