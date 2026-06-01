# Pre-Planning Spec Review Checklist: Refonte Page Collection

**Purpose**: Pre-planning gate — author self-check before writing plan.md. Tests requirement quality (completeness, clarity, consistency, measurability) across all 5 user stories. Does NOT test implementation correctness.
**Created**: 2026-06-01
**Reviewed**: 2026-06-01
**Feature**: [spec.md](../spec.md)

---

## Requirement Completeness — Hero Section

- [x] CHK001 — Are the visual dimensions and positioning of the circular logo macaron explicitly specified (size, border radius, fallback placeholder dimensions)? [Completeness, Gap, Spec §FR-001] → **Resolved**: deferred to `design/pages/collection.html` (confirmed present); FR-001 updated to reference design file.
- [x] CHK002 — Is the mean-of-means approach for average rating calculation documented in Requirements (not only Assumptions), given it is a deliberate — and biased — design decision? [Completeness, Spec §FR-002, Assumptions] → **Resolved**: FR-002 now explicitly states "moyenne des notes moyennes de chaque tome (biais intentionnel)".
- [x] CHK003 — Are all four hero meta-data fields individually mapped to their source data fields/entities? [Completeness, Spec §FR-002] → **Resolved**: FR-002 maps all four fields; `statut éditorial` reads from `Collection` entity status field.
- [x] CHK004 — Are measurable visual criteria defined for the "non-favori" state of the "Ajouter aux favoris" button? [Clarity, Spec §FR-003] → **Resolved**: deferred to design file (confirmed present).
- [x] CHK005 — Is the no-op behavior of "+ Suggérer un tome manquant" specified for all interaction types (keyboard, tap)? [Coverage, Spec §FR-004] → **Resolved**: FR-004 updated to cover "clic, clavier, toucher"; `href="#"` handles all.
- [x] CHK006 — Are hero section layout requirements defined for mobile/responsive breakpoints? [Gap, Non-Functional] → **Resolved**: NFR-001 added; design file is authority for responsive breakpoints.

---

## Requirement Completeness — Books Grid

- [x] CHK007 — Is the card background color algorithm unambiguously sourced? [Ambiguity, Spec §FR-007, Assumptions] → **Resolved**: FR-007 and Assumptions both state design file is sole source of truth; design file confirmed present.
- [x] CHK008 — Are the visual properties of the diagonal watermark specified in Requirements or deferred to the design file? [Clarity, Spec §FR-008] → **Resolved**: FR-008 explicitly defers to design file.
- [x] CHK009 — Is the static possession indicator described with specific visual criteria? [Clarity, Spec §FR-009] → **Resolved**: FR-009 states "point d'interrogation gris"; sufficient for implementation.
- [x] CHK010 — Are the card metadata layout requirements (field order, typography, truncation) specified? [Completeness, Spec §FR-010] → **Resolved**: field list in FR-010; layout deferred to design file.
- [x] CHK011 — Is the sort tie-breaking behavior defined for "Tri par Numéro" and "Tri par Note"? [Edge Case, Gap, Spec §FR-012] → **Resolved**: FR-012 updated with stable sort; Edge Cases section updated.
- [x] CHK012 — Are the visual "disabled" state requirements for Possédés/Manquants filters specified? [Clarity, Spec §FR-013] → **Resolved**: deferred to design file.
- [x] CHK013 — Is the default display order for the "Toutes" filter explicitly documented? [Clarity, Spec §FR-011] → **Resolved**: FR-011 updated — `volumeNumber` ASC (order serveur).

---

## Risk: Completion Widget — Static Hardcoded Values

- [x] CHK014 — Is the rationale for hardcoding the Completion widget values explicitly documented as a deliberate UX mock decision? [Assumption, Spec §FR-005] → **Resolved**: FR-005 states "codées en dur" with explicit TODO tech debt note.
- [x] CHK015 — Are requirements defined (or explicitly out-of-scope) for how the Completion widget transitions from static mock to dynamic data? [Gap, Spec §FR-005] → **Resolved**: FR-005 now includes explicit TODO marking it as future ticket tech debt.
- [x] CHK016 — Is it specified whether the hardcoded text "Il vous manque 16 tomes pour boucler la saga Kaï." is acceptable on all collections? [Ambiguity, Spec §FR-005, US3] → **Resolved**: changed to `{{ collection.name }}` — only the name is dynamic; numeric values remain static.

---

## Requirement Completeness — Publishing History

- [x] CHK017 — Is the CollectionPublishingHistory schema fully specified with field-level constraints within Requirements? [Completeness, Spec §FR-014] → **Resolved**: FR-014 now includes field types, nullability; Key Entities section confirmed.
- [x] CHK018 — Is "strictly more than one record" defined to explicitly exclude soft-deleted records? [Clarity, Spec §FR-015] → **Resolved**: standard Doctrine (no soft-delete in this project); all records count.
- [x] CHK019 — Is a tie-breaking rule defined for the timeline sort when two entries share the same startYear? [Edge Case, Spec §FR-016] → **Resolved**: FR-016 updated — tie-break by `id` ASC (insertion order).
- [x] CHK020 — Is the "éditeur inconnu" fallback for a deleted Editor FK promoted from Edge Cases prose into FR-017? [Consistency, Spec §FR-017, Edge Cases] → **Resolved**: FR-017 now includes "(éditeur inconnu)" fallback; US4 acceptance scenario 7 added.
- [x] CHK021 — Are requirements defined for displaying overlapping publication periods? [Edge Case, Gap] → **Resolved**: FR-017 states overlapping periods displayed as-is; Edge Cases section updated.

---

## Requirement Completeness — Recurring Contributors

- [x] CHK022 — Is the initials derivation algorithm specified for edge cases: single-name contributors, name particles? [Clarity, Spec §FR-020] → **Resolved**: FR-020 updated — single name uses first 2 chars ("Jo" for "Joe"); particles use available initials.
- [x] CHK023 — When a contributor holds multiple roles, which role label is displayed in the pill? [Ambiguity, Spec §FR-020] → **Resolved**: US5 §4 confirms one pill per (contributor, role) pair — each pill has exactly one role.
- [x] CHK024 — Are visual requirements defined for the zero-state of the contributors section (0 contributors)? [Edge Case, Spec US5 §5] → **Resolved**: FR-019 now includes "Afficher '0 CONTRIBUTEURS' si aucune contribution".
- [x] CHK025 — Is the maximum number of contributor pills defined, or is the list intentionally unbounded? [Gap, Completeness] → **Resolved**: FR-020 states "liste non bornée — toutes les pilules sont affichées".

---

## Risk: Cross-Pagination Contributors Calculation

- [x] CHK026 — Does SC-001 (page load < 2s for 30 books) explicitly cover the dedicated contributors aggregation query? [Ambiguity, Spec §SC-001, FR-018] → **Resolved**: SC-001 updated — "(inclut la requête d'agrégation des contributeurs et le rendu Twig complet)".
- [x] CHK027 — Is the implementation strategy for the cross-pagination contributors query documented as a requirement? [Gap, Spec §FR-018, Assumptions] → **Resolved**: FR-018 and Assumptions now specify "requête dédiée SQL/DQL" — eager loading ruled out.
- [x] CHK028 — Are caching/memoization requirements defined for the contributors aggregation? [Gap, Non-Functional, Spec §FR-018] → **Resolved**: caching is out of scope for this ticket; not required.

---

## Requirement Clarity & Measurability

- [x] CHK029 — Is SC-002 ("visuellement conforme à 100%") operationalized with a concrete comparison method? [Measurability, Spec §SC-002] → **Resolved**: SC-002 updated — "Validation : revue manuelle par l'auteur du ticket, comparaison section par section avec le fichier design".
- [x] CHK030 — Is SC-003 ("sans erreur console") defined for which browsers and environments? [Clarity, Spec §SC-003] → **Resolved**: SC-003 updated — "Chrome, Firefox, Safari, Edge — deux dernières versions stables".
- [x] CHK031 — Is "Symfony UX (Turbo/Stimulus)" specified as a mandatory technology constraint or a recommendation? [Clarity, Assumptions] → **Resolved**: NFR-003 created — Symfony UX is now a mandatory constraint in Requirements.

---

## Edge Case Coverage

- [x] CHK032 — Are requirements defined for a collection with zero books (empty grid state)? [Gap, Edge Case] → **Resolved**: FR-006 updated; Edge Cases section updated — "LES TOMES — 0 VOLUMES" with empty grid.
- [x] CHK033 — Are requirements defined for very long collection names or original names (overflow, truncation)? [Gap, Edge Case] → **Resolved**: deferred to design file; low risk.
- [x] CHK034 — Is the partial-year edge case promoted from Edge Cases into FR-002? [Traceability, Spec §FR-002, Edge Cases] → **Resolved**: FR-002 already covered "min–max des tomes ayant une année connue"; Edge Cases preserved for completeness.
- [x] CHK035 — Is the missing volumeNumber fallback promoted from Edge Cases into FR-007? [Traceability, Spec §FR-007, Edge Cases] → **Resolved**: FR-007 updated — "Si `volumeNumber` est absent, utiliser `0` comme valeur de fallback".

---

## Non-Functional Requirements

- [x] CHK036 — Are accessibility requirements defined for interactive elements? [Gap, Non-Functional] → **Resolved**: NFR-002 added — ARIA, keyboard navigation, WCAG 2.1 AA required.
- [x] CHK037 — Are responsive/mobile layout requirements specified for all sections? [Gap, Non-Functional] → **Resolved**: NFR-001 added — all 5 sections must be responsive; design file is authority.
- [x] CHK038 — Is the < 2s performance budget defined to include or exclude the contributors aggregation query? [Clarity, Spec §SC-001] → **Resolved**: same as CHK026 — SC-001 covers full page load.
- [x] CHK039 — Are requirements defined for the page when JavaScript is disabled? [Gap, Edge Case, Non-Functional] → **Resolved**: explicitly out of scope; documented in Assumptions — "La page requiert JavaScript pour les tris/filtres client".

---

## Dependencies & Assumptions

- [x] CHK040 — Is design/pages/collection.html listed as a blocking prerequisite? [Dependency, Spec §Assumptions] → **Resolved**: file confirmed to exist at `design/pages/collection.html`; Assumptions reference it as source of truth.
- [x] CHK041 — Is the 20-books/page pagination assumption documented as a constraint with risk note if page size changes? [Assumption, Spec §Assumptions] → **Resolved**: Assumptions updated with explicit risk note — contributors query is independent of pagination.
- [x] CHK042 — Is it documented whether the Editor entity already exists in the schema? [Dependency, Gap, Spec §FR-014] → **Resolved**: `src/Entity/Editor.php` confirmed to exist; Key Entities and Assumptions updated accordingly.
