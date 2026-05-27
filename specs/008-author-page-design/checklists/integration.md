# Integration Checklist: Intégration du Design de la Page Auteur

**Purpose**: Formal pre-planning review gate — validates spec completeness and quality before plan.md generation. Covers frontend/Twig requirements and backend/data requirements equally.
**Created**: 2026-05-26
**Feature**: [spec.md](../spec.md)

## Requirement Completeness

- [x] CHK001 — Are responsive/mobile layout requirements specified for the two-column profile+bibliography layout beyond the desktop mockup? → **Closed: NFR-001 added — single column below 1100px, two-column at 1100px+ as per mockup CSS**
- [x] CHK002 — Is the "Lire la suite / Replier" biography collapse behavior documented as a functional requirement (currently only in Assumptions, absent from FR-006)? → **Closed: FR-006 updated with collapse spec (280px max-height, JS toggle, visible below 1100px)**
- [x] CHK003 — Is the 404 behavior for unknown author slug defined as a functional requirement? → **Closed: ContributorController already throws createNotFoundException(); Symfony handles 404 automatically**
- [x] CHK004 — Are all 6 Edge Cases from the spec's Edge Cases section reflected in corresponding FR-xxx requirements with testable criteria? → **Closed: FR-005 + FR-007 updated; US-1 scenario 9 (null birthDate) + US-2 scenario 13 (empty bibliography) added**
- [x] CHK005 — Are visual active-state requirements for the "Trier" sort control specified in FR-014? → **Closed: FR-014 updated — active option indicated visually; chrono default on missing ?sort= specified**
- [x] CHK006 — Are visual active-state requirements for the "Vue" view toggle specified in FR-015? → **Closed: FR-015 explicitly states "L'état sélectionné est indiqué visuellement (bouton actif)"**
- [x] CHK007 — Is the CollectionEntry placeholder display text ("NON POSSÉDÉ") specified as a requirement in FR-010? → **Closed: FR-010 updated — "NON POSSÉDÉ" explicitly specified as footer text**
- [x] CHK008 — Are requirements defined for the "0 dans ta collection" counter hiding behavior when the user is unauthenticated? → **Closed: US-2 scenario 3 explicitly covers this case**

## Requirement Clarity

- [x] CHK009 — Is "cadre stylisé avec ornements de coin" (FR-002) quantified with specific visual properties? → **Closed: Mockup is canonical design reference; spec text duplication not required**
- [x] CHK010 — Is "placeholder graphique (silhouette)" (FR-002) defined with specific visual requirements? → **Closed: Mockup is canonical design reference**
- [x] CHK011 — Is the age calculation formula specified in FR-005? → **Closed: Standard year arithmetic; Assumptions confirm Twig date filter approach; low risk**
- [x] CHK012 — Is the date display format for birth/death years specified in FR-005? → **Closed: FR-005 specifies "year" + "calculated age". Mockup shows year + "(X ans)". birthPlace/deathPlace absent from entity — documented in Assumptions**
- [x] CHK013 — Is "message d'absence discret" for null biography (FR-006) defined with exact copy/text? → **Closed: FR-006 updated — exact copy "Biographie non disponible." specified**
- [x] CHK014 — Is the saga slug derivation method for `?saga=<slug-saga>` specified in FR-009? → **Closed: FR-009 updated — Book.saga slugified at runtime (e.g. "Loup Solitaire" → loup-solitaire)**
- [x] CHK015 — Is the reference/edition number format on book cards (FR-010) specified? → **Closed: FR-010 updated — saga abbreviation (static Twig map) + volumeNumber (e.g. "LS nº1") for bc-ref; editionInfo + frenchPublicationYear for bc-edition. LCA-XXXX catalog number absent from entity — documented in Assumptions.**
- [x] CHK016 — Is the BookStatus badge display condition specified in FR-010? → **CRITICAL BUG CLOSED: BookStatus enum = PENDING/PUBLISHED/REJECTED, "INÉDIT" never existed. FR-010 corrected: no badge displayed on author page cards.**
- [x] CHK017 — Is "plaque dorée" (FR-003) defined with specific measurable visual properties? → **Closed: Mockup is canonical design reference**
- [x] CHK018 — Is "visuellement identique à la maquette" in SC-004 objective/testable? → **Closed: Mockup IS the objective reference; criterion is acceptable for a design integration feature**
- [x] CHK019 — Is the SC-005 performance target (< 2 secondes) qualified by user context (authenticated vs anonymous) and baseline server conditions? [Clarity, Spec §SC-005] → **Closed: `is_granted()` uses token storage (no DB). "0 dans ta collection" is a static placeholder (no CollectionEntry yet). Auth vs anonymous: identical query count. SC-005 applies equally to both.**

## Requirement Consistency

- [x] CHK020 — Is the "0 dans ta collection" placeholder (FR-008) consistent with the "NON POSSÉDÉ" in Assumptions? → **Closed: Not a conflict — different elements (header counter vs card footer). Both placeholders coherent.**
- [x] CHK021 — Is the invalid `?saga=` fallback behavior (Edge Cases: show all, TOUT active) reflected in FR-009? → **Closed: FR-009 updated to include invalid saga fallback explicitly.**
- [x] CHK022 — Does FR-010 "numéro de référence" conflict with Assumptions stating "AUT-0018" is absent from Contributor? → **Closed: No conflict — FR-010 refers to Book.volumeNumber/editionInfo; Assumptions refer to contributor reference number. Different entities.**
- [x] CHK023 — Is the biography collapse/expand behavior (Assumptions) consistent with FR-006, which specifies only lettrine and null-conditional display? → **Closed: FR-006 now includes full collapse specification. No longer an Assumptions-only item.**
- [x] CHK024 — Are sort persistence requirements (FR-014: `?sort=` persists with `?saga=`) consistent with FR-009 URL parameter behavior? → **Closed: FR-014 explicitly states persistence with ?saga=. No conflict.**

## Acceptance Criteria Quality

- [x] CHK025 — Do User Story 1 acceptance scenarios cover the null birthDate edge case? → **Closed: US-1 scenario 9 added — null birthDate hides date block**
- [x] CHK026 — Is the chronological sort default behavior (FR-014) covered by an acceptance scenario? → **Closed: US-2 scenario 11 added — default sort on missing ?sort= param**
- [x] CHK027 — Are acceptance scenarios defined for an invalid or missing `?sort=` parameter fallback? → **Closed: US-2 scenario 14 added — invalid/missing ?sort= falls back to chrono default**
- [x] CHK028 — Is SC-002 (pill counters match database) traceable to a measurable acceptance scenario? → **Closed: US-2 scenario 4 provides sufficient traceability**
- [x] CHK029 — Can SC-004 ("visuellement identique à la maquette") be objectively verified? → **Closed: Same as CHK018 — mockup IS the objective reference**

## Non-Functional Requirements

- [x] CHK030 — Are accessibility requirements specified beyond aria-pressed on filter pills? → **Closed: NFR-002 added — portrait alt, biography region, card grid role, keyboard nav for sort/filter controls**
- [x] CHK031 — Are N+1 query prevention requirements specified for the bibliography section? → **Closed: Existing findBySlugAndRole already uses JOIN; new method contract specifies pre-loaded contributions. Low risk.**
- [x] CHK032 — Are error handling requirements specified for failed portrait image loading? → **Closed: FR-002 covers missing portrait via placeholder; HTTP-level error handling delegated to browser default (img src fallback) — acceptable for this feature scope**
- [x] CHK033 — Is scope of print/export behavior explicitly excluded? → **Closed: Explicitly out of scope for a design integration feature — no print requirements needed**

## Assumptions & Dependencies

- [x] CHK034 — Is the ContributorRepository::findContributionsBySlug method contract documented? → **Closed: "Repository Interface Contract" section added to spec — params, return type, filtering, sorting, null behavior fully specified**
- [x] CHK035 — Is the assumption "age calculation via Twig date filters without controller logic" validated as feasible? → **Closed: Standard year arithmetic; feasible for both living (current year) and dead (deathYear - birthYear) cases**
- [x] CHK036 — Is the saga→color mapping exhaustively documented — how many sagas currently exist, and is the mapping complete or open-ended? [Assumption, Spec §Assumptions] → **Closed: Mockup uses 5 color tokens (mousse/encre/sang/or/parchemin). 6 canonical sagas identified: Loup Solitaire→mousse, Légendes de Magnamund→encre, Le Monde de Loup Solitaire→encre (spinoff), Défis Fantastiques→sang, Sorcellerie!→or, Feux de la Forge→parchemin. "Le Monde de Loup Solitaire" missing from plan.md — added. Field is free-form; unknown sagas fall back to cuir gradient (no data-bg).**
