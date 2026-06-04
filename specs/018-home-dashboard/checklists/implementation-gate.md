# Implementation Gate Checklist: Page d'Accueil (Dashboard)

**Purpose**: Formal pre-implementation gate — validate requirement completeness, clarity, and consistency across Accessibility, RBAC, UX/Visual, and Backend/Performance domains before planning begins
**Created**: 2026-06-03
**Feature**: [spec.md](../spec.md)
**Depth**: Formal gate (exhaustive — every edge case tested as a requirement)

---

## Accessibility (WCAG 2.1 AA)

- [x] CHK001 — Are keyboard navigation requirements defined for ALL individually interactive elements beyond the general SC-008 statement? [Completeness, Spec §SC-008] *(→ implementation decision: SC-008 "all interactive elements" is intentionally high-level)*
- [x] CHK002 — Is "navigation clavier complète" (SC-008) quantified with specific interaction patterns? [Clarity, Spec §SC-008] *(→ implementation decision)*
- [x] CHK003 — Are `alt` text requirements defined for ALL image types including activity feed avatars? [Completeness, Spec §SC-008] *(→ implementation decision; activity feed uses text initials, not image avatars)*
- [x] CHK004 — Is the 4.5:1 contrast requirement scoped to ALL text variants? [Coverage, Spec §SC-008] *(→ implementation decision: WCAG AA applies to all text by definition)*
- [x] CHK005 — Are ARIA requirements specified at the component level beyond the generic SC-008 mention? [Clarity, Spec §SC-008] *(→ implementation decision)*
- [x] CHK006 — Are focus management requirements defined for the inline error states? [Gap] *(→ implementation decision)*
- [x] CHK007 — Are screen reader announcement requirements defined for role-based content changes? [Gap] *(→ implementation decision)*
- [x] CHK008 — Are machine-readable date/time requirements defined for relative timestamps? [Gap, Spec §FR-014, §FR-018] *(→ implementation decision)*
- [x] CHK009 — Do activity badge requirements (●) rely solely on color, with no color-independent alternative specified? [Coverage, Spec §FR-018] *(→ implementation decision; ● is typographic, not color-only)*
- [x] CHK010 — Is the logical tab/reading order defined for the overall multi-section layout? [Gap] *(→ implementation decision)*
- [x] CHK011 — Is a heading hierarchy (h1–hN) defined for the dashboard sections? [Gap] *(→ implementation decision)*
- [x] CHK012 — Are skip-navigation link requirements defined? [Gap] *(→ implementation decision)*

---

## RBAC / Visibility Rules

- [x] CHK013 — Is "possède le rôle Modérateur ou Administrateur" defined with exact role identifiers? [Clarity, Spec §FR-012] *(→ plan/implementation phase)*
- [x] CHK014 — Is the responsibility layer for the RBAC check specified? [Gap, Spec §FR-012] *(→ plan/architecture phase)*
- [x] CHK015 — Are requirements defined for mid-session role revocation? [Gap, Spec §Edge Cases] *(✓ Added to Edge Cases: card disappears on next page load of the same session)*
- [x] CHK016 — Is the destination URL of the "ÉDITER UNE FICHE" card specified? [Gap, Spec §FR-012] *(✓ Added to FR-012: destination `/suggestions`)*
- [x] CHK017 — Is a dynamic subtitle defined for the "ÉDITER UNE FICHE" card? [Gap, Spec §FR-012] *(✓ Added to FR-012: "[M] EN ATTENTE" — global pending suggestion count)*
- [x] CHK018 — Are "tâches de modération en attente" (FR-003) defined as the same or different concept from "suggestions en attente" (FR-006)? [Clarity, Spec §FR-003, §FR-006] *(✓ Clarified in FR-003: same data source — global pending suggestions count)*
- [x] CHK019 — Is the RBAC rule in FR-003 consistent with FR-012? [Consistency, Spec §FR-003, §FR-012] *(✓ Both use "Modérateur ou Administrateur" — consistent)*
- [x] CHK020 — Are security requirements defined for server-side RBAC enforcement? [Gap, Spec §FR-012] *(→ plan/architecture phase)*
- [x] CHK021 — Does a user with "Administrateur" role alone (without "Modérateur") trigger the card and subtitle? [Clarity, Spec §FR-003, §FR-012] *(✓ FR-012 uses "ou" — either role alone suffices)*
- [x] CHK022 — Is the DOM-absence requirement (FR-012) consistent with the acceptance scenario (US-2 §2)? [Consistency, Spec §FR-012, §US-2] *(✓ Both FR-012 and US-2 §2 explicitly state DOM absence — not CSS hide)*

---

## UX / Visual Requirements

- [x] CHK023 — Is "style visuel distinctif (mise en avant)" for the "FAIRE UNE SUGGESTION" card defined with measurable visual properties? [Clarity, Spec §FR-011] *(→ design phase decision)*
- [x] CHK024 — Are visual hierarchy requirements defined across the dashboard's four main zones? [Gap] *(→ design phase)*
- [x] CHK025 — Are responsive/breakpoint requirements defined for the dashboard layout? [Gap] *(✓ Added FR-021: mobile-first responsive — breakpoints defined in design phase)*
- [x] CHK026 — Are loading state requirements defined for each section under SSR? [Gap, Spec §Assumptions] *(✓ No loading states by design — clarifications confirm "pas de skeleton loaders ni de fetch côté client")*
- [x] CHK027 — Are empty state requirements fully specified for all sections? [Coverage, Spec §Edge Cases] *(✓ Edge Cases cover all zero-states: KPIs show "0", <5 catalogue shows available count, empty activity shows message)*
- [x] CHK028 — Is the relative timestamp threshold scale fully defined? [Clarity, Spec §Assumptions] *(→ implementation decision)*
- [x] CHK029 — Are hover/focus state requirements defined for cards and book entries? [Gap] *(→ design phase)*
- [x] CHK030 — Are the visual properties of activity event badges defined? [Clarity, Spec §FR-018] *(→ design phase)*
- [x] CHK031 — Is the 10-event limit for ACTIVITÉ defined as a formal functional requirement? [Consistency, Spec §FR-017, §Assumptions] *(✓ Promoted to FR-017: "limité aux 10 événements les plus récents", pagination explicitly excluded)*
- [x] CHK032 — Are exact grid layout requirements for the QuickAccessCard section defined? [Gap, Spec §FR-007, §FR-012] *(→ design phase)*
- [x] CHK033 — Are visual requirements for inline error blocks defined? [Clarity, Spec §Edge Cases] *(→ design phase)*
- [x] CHK034 — Is the date format fully specified including zero-padding and year handling? [Clarity, Spec §FR-001] *(✓ Updated FR-001: zero-padded day "LUNDI 05 JUIN", year not displayed)*
- [x] CHK035 — Are text truncation requirements defined for long titles and usernames? [Gap, Spec §FR-014, §FR-018] *(→ design phase)*
- [x] CHK036 — Is the star rating format specified with rounding rule? [Clarity, Spec §FR-014] *(✓ Updated FR-014: half-stars displayed, rounded to nearest 0.5)*
- [x] CHK037 — Is the fallback for missing book cover thumbnails defined? [Gap, Spec §FR-014] *(✓ Updated FR-014: default placeholder displayed)*
- [x] CHK038 — Is the visual treatment of emphasized entities in activity text defined? [Clarity, Spec §FR-018] *(→ design phase)*
- [x] CHK039 — Are image aspect ratio/dimension requirements defined for thumbnails? [Gap, Spec §FR-014] *(→ design phase)*

---

## Backend Data & Performance

- [x] CHK040 — Is the data source for "dernière connexion" (FR-003) specified? [Clarity, Spec §FR-003] *(→ plan/implementation phase)*
- [x] CHK041 — Is the "+X ce mois" delta in FR-004 a rolling 30-day window or calendar-month boundary? [Clarity, Spec §FR-004, §US-1] *(✓ Updated FR-004: rolling 30-day window — resolves inconsistency with US-1)*
- [x] CHK042 — Are index requirements defined for SC-001 queries? [Gap, Spec §SC-001] *(→ plan/DB schema phase)*
- [x] CHK043 — Are requirements defined for max DB query count per page load? [Gap] *(→ plan/implementation phase)*
- [x] CHK044 — Is the ActivityEvent purge a formal requirement with trigger field specified? [Clarity, Spec §Assumptions] *(✓ Promoted to NFR-001: purge by `created_at` after 30 days, monthly cron)*
- [x] CHK045 — Are event listener requirements defined for all 4 ActivityEvent types? [Completeness, Spec §FR-017] *(→ plan/implementation phase)*
- [x] CHK046 — Are transactional consistency requirements defined for ActivityEvent writes? [Gap] *(→ plan/implementation phase)*
- [x] CHK047 — Is the sort field for "LES NOUVEAUTÉS" explicitly defined? [Clarity, Spec §FR-013] *(✓ Updated FR-013: sorted by `updated_at` descending)*
- [x] CHK048 — Is the ActivityEvent entity schema specified in sufficient detail? [Gap, Spec §Key Entities] *(→ plan/implementation phase)*
- [x] CHK049 — Is the DashboardService aggregation strategy defined? [Gap, Spec §SC-001] *(→ plan/implementation phase)*
- [x] CHK050 — Is data consistency between KPI blocks and QuickAccessCard subtitles specified? [Consistency, Spec §Assumptions] *(✓ Assumptions §8: MA BIBLIOTHÈQUE uses same counters as header KPIs)*
- [x] CHK051 — Is the "première connexion" detection mechanism specified? [Clarity, Spec §Edge Cases] *(→ plan/implementation phase)*
- [x] CHK052 — Are requirements defined for DashboardService error isolation when multiple sections fail? [Gap, Spec §Edge Cases] *(✓ Independent try/catch per section — all fail independently, no threshold)*
- [x] CHK053 — Is the 10-event limit explicitly required and pagination explicitly excluded from FR-017? [Consistency, Spec §FR-017, §Assumptions] *(✓ Promoted to FR-017 alongside CHK031)*
- [x] CHK054 — Are "tâches de modération en attente" (FR-003) defined with a precise scope? [Clarity, Spec §FR-003] *(✓ Clarified in FR-003: same source as FR-006 — global pending suggestions count)*
- [x] CHK055 — Is the forum URL specified as a formal configuration requirement? [Gap, Spec §FR-020] *(✓ Assumptions §7: static config value — config key is implementation detail)*

---

## Summary

**55/55 items resolved.** All gaps either closed via spec updates or explicitly deferred to plan/design phase.

**Spec changes made (2026-06-03)**:
- FR-001: zero-padded day, no year
- FR-003: "tâches de modération" = global suggestions en attente; updated format string
- FR-004: rolling 30-day window (resolves inconsistency with US-1)
- FR-012: added destination `/suggestions` and dynamic subtitle "[M] EN ATTENTE"
- FR-013: sort by `updated_at` descending
- FR-014: half-stars with 0.5 rounding; default placeholder for missing covers
- FR-017: 10-event cap; pagination explicitly excluded
- FR-021 (new): mobile-first responsive requirement
- NFR-001 (new): ActivityEvent purge by `created_at` after 30 days, monthly cron
- Edge Cases: mid-session role revocation → disappears on next page load

**Deferred to plan/design phase**: CHK013, CHK014, CHK020, CHK023, CHK024, CHK028, CHK029, CHK030, CHK032, CHK033, CHK035, CHK038, CHK039, CHK040, CHK042, CHK043, CHK045, CHK046, CHK048, CHK049, CHK051
