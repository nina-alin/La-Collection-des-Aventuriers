# Pre-Plan Sanity Checklist: Composant Recherche Globale (Omnibox)

**Purpose**: Validate requirement quality across UX states, accessibility, and performance/data contract before planning
**Created**: 2026-05-31
**Feature**: [spec.md](../spec.md)

---

## Requirement Completeness — UX States

- [x] CHK001 - Are visual requirements defined for panel open/close animation (or is no animation explicitly acceptable)? → Animation follows design CSS; no additional spec requirement. [Resolved]
- [x] CHK002 - Are requirements defined for panel maximum height when content exceeds viewport on small screens? → Mobile layout defined by design CSS per Assumptions; out of spec scope. [Resolved]
- [x] CHK003 - Is the counter format for "Recherches Récentes" specified? → `(N)` format added to FR-004. [Resolved]
- [x] CHK004 - Is the counter format for "Souvent Consultés" specified? → `(N)` format added to FR-006. [Resolved]
- [x] CHK005 - Is the panel header (FR-007 "COMMENCE À ÉCRIRE_") specified for the dynamic results state? → Pre-input only; hidden during results. Added to FR-007. [Resolved]
- [x] CHK006 - Is the number of "Recherches Récentes" items displayed specified? → Max 5 (matches session cap). Added to FR-022. [Resolved]

---

## Requirement Clarity — Entity Display

- [x] CHK007 - Are sizing requirements defined for the "indicateur visuel"? → 40×40px added to FR-008 and FR-009. [Resolved]
- [x] CHK008 - Is the fallback specified when a Livre has no couverture miniature? → Generic book icon fallback added to FR-009. [Resolved]
- [x] CHK009 - Are "avatar avec initiales" rules defined for Auteur? → 2 initials, auto-generated background added to FR-009. [Resolved]
- [x] CHK010 - Are metadata truncation rules quantified? → 1 line, ellipsis added to FR-008. [Resolved]
- [x] CHK011 - Is display priority specified for Auteur photo vs. initiales? → Photo > initiales fallback added to FR-009. [Resolved]

---

## Requirement Consistency — State Transitions

- [x] CHK012 - FR-017 vs FR-003 consistency on clear? → FR-017 already states return to pre-input state; consistent. [Resolved]
- [x] CHK013 - Skeleton count vs 8-result cap consistency? → "typiquement 3" = indicative, max 8 cap is separate rule; no conflict. [Resolved]
- [x] CHK014 - "Souvent Consultés" cap consistency? → 4-item cap from Clarifications; FR-005/FR-006 updated implicitly via Clarifications. [Resolved]

---

## Accessibility Requirements Coverage

- [x] CHK015 - Is a WCAG compliance level specified? → WCAG 2.1 AA added as NFR-001. [Resolved]
- [x] CHK016 - Are ARIA roles defined? → combobox pattern (role, aria-expanded, aria-controls, aria-activedescendant, listbox, option) added as NFR-002. [Resolved]
- [x] CHK017 - Focus management on panel open? → Focus stays in input; aria-activedescendant handles visual nav. Added to FR-012. [Resolved]
- [x] CHK018 - Keyboard nav scope for pre-input sections? → All visible items navigable via ↑↓ (including pre-input sections). Added to FR-012. [Resolved]
- [x] CHK019 - Screen reader announcement for results? → aria-live="polite" region added as NFR-003. [Resolved]
- [x] CHK020 - Footer link keyboard access? → Tab-only (not ↑↓). Added to FR-012. [Resolved]

---

## Performance Requirements Quality

- [x] CHK021 - SC-002 measurement method? → focus event → first panel paint. Added as NFR-005. [Resolved]
- [x] CHK022 - SC-003 measurement method? → API response received → results rendered. Added as NFR-005. [Resolved]
- [x] CHK023 - API timeout threshold? → 5 000ms added as NFR-004. [Resolved]
- [x] CHK024 - Mobile performance requirements? → Same thresholds apply; layout adapts per design CSS. [Resolved]

---

## Data Contract & API Requirements

- [x] CHK025 - API response shape per entity type? → Key Entities section defines all required fields; FR-021 updated to reference it. [Resolved]
- [x] CHK026 - In-flight cancellation requirements? → Cancel-on-new-keystroke added to FR-019. [Resolved]
- [x] CHK027 - "Popularité globale" ranking criteria? → Backend concern; outside component spec scope per Assumptions. [Resolved]
- [x] CHK028 - Session history FIFO + dedup behavior? → FIFO eviction + dedup (move to top) added as FR-022. [Resolved]

---

## Edge Case & Resilience Requirements

- [x] CHK029 - Empty "Souvent Consultés" state? → Section hidden (silent degradation). Added to FR-023. [Resolved]
- [x] CHK030 - Both sections empty simultaneously? → Panel shows with footer link only. Added to FR-023. [Resolved]
- [x] CHK031 - Are requirements defined for the global search submission path? → `/catalogue?q=:query` added as FR-022. [Resolved]

---

## Notes

- 31/31 items resolved.
- Spec updated: FR-004, FR-006, FR-007, FR-008, FR-009, FR-012, FR-019, FR-021 amended; FR-022/FR-023/FR-024 and NFR-001–NFR-005 added.
