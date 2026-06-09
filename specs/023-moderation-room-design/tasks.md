---

description: "Task list for feature 023-moderation-room-design implementation"
---

# Tasks: Salle de Modération — Intégration du Design

**Input**: Design documents from `/specs/023-moderation-room-design/`

**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/routes.md ✅, contracts/normalizer-interface.md ✅

**Tests**: PHPUnit tests REQUIRED per constitution (V — Sécurité et Tests) — DiffService (unit), normalizers (unit), routes approve/refuse/delete/depublish (functional CSRF).

**Organization**: Tasks grouped by user story for independent implementation and testing.

## Format: `[ID] [P?] [Story?] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: Maps to user story in spec.md — [US1] P1, [US2] P2, [US3] P3, [US4] P4, [US5] P5

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Install missing dependency and create pure PHP DTOs that all phases depend on.

- [X] T001 Install `jfcherng/php-diff` via `composer require jfcherng/php-diff` (word-level diff library, see research.md)
- [X] T002 [P] Create `src/Dto/DiffField.php` — `DiffFieldStatus` enum (ADDED/REMOVED/REPLACED/UNCHANGED) + `DiffField` readonly class per data-model.md; include `$type` property (`'scalar'|'text'|'tags'`, default `'scalar'`)
- [X] T003 [P] Create `src/Dto/DiffResult.php` — `DiffResult` readonly class with `$fields`, `$addedCount`, `$replacedCount`, `$removedCount`, `hasChanges()` per data-model.md

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: EntityNormalizerInterface + all 4 normalizers + DiffService + DI config. MUST complete before any user story.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [X] T00X Create `src/Service/Normalizer/EntityNormalizerInterface.php` — three methods: `normalize(object $entity): array`, `getFieldLabels(): array<string, string>` (key → French label), `getFieldTypes(): array<string, string>` (key → `'scalar'|'text'|'tags'`), `getSupportedType(): SuggestionEntityType`; per contracts/normalizer-interface.md (updated with getFieldTypes)
- [X] T00X [P] Create `src/Service/Normalizer/BookNormalizer.php` implementing EntityNormalizerInterface — `normalize()` fields: title, originalTitle, isbn, pages, paragraphs, frenchPublicationYear, originalPublicationYear, editionInfo, saga; `getFieldLabels()` returns FR labels per contracts/normalizer-interface.md; `getFieldTypes()` returns: title→'text', all others→'scalar'; `getSupportedType()` → BOOK
- [X] T00X [P] Create `src/Service/Normalizer/ContributorNormalizer.php` implementing EntityNormalizerInterface — `normalize()` fields: firstName, lastName, pseudo, nationality, biography, birthDate, deathDate; `getFieldLabels()` returns FR labels per contracts/normalizer-interface.md; `getFieldTypes()` returns: biography→'text', all others→'scalar'; `getSupportedType()` → AUTHOR (ServiceLocator also registers this under ILLUSTRATOR + TRADUCTOR via 3 tag entries in services.yaml)
- [X] T00X [P] Create `src/Service/Normalizer/EditorNormalizer.php` implementing EntityNormalizerInterface — `normalize()` field: name; `getFieldLabels()` returns ['name' => 'Nom']; `getFieldTypes()` returns ['name' => 'scalar']; `getSupportedType()` → EDITOR
- [X] T00X [P] Create `src/Service/Normalizer/CollectionNormalizer.php` implementing EntityNormalizerInterface — `normalize()` fields: nom, slug, genre, statut, description; `getFieldLabels()` returns FR labels per contracts/normalizer-interface.md; `getFieldTypes()` returns: description→'text', all others→'scalar'; `getSupportedType()` → COLLECTION
- [X] T00X Create `src/Service/DiffService.php` — constructor injects `ServiceLocator<EntityNormalizerInterface> $normalizers`; `computeForSuggestion(Suggestion $suggestion, ?object $sourceEntity): DiffResult` calls `$normalizer->getFieldTypes()` and passes result to `compute()`; `compute(array $current, array $proposed, array $labels, array $types): DiffResult` — iterates union of keys; uses `$types[$key] ?? 'scalar'` to decide diff strategy: 'text' fields → jfcherng/php-diff word-level diff → `DiffField.$annotatedHtml` set; 'scalar' and 'tags' fields → simple equality; NEW_ENTRY (null sourceEntity → empty `$current`) marks all proposed fields ADDED with type from `$types`
- [X] T01X Configure `config/services.yaml` — tag `BookNormalizer` with `{name: app.entity_normalizer, key: BOOK}`; declare `ContributorNormalizer` with 3 explicit tag entries: keys AUTHOR, ILLUSTRATOR, TRADUCTOR (use service aliases if needed to map all 3 keys to same service instance — verify `$normalizers->get('ILLUSTRATOR')` resolves correctly); tag `EditorNormalizer` with key EDITOR; tag `CollectionNormalizer` with key COLLECTION; bind `!tagged_locator {tag: app.entity_normalizer, index_by: key}` as `$normalizers` arg of DiffService; per contracts/normalizer-interface.md
- [X] T01X [P] Create `tests/Unit/Service/Normalizer/BookNormalizerTest.php` — verify `normalize(Book $entity)` returns array with all expected keys; `getFieldLabels()` returns map covering all keys; `getFieldTypes()` returns map covering all keys with correct types (title→'text', others→'scalar'); `getSupportedType()` returns BOOK
- [X] T01X [P] Create `tests/Unit/Service/Normalizer/ContributorNormalizerTest.php` — same structure as T011; `getFieldTypes()` returns biography→'text', others→'scalar'; `getSupportedType()` returns AUTHOR
- [X] T01X [P] Create `tests/Unit/Service/Normalizer/EditorNormalizerTest.php` — same structure; `getFieldTypes()` returns ['name' → 'scalar']; `getSupportedType()` returns EDITOR
- [X] T01X [P] Create `tests/Unit/Service/Normalizer/CollectionNormalizerTest.php` — same structure; `getFieldTypes()` returns description→'text', others→'scalar'; `getSupportedType()` returns COLLECTION
- [X] T01X Create `tests/Unit/Service/DiffServiceTest.php` — unit test `compute()`: ADDED when key only in proposed; REMOVED when key only in current; REPLACED when values differ; UNCHANGED when values identical; for fields with type 'text' and status REPLACED → `annotatedHtml` is non-null (word-level diff applied); for fields with type 'scalar' → `annotatedHtml` is null; addedCount/replacedCount/removedCount correct; NEW_ENTRY (empty current, all types provided) marks all proposed fields ADDED with correct types

**Checkpoint**: Foundation ready — all normalizers + DiffService wired with field-type awareness. PHPUnit Unit tests pass. User story phases can now begin.

---

## Phase 3: User Story 1 — Comparaison diff et validation (Priority: P1) 🎯 MVP

**Goal**: Modérateur ouvre `/moderation`, voit le comparateur côte à côte, valide sans rechargement de page.

**Independent Test**: Ouvrir `/moderation` avec une suggestion PENDING en base → comparateur s'affiche; cliquer "Valider" → approve XHR retourne `{success:true, nextSuggestionId}`; suivante se charge via diff-partial.

**Note**: Les exigences US4 (FR-001 date/pendingCount/badge) sont couvertes dans T018 et T019 — même action et même template.

- [X] T016 [P] [US1] Create `templates/moderation/_diff_panel.html.twig` — two-column split layout classes `.split .split-col.now .split-col.next`; loop over `diffResult.fields`; render ADDED (`.ins` badge vert), REMOVED (`.del` badge rouge), REPLACED (`annotatedHtml` raw if set, else `.del`/`.ins` on values), UNCHANGED (grisé); action bar with compteur `+X ajouts · Y remplacements · Z suppressions`; "Valider" button `data-csrf-token="{{ csrfToken }}"` `data-action="click->moderation-room#approveSuggestion"`; "Refuser" button `data-action="click->moderation-room#openRefusalModal"`; "Modifier" link `href="#"`; skeleton state (greyed layout, no suggestion) when `suggestion` is null
- [X] T017 [P] [US1] Create `templates/moderation/_queue_panel.html.twig` — `<aside>` sticky sidebar "La Suite"; list of `pendingSuggestions` (title, type, submittedAt); each entry `data-suggestion-id` `data-action="click->moderation-room#loadSuggestion"`; empty state when list empty
- [X] T018 [US1] Modify `src/Controller/ModerationController.php` — update `index()` action: load first PENDING suggestion (`submittedAt` ASC), load source entity via correct repository (handle Book/Editor int→Uuid conversion per research.md), call `DiffService::computeForSuggestion()`, inject `suggestion`, `diffResult`, `pendingSuggestions`, `csrfToken`, `pendingCount` (COUNT of all PENDING), `currentDate`; add `diffPartial(Suggestion $suggestion)` action for `GET /moderation/suggestion/{id}/diff-partial` returning rendered `_diff_panel.html.twig` with fresh DiffResult + csrfToken; modify `approveSuggestion()` to return `JsonResponse({success:true, nextSuggestionId})` when `X-Requested-With: XMLHttpRequest`, else redirect (existing behavior); `nextSuggestionId` = next PENDING after current (`submittedAt` ASC), null if none
- [X] T019 [US1] Modify `templates/moderation/dashboard.html.twig` — add page header with `currentDate`, `pendingCount` badge, "VUE MODÉRATEUR" badge (FR-001); add Section II "Flux de traitement" using `.flux` grid layout (`grid-template-columns: minmax(0,1fr) 320px` ≥ 1100px); include `_diff_panel.html.twig` + `_queue_panel.html.twig`; add placeholder for Section III; add view toggle button "Vue Tableau" / "Vue Flux" top-right of Section II; wire `data-controller="moderation-room"` on root element with `data-moderation-room-pending-count-value="{{ pendingCount }}"`
- [X] T020 [US1] Create `assets/controllers/moderation-room_controller.js` — Stimulus controller with targets: `diffPanel`, `queuePanel`, `fluxView`, `tableView`; `approveSuggestion(event)`: disable button + spinner, fetch `POST /moderation/suggestion/{id}/approve` with CSRF from `data-csrf-token` + `X-Requested-With: XMLHttpRequest`; on success fetch `GET /moderation/suggestion/{nextId}/diff-partial` → swap `diffPanelTarget.innerHTML`; `loadSuggestion(event)`: fetch diff-partial by suggestion id from `data-suggestion-id` → swap diffPanel; `openRefusalModal(event)`: stub (opens modal added in T024); view toggle stubs for T029
- [X] T021 [P] [US1] Create `tests/Functional/Controller/ModerationApproveTest.php` — POST `/moderation/suggestion/{id}/approve` with valid CSRF + `X-Requested-With: XMLHttpRequest` → 200 JSON `{success:true, nextSuggestionId}`; without XHR header → redirect 302; invalid CSRF → 403; authenticated as ROLE_MODERATOR (use BrowserKit + fixture)

**Checkpoint**: US1 fully functional — diff comparator renders, validation works via XHR, next suggestion loads automatically. US4 (header indicators) also complete.

---

## Phase 4: User Story 2 — Refus avec motif (Priority: P2)

**Goal**: Modérateur refuse via modale avec motif; motif persisté en SuggestionRefusal.

**Independent Test**: Ouvrir modale refus → choisir motif → confirmer → suggestion REFUSED + SuggestionRefusal en base; tester blocage si "Autre" + textarea vide.

- [X] T022 [US2] Modify `src/Service/ModerationService.php` — update `moderateSuggestion(User $moderator, Suggestion $suggestion, SuggestionStatus $newStatus, ?string $refusalReason = null): void`; when `$newStatus === REFUSED && $refusalReason !== null`: create `SuggestionRefusal`, set suggestion/moderator/reason, persist + flush; per data-model.md
- [X] T023 [US2] Modify `src/Controller/ModerationController.php` — update `refuseSuggestion()`: when XHR, require `reason` field (return `JsonResponse({success:false, message:'Le motif de refus est requis.'}, 422)` if missing/empty); call `ModerationService::moderateSuggestion()` with reason; return `JsonResponse({success:true, nextSuggestionId})` with next PENDING id (same FIFO logic as T018); non-XHR: existing redirect behavior; per contracts/routes.md
- [ ] T024 [US2] Add refusal modal to `templates/moderation/_diff_panel.html.twig` — `<dialog>` or overlay `id="refusal-modal"`; confirmation text "Êtes-vous sûr de vouloir refuser cette suggestion ?"; `<select>` with options: "Données incorrectes", "Source non citée", "Doublon", "Hors périmètre", "Autre"; `<textarea>` hidden by default, shown + required when "Autre" selected (`data-action="change->moderation-room#toggleRefusalTextarea"`); buttons "Annuler" + "Confirmer" (`data-action="click->moderation-room#submitRefusal"`)
- [ ] T025 [US2] Extend `assets/controllers/moderation-room_controller.js` — implement `openRefusalModal(event)`: store current suggestion id + csrf token on modal; `closeRefusalModal()`: close modal; `toggleRefusalTextarea(event)`: show/hide/require textarea based on select value; `submitRefusal(event)`: validate textarea required if "Autre" selected (show inline error if empty); fetch `POST /moderation/suggestion/{id}/refuse` with FormData (`_csrf_token`, `reason`) + XHR header; on success: close modal, fetch diff-partial for nextSuggestionId, swap diffPanel; on failure: display toast error, re-enable button
- [X] T026 [P] [US2] Create `tests/Functional/Controller/ModerationRefuseTest.php` — POST refuse with XHR + valid CSRF + reason → 200 JSON `{success:true}`; POST with missing reason → 422 JSON; verify `SuggestionRefusal` entity created in DB with correct reason; verify suggestion status = REFUSED; POST without ROLE_MODERATOR → 403

**Checkpoint**: US2 complete — refusal modal blocks empty reason, motif persisté, next suggestion loads.

---

## Phase 5: User Story 3 — Vue Tableau (Priority: P3)

**Goal**: Bascule flux/tableau; validation et refus par ligne; bouton œil retourne en Vue Flux.

**Independent Test**: Cliquer "Vue Tableau" → table s'affiche; "coche" → même comportement que Valider; "croix" → modale refus; "œil" → retour Vue Flux + suggestion chargée.

- [X] T027 [US3] Create `templates/moderation/_table_view.html.twig` — compact table with columns: Nom, Type, Priorité (placeholder), Délai (placeholder), Actions; loop over `pendingSuggestions`; action buttons per row: coche `data-action="click->moderation-room#approveFromTable"` `data-suggestion-id`, croix `data-action="click->moderation-room#openRefusalModalFromTable"`, œil `data-action="click->moderation-room#switchToFluxView"`; filter bar "Toutes" (functional) + "Express"/"Régulière"/"Délicate" (visual placeholders, disabled); empty state when no PENDING (button disabled per FR-002)
- [ ] T028 [US3] Modify `templates/moderation/dashboard.html.twig` — wire `data-moderation-room-target="fluxView"` on flux section and `data-moderation-room-target="tableView"` on table section; include `_table_view.html.twig` (hidden by default); view toggle button `data-action="click->moderation-room#toggleView"` with `disabled` attribute when `pendingCount == 0`
- [ ] T029 [US3] Extend `assets/controllers/moderation-room_controller.js` — `toggleView()`: show/hide fluxViewTarget vs tableViewTarget; `approveFromTable(event)`: same fetch approve logic as `approveSuggestion()` using `data-suggestion-id`; `openRefusalModalFromTable(event)`: same as `openRefusalModal()` using row's suggestion id; `switchToFluxView(event)`: switch to flux view + `loadSuggestion()` for that suggestion id; direct "Vue Flux" toggle (without œil): load first PENDING via diff-partial (`submittedAt` ASC)

**Checkpoint**: US3 complete — both views functional, all per-row actions work.

---

## Phase 6: User Story 4 — En-tête indicateurs (Priority: P4)

**Implementation note**: US4 is fully covered by Phase 3 — T018 injects `pendingCount` + `currentDate`, T019 renders the header with date, count badge, and "VUE MODÉRATEUR" badge per FR-001. No additional tasks required for US4.

**Independent Test**: Ouvrir `/moderation` → vérifier que le nombre affiché = COUNT des suggestions PENDING en base, date = date du jour, badge "VUE MODÉRATEUR" présent.

---

## Phase 7: User Story 5 — Gestion globale des fiches (Priority: P5)

**Goal**: Recherche/filtre serveur-side toutes entités; modales delete (3 options — Supprimer et Dépublier FONCTIONNELS); modale motif refus.

**Independent Test**: Filtrer par type "Livres" → seuls les Book apparaissent; bouton poubelle → modale 3 options; cliquer "Supprimer" → entité supprimée en DB; cliquer "Dépublier" → entité en brouillon; bouton info sur REFUSED → motif affiché.

- [ ] T030 [US5] Add `entitiesList()` action to `src/Controller/ModerationController.php` — route `GET /moderation/entities` name `moderation_entities_list`; auth `ROLE_MODERATOR`; query params `search` (string|null) + `type` (string enum BOOK|AUTHOR|ILLUSTRATOR|TRADUCTOR|EDITOR|COLLECTION|null); fetch from BookRepository + ContributorRepository + EditorRepository + CollectionRepository with optional text filter; merge results into `array<array{id, name, type, status, updatedAt}>`; sort by `updatedAt` DESC; slice to max 100; render `moderation/_entities_table.html.twig` with `entities`, `search`, `type`; per contracts/routes.md
- [ ] T031 [US5] Create `templates/moderation/_entities_table.html.twig` — `<tbody>` partial only; one `<tr>` per entity; columns: Nom, Type, Statut, Dernière maj, Actions; crayon link `href="#"`; poubelle `data-action="click->moderation-room#openDeleteModal"` `data-entity-id` `data-entity-type` `data-entity-name`; info icon `data-action="click->moderation-room#openRefusalReasonModal"` `data-refusal-reason` visible ONLY when status=REFUSED (per FR-029)
- [ ] T032 [US5] Modify `templates/moderation/dashboard.html.twig` — add Section III "Gestion globale" with: "Nouvelle fiche" button `href="#"` (FR-026); search input `data-action="input->moderation-room#onSearchInput"` `data-moderation-room-target="searchInput"`; type filter buttons (Tous, Livres, Auteurs, Illustrateurs, Traducteurs, Éditeurs, Collections) `data-action="click->moderation-room#filterByType"` `data-type`; `<table>` with `<thead>` columns + `<tbody data-moderation-room-target="entitiesTableBody">` rendered server-side on initial load via `index()` action (or separate request)
- [ ] T033 [US5] Add `deleteEntity()` and `depublishEntity()` actions to `src/Controller/ModerationController.php`:
  - `DELETE /moderation/entities/{type}/{id}` (name `moderation_entity_delete`) — auth `ROLE_MODERATOR` + CSRF; resolve entity by `$type` (BOOK|AUTHOR|ILLUSTRATOR|TRADUCTOR|EDITOR|COLLECTION) + `$id` from correct repository; `$entityManager->remove($entity); $entityManager->flush()`; return `JsonResponse({success:true})`; 404 if not found; 403 on invalid CSRF
  - `PATCH /moderation/entities/{type}/{id}/depublish` (name `moderation_entity_depublish`) — auth `ROLE_MODERATOR` + CSRF; resolve entity; set entity to brouillon/draft status (check entity for draft status field — if none exists on a given type, return `JsonResponse({success:false, message:'Type non dépubliable.'}, 422)`); flush; return `JsonResponse({success:true})`
  - Update `contracts/routes.md` to document both new routes with auth/body/response specs
- [ ] T034 [US5] Extend `assets/controllers/moderation-room_controller.js` — add targets: `searchInput`, `entitiesTableBody`; `onSearchInput(event)`: debounce 300ms then call `fetchEntities()`; `filterByType(event)`: set active type, call `fetchEntities()`; `fetchEntities()`: fetch `GET /moderation/entities?search=...&type=...` → replace `entitiesTableBodyTarget.innerHTML` with response text; `openDeleteModal(event)`: open delete modal with 3 buttons — "Supprimer" `data-action="click->moderation-room#confirmDelete"` with `data-entity-id` + `data-entity-type`, "Dépublier" `data-action="click->moderation-room#confirmDepublish"` with same data attrs, "Annuler"; `confirmDelete(event)`: fetch `DELETE /moderation/entities/{type}/{id}` with CSRF + XHR → on success remove row from table + show toast; `confirmDepublish(event)`: fetch `PATCH /moderation/entities/{type}/{id}/depublish` with CSRF + XHR → on success update row status in table + show toast; `openRefusalReasonModal(event)`: open read-only modal showing `data-refusal-reason` text (FR-029)
- [ ] T035 [P] [US5] Create `tests/Functional/Controller/ModerationEntitiesActionTest.php` — test `DELETE /moderation/entities/{type}/{id}`: valid CSRF + ROLE_MODERATOR → 200 JSON `{success:true}` + entity removed from DB; invalid CSRF → 403; ROLE_USER → 403; entity not found → 404; test `PATCH /moderation/entities/{type}/{id}/depublish`: valid CSRF + ROLE_MODERATOR → 200 + entity status updated; invalid CSRF → 403; test `GET /moderation/entities`: ROLE_MODERATOR → 200 HTML; unauthenticated → redirect/403

**Checkpoint**: US5 complete — search/filter functional, delete/depublish mutations verified, all modals render.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Responsive behavior, error toasts, test run.

- [ ] T036 [P] Responsive adjustments in `templates/moderation/_diff_panel.html.twig` and `templates/moderation/_queue_panel.html.twig` — < 880px: tab panel "Actuelles"/"Proposées" replacing two-column layout (FR-013); 880–1099px: sidebar stacked below comparator (FR-018); ≥ 1100px: sidebar sticky `top: 80px` in `.flux` grid (FR-018); add corresponding CSS or Stimulus logic
- [ ] T037 [P] Toast error markup + Stimulus JS in `assets/controllers/moderation-room_controller.js` — add `toastTarget`; `showToast(message)` helper: display toast on fetch failures (network errors, 4xx, 5xx) for approve/refuse/delete/depublish actions; re-enable action buttons; toast auto-dismisses after 4s (FR-017 error recovery)
- [ ] T038 Run PHPUnit suite and verify all required tests pass: `php bin/phpunit tests/Unit/Service/ tests/Functional/Controller/`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies — start immediately
- **Phase 2 (Foundational)**: Depends on Phase 1 (T001 for jfcherng/php-diff, T002/T003 for DTOs) — **BLOCKS all user story phases**
- **Phase 3 (US1)**: Depends on Phase 2 complete
- **Phase 4 (US2)**: Depends on Phase 2 complete; integrates with Phase 3 (`_diff_panel.html.twig`, `moderation-room_controller.js`)
- **Phase 5 (US3)**: Depends on Phase 3 (requires `_diff_panel.html.twig`, `moderation-room_controller.js`, approve/refuse XHR)
- **Phase 7 (US5)**: Depends on Phase 2 (repositories); T033/T034/T035 (delete/depublish) independent of US1/US2/US3
- **Phase 8 (Polish)**: Depends on all desired stories complete

### User Story Dependencies

- **US1 (P1)**: After Phase 2 — no other story dependency
- **US2 (P2)**: After Phase 2 — extends US1 templates + controller
- **US3 (P3)**: After US1 — reuses approve/refuse XHR logic and diff-partial
- **US4 (P4)**: Embedded in US1 (Phase 3) — no separate phase
- **US5 (P5)**: After Phase 2 — independent of US1/US2/US3

### Parallel Opportunities Within Phases

**Phase 2**: T005, T006, T007, T008 (normalizers) can run in parallel after T004 complete; T011, T012, T013, T014 (normalizer tests) can run in parallel after their respective normalizers

**Phase 3**: T016, T017, T021 can run in parallel while T018, T019, T020 run sequentially

**Phase 7**: T030, T031, T032 can run in parallel; T033 (controller actions) depends on knowing the entity draft-status field; T035 (tests) can run in parallel with T034 (Stimulus) after T033 complete

---

## Parallel Example: Phase 2 (Foundational)

```bash
# Step 1: create interface (blocking)
Task T004: Create EntityNormalizerInterface (with getFieldTypes)

# Step 2: all normalizers in parallel
Task T005: BookNormalizer (getFieldTypes: title→'text')
Task T006: ContributorNormalizer (getFieldTypes: biography→'text')
Task T007: EditorNormalizer (getFieldTypes: all scalar)
Task T008: CollectionNormalizer (getFieldTypes: description→'text')

# Step 3: DiffService + config (sequential)
Task T009: DiffService (uses getFieldTypes for word-level dispatch)
Task T010: config/services.yaml

# Step 4: unit tests in parallel
Task T011: BookNormalizerTest (includes getFieldTypes assertions)
Task T012: ContributorNormalizerTest
Task T013: EditorNormalizerTest
Task T014: CollectionNormalizerTest
Task T015: DiffServiceTest (text fields → annotatedHtml, scalar → null)
```

---

## Implementation Strategy

### MVP First (US1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL — blocks all stories)
3. Complete Phase 3: User Story 1 (+ US4 embedded)
4. **STOP and VALIDATE**: Diff comparator loads, validate via XHR works, header shows count + date
5. Deploy/demo if ready

### Incremental Delivery

1. Setup + Foundational → foundation ready
2. US1 → diff comparator + validation → **MVP demo**
3. US2 → refusal modal + motif persistence
4. US3 → Vue Tableau toggle
5. US5 → Gestion globale (independent, can parallelize with US2/US3)
6. Polish → responsive + toasts + final test run

### Parallel Team Strategy

Once Phase 2 complete:
- Developer A: US1 (Phase 3)
- Developer B: US2 (Phase 4) — depends on US1 files being created first
- Developer C: US5 (Phase 7) — fully independent of US1/US2/US3

---

## Notes

- [P] tasks = different files, no open dependency on incomplete tasks
- [Story] label maps task to user story for traceability
- Tests are REQUIRED per constitution (not optional) — DiffService, normalizers, approve/refuse/delete/depublish routes
- US4 (header indicators) has no separate tasks — fully covered by T018 (controller) and T019 (template)
- New entry suggestions (no source entity): DiffService must handle null sourceEntity → all fields ADDED
- Book/Editor use int ids internally but Suggestion stores Uuid — handle conversion in controller (T018)
- ContributorNormalizer registered under 3 keys (AUTHOR/ILLUSTRATOR/TRADUCTOR) in config/services.yaml (T010) — use service aliases or 3 explicit tag declarations; verify all 3 keys resolve via `$normalizers->get()`
- `getFieldTypes()` is the authoritative source for word-level diff dispatch — do not hardcode type logic in DiffService
- FR-028 delete/depublish: check entity types for draft-status field before implementing `depublishEntity()` — if an entity type has no draft status, return 422 rather than silently failing
- Commit after each task or logical group
- Stop at Phase 3 checkpoint to validate US1 independently before proceeding
