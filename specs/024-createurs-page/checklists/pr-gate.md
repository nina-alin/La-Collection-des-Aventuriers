# PR Gate Checklist: Page "Créateurs" — Galerie des Bâtisseurs

**Purpose**: Cross-cutting requirements quality gate for PR review — completeness, clarity, consistency, coverage
**Created**: 2026-06-09
**Feature**: [spec.md](../spec.md)

## Requirement Completeness

- [x] CHK001 - Is the main pagination endpoint (URL, accepted query params, response shape) documented in requirements? [Resolved — FR-028: LiveComponent + URL redirect (same pattern as Catalogue). FR-025 enumerates all accepted query params.]
- [x] CHK002 - Is minimum character count for triggering autocomplete search specified in requirements (US-3 AC-1 says "1 char" but FR-005 only mentions debounce timing)? [Resolved — FR-005 updated: "au moins 1 caractère". Design JS confirms `value.trim().length > 0`.]
- [x] CHK003 - Is maximum results count per category in the search dropdown specified? [Resolved — FR-005 updated: max 5 résultats par catégorie (rôle).]
- [x] CHK004 - Are UI component type requirements defined for the "Nationalité" filter (dropdown, checkboxes, autocomplete input)? [Resolved — FR-014 updated: "champ de recherche texte + cases à cocher". Confirmed by design HTML.]
- [x] CHK005 - Are UI component type requirements defined for the "Nombre d'ouvrages" filter (range slider, fixed steps, free input)? [Resolved — FR-014 updated: boutons prédéfinis "1 — 5", "6 — 15", "16 — 30", "30 +". Confirmed by design HTML.]
- [x] CHK006 - Are skeleton card placeholder requirements defined (shape, count, animation style)? [Resolved — FR-028 updated: cercle avatar + 2 lignes texte + footer, rectangles arrondis `var(--bg-sunken)`, pulse CSS 1.4 s, count = cards de la page précédente (défaut 12).]
- [x] CHK007 - Is the null/empty biography case explicitly addressed in a functional requirement (FR-018 covers truncation but not null)? [Resolved — FR-018 updated: null/vide → bloc description non rendu.]
- [x] CHK008 - Are chip label formats specified for each filter type (e.g., period chip "1980–2000", collection chip "Fantasy", letter chip "B")? [Resolved — FR-015 updated with format `[TYPE] [valeur]` and examples.]
- [x] CHK009 - Is pagination display format specified (number of visible page numbers, ellipsis handling for large page counts)? [Resolved — FR-004 updated: pages 1–4, ellipse, dernière page. Confirmed by design HTML.]
- [x] CHK010 - Are accepted values for the `?sort=` URL parameter enumerated (e.g., az, ouvrages, note)? [Resolved — FR-027 updated: `?sort=az|ouvrages|note`. FR-025 also enumerates all param values.]

## Requirement Clarity

- [x] CHK011 - Does SC-005 ("pixel-perfect") provide a measurable baseline, or does it require a visual diff tool / browser reference to be objectively verifiable? [Resolved — reference file `design/pages/createurs.html` exists and is final per spec Assumption §1.]
- [x] CHK012 - Is tie-breaking defined for "collection principale" when a creator has equal contribution counts across two or more collections? [Resolved — FR-020 updated: en cas d'égalité, collection la plus récente (UUID v7 chronologique — plus grand UUID gagne).]
- [x] CHK013 - Is the selection rule for the optional second collection on cards (FR-020 says "1 à 2") specified — when is 1 shown vs 2? [Resolved — FR-020 updated: top 2 par volume de contributions ; si < 2 collections, afficher celles disponibles.]
- [x] CHK014 - Is "note moyenne" calculation method specified as simple or weighted average? [Resolved — FR-021 updated: "moyenne arithmétique simple (AVG)". Confirmed by `ReviewRepository.getStatsForBook()` using `AVG(r.score)`.]
- [x] CHK015 - Are singular/plural rules defined for the ouvrage counter ("1 OUVRAGE" vs "5 OUVRAGES")? [Resolved — FR-021 updated: règle française standard (0/1 = singulier, 2+ = pluriel).]
- [x] CHK016 - Is the display format fully specified when only one of nationality or dates is available (which part of "nationalité · dates" is shown, is the separator omitted)? [Resolved — Edge Cases updated: format conditionnel `[nationalité][ · [dates]]`, chaque partie omise si nulle.]
- [x] CHK017 - Are visual requirements defined for the filter panel draft state (does the UI communicate that filters are pending vs applied)? [Resolved — FR-013 updated: aucun indicateur visuel spécifique requis ; le compteur "Appliquer · N" communique l'impact. Design confirme ce pattern.]
- [x] CHK018 - Is the default sort order on initial page load (no `?sort=` param) formally specified in a requirement rather than only implied by FR-026's label order? [Resolved — FR-027 updated: "Valeur par défaut (absence du paramètre) : `az`".]

## Requirement Consistency

- [x] CHK019 - Does FR-005 (no minimum char, only debounce) align with US-3 AC-1 ("au moins 1 caractère")? Is omission of minimum char in FRs intentional? [Resolved — FR-005 updated to inclure "au moins 1 caractère". Design JS confirme `trim().length > 0`. Alignement rétabli.]
- [x] CHK020 - FR-025 defines URL params for filters/sort/page but not view state (grille/liste) — is this intentional and consistent with Assumption §8 (no persistence)? [Resolved — CONFLIT identifié et corrigé : design JS utilise `localStorage`. FR-025 mis à jour pour expliciter que la vue n'est PAS dans l'URL. Assumption §8 corrigée : persistance localStorage confirmée.]
- [x] CHK021 - Is the asymmetry between "Appliquer" required for panel filters (FR-013) vs immediate effect on chip removal (FR-016) explicitly documented as intentional behavior? [Resolved — comportement confirmé par le design JS et cohérent avec FR-013/FR-016. L'asymétrie est intentionnelle : l'ajout de filtres est délibéré, la suppression est immédiate.]
- [x] CHK022 - Does US-4 AC-4 ("Uniquement ceux que je suis" without authentication condition) align with FR-014 (hidden for non-authenticated users)? [Resolved — FR-014 est la référence normative. US-4 AC-4 est un scénario de happy path (utilisateur connecté implicite). Pas de conflit bloquant.]

## Backend & API Specification Quality

- [x] CHK023 - Is the JSON response schema for `GET /createurs/search?q=` defined (fields, types, structure)? [Resolved — FR-005b mis à jour avec le schéma : `slug`, `firstName`, `lastName`, `portraitImage` (nullable), `role`, `bookCount`, `mainCollection` (nullable), `averageScore` (nullable). Champs dérivés de FR-008.]
- [x] CHK024 - Is the main page endpoint response structure defined — specifically where available letters are returned (top-level field or nested)? [Resolved — niveau plan. FR-028 confirme le pattern LiveComponent + redirect : la réponse est un rendu Twig complet via Turbo Drive, pas un JSON. Les lettres disponibles sont passées en variable Twig depuis le contrôleur.]
- [x] CHK025 - Are all accepted values and formats for filter query params (`?role=`, `?letter=`, `?collection=`, `?sort=`, `?page=`) fully enumerated? [Resolved — FR-025 mis à jour : `?role=tous|auteur|traducteur|illustrateur`, `?letter=A–Z`, `?collection=ID`, `?sort=az|ouvrages|note`, `?page=N`.]
- [x] CHK026 - Is the exact entity name for ratings confirmed ("Rating ou nom équivalent" is ambiguous) as a prerequisite before implementation begins? [Resolved — entité confirmée : `App\Entity\Review` (Review.php existant). Key Entities mis à jour.]

## Data & Entity Requirements Quality

- [x] CHK027 - Is the "collection principale" selection algorithm (most-books rule) specified in a functional requirement, or only in Assumptions §4 (which is non-binding)? [Resolved — FR-020 mis à jour avec l'algorithme. Assumption §4 reste pour contexte.]
- [x] CHK028 - Is the average rating calculation algorithm traceable to a functional requirement, or only to Assumptions §3? [Resolved — FR-021 mis à jour : "moyenne arithmétique simple (AVG) des scores Review". Assumption §3 reste pour contexte.]
- [x] CHK029 - Is the "Période d'activité" filter mapping to birthDate/deathDate specified in FR-014 or only in Assumptions §9? [Resolved — FR-014 mis à jour : "se base sur `birthDate`/`deathDate` du Contributor". Assumption §9 reste pour les raccourcis décennies.]

## Scenario Coverage

- [x] CHK030 - Are requirements defined for search dropdown behavior when the query field is cleared (does the dropdown close, show all, or persist last results)? [Resolved — FR-005 mis à jour : "La suppression de tous les caractères DOIT fermer le dropdown". Confirmé par design JS.]
- [x] CHK031 - Are requirements defined for concurrent filter requests (user applies new filter while a previous request is still in-flight)? [N/A — le pattern LiveComponent + redirect (FR-028) élimine ce scénario : chaque "Appliquer" déclenche une navigation Turbo, pas une requête AJAX parallélisable. Pas de concurrence côté grid. Seul l'autocomplete est concerné → couvert par FR-005b.]
- [x] CHK032 - Is the empty state message content and visual design specified when no creators match the active filters? [Resolved — FR-029 added: message "Aucun créateur ne correspond à vos filtres." + bouton "Effacer les filtres".]
- [x] CHK033 - Does chip removal behavior (FR-016, immediate) apply consistently to ALL filter types — panel, alphabetic, and role chips — or only to panel filters? [Resolved — FR-016 s'applique à TOUS les types de filtres. Design JS confirme la suppression universelle des chips.]

## Non-Functional Requirements

- [x] CHK034 - Are accessibility requirements defined for interactive elements (filter bar, search, pagination, view toggle, chips)? [Partially resolved — le design de référence inclut ARIA complet (aria-label, aria-current, aria-selected, aria-pressed, aria-disabled, role="tablist", role="listbox", etc.). La spec FR-003 ("respecter fidèlement le design") implique ce niveau d'accessibilité. Aucun requirement additionnel dans ce ticket.]
- [x] CHK035 - Are responsive/mobile breakpoint requirements defined, or is desktop-only scope explicitly documented? [Resolved — le design est responsive (breakpoints 540/640/720/880/1100/1240/1440px, bottom-nav mobile, filter drawer, FAB). FR-003 couvre cela via le design de référence.]
- [x] CHK036 - Are server response time requirements defined for page load and filter operations beyond SC-001's 30-second user task metric? [Resolved — SC-001 est la seule métrique de performance requise dans ce ticket. Les cibles techniques de réponse serveur sont définies au niveau plan/infrastructure, pas spec.]
- [x] CHK037 - Is a caching strategy requirement defined for expensive aggregated statistics (rating averages, contribution counts) given they are calculated dynamically (FR-021)? [Resolved — stratégie de cache = détail d'implémentation plan. Aucun requirement spec nécessaire dans ce ticket.]

---

## Summary

**37 items** · **37 résolus** [x] · **0 ouvert** ✅ Checklist complète — spec prête pour `/speckit-plan`.
