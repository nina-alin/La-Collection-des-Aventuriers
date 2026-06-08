---
description: "Task list for feature 022-contact-page"
---

# Tasks: Page "Nous Contacter" fonctionnelle

**Input**: Design documents from `/specs/022-contact-page/`

**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/contact-api.md ✅, quickstart.md ✅

**Tests**: PHPUnit tests included — explicitly requested in plan.md (tests fonctionnels ContactController + tests unitaires ContactMailerService).

**Organization**: Tasks grouped by user story for independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: Which user story this task belongs to ([US1]–[US4])
- File paths are relative to repository root

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Environment variables and DI configuration — required before any PHP code can be wired up.

- [X] T001 Add `CONTACT_EMAIL_FROM=` and `CONTACT_EMAIL_TO=` entries to `.env` and `.env.dist`
- [X] T002 Bind `$contactEmailFrom` and `$contactEmailTo` parameters for `ContactMailerService` in `config/services.yaml`

**Checkpoint**: Environment and DI ready — PHP service implementation can now begin.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: `ContactMailerService` is the shared dependency required by the controller POST route (US1). It must exist before the controller can be completed.

**⚠️ CRITICAL**: No user story POST handling can be implemented until this phase is complete.

- [X] T003 Create `ContactMailerService` in `src/Service/ContactMailerService.php`: inject `MailerInterface`, `string $contactEmailFrom`, `string $contactEmailTo` via DI; implement `send(?string $prenom, ?string $nom, ?string $pseudo, string $email, string $raison, string $message): void`; build subject `[Contact] {libellé raison} — {pseudo ?: "$prenom $nom"}`; use full raison-to-label mapping from data-model.md; construct `Email()` with `->from()` / `->to()` / `->subject()` / `->text()`; call `$this->mailer->send($mail)` — let exceptions propagate to the controller

**Checkpoint**: Foundation ready — user story implementation can now begin.

---

## Phase 3: User Story 1 - Visiteur non connecté soumet un formulaire (Priority: P1) 🎯 MVP

**Goal**: A public visitor can load `/contact`, fill and submit the form, and receive a confirmation message. Server-side validation and email sending work end-to-end.

**Independent Test**: Access `/contact` without being logged in, fill all required fields, submit → success message appears and fields are cleared.

### Tests for User Story 1 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [X] T004 [P] [US1] Create `ContactMailerServiceTest` in `tests/Service/ContactMailerServiceTest.php`: mock `MailerInterface`; test `send()` dispatches an `Email` with correct `from`, `to`, subject format `[Contact] {libellé} — {identifiant}`; test pseudo takes priority over prénom+nom in subject; test all nine raison labels; verify exception propagation
- [X] T005 [P] [US1] Create `ContactControllerTest` in `tests/Controller/ContactControllerTest.php`: test `GET /contact` returns 200 HTML; test `POST /contact/send` with valid JSON returns 200 `{"success":true}`; test invalid CSRF token returns 403 `{"success":false}`; test missing identity (no pseudo, no prénom+nom) returns 422; test invalid email returns 422; test invalid raison value returns 422; test empty message returns 422; test Mailer exception returns 500 `{"success":false}`; test malformed JSON body returns 400

### Implementation for User Story 1

- [X] T006 [P] [US1] Create `templates/contact/contact.html.twig` from `design/contact.html`: extend `base.html.twig`, copy full HTML structure and all CSS classes, add `<input type="hidden" name="_token" value="{{ csrf_token('contact') }}">` inside the form, add `<noscript>` block (FR-006b), copy JS validation block verbatim from design, extend the JS submit handler to: read `_token`, call `fetch('/contact/send', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({...})})`, disable the submit button during the request, show `.form-success` on `{"success":true}` and clear fields, display validation errors on `{"success":false}` responses
- [X] T007 [P] [US1] Create `ContactController` in `src/Controller/ContactController.php`: implement `GET /contact` route (`name: 'app_contact'`, `methods: ['GET']`), render `contact/contact.html.twig` with `userPseudo: null` and `userEmail: ''` (anonymous defaults for now — US2 will pass real values)
- [X] T008 [US1] Implement `POST /contact/send` (`name: 'app_contact_send'`) in `src/Controller/ContactController.php` (depends on T003, T007): decode JSON body (`json_decode`), return 400 if decode fails or Content-Type is not application/json; validate CSRF via `isCsrfTokenValid('contact', $data['_token'] ?? '')` → return 403 `{"success":false,"message":"Requête invalide."}`; validate identity, email (filter_var FILTER_VALIDATE_EMAIL), raison (whitelist from data-model.md), message (non-empty trim), field lengths (prénom/nom/pseudo ≤ 100, email ≤ 254, message ≤ 5000) → return 422 `{"success":false,"errors":[...]}`; call `ContactMailerService::send()`, catch `\Throwable` → return 500 `{"success":false,"message":"Une erreur est survenue, veuillez réessayer."}`; return 200 `{"success":true}`

**Checkpoint**: User Story 1 is fully functional and testable independently — public contact form works end-to-end.

---

## Phase 4: User Story 2 - Pré-remplissage pour les utilisateurs connectés (Priority: P2)

**Goal**: A logged-in user arrives at `/contact` with the "Pseudonyme" and "Email" fields pre-populated from their profile.

**Independent Test**: Log in, access `/contact` → "Pseudonyme" and "Email" fields are pre-filled with the profile data.

### Implementation for User Story 2

- [X] T009 [US2] Update `GET /contact` in `src/Controller/ContactController.php` to pass `userPseudo: $this->getUser()?->getPseudo()` (string|null) and `userEmail: $this->getUser()?->getEmail() ?? ''` to the template (depends on T007)
- [X] T010 [US2] Add `value="{{ userEmail }}"` and `value="{{ userPseudo ?? '' }}"` to the email and pseudo form inputs in `templates/contact/contact.html.twig` (depends on T006)
- [X] T011 [US2] Store pre-fill values as `data-default-pseudo` and `data-default-email` data attributes on the form element in `templates/contact/contact.html.twig`; update the JS reset handler to restore those values (not empty strings) when the user is connected — read from data attributes in the reset logic (depends on T010)

**Checkpoint**: User Stories 1 and 2 are independently functional — pre-fill works for connected users without breaking the public form.

---

## Phase 5: User Story 3 - Maillage interne du site (Priority: P3)

**Goal**: The site footer shows "Nous contacter" linking to `/contact`; the Mentions Légales page links to `/contact` in sections 2 and 5.

**Independent Test**: On any site page, the footer "Communauté" section shows "Nous contacter" with a working link to `/contact`. On the Mentions Légales page, both "page de contact" links navigate to `/contact`.

### Implementation for User Story 3

- [X] T012 [P] [US3] Replace the "Devenir modérateur" `<li>` item with `<li><a href="{{ path('app_contact') }}">Nous contacter</a></li>` in `templates/components/Layout/Footer.html.twig`
- [X] T013 [P] [US3] Replace both `href="#contact"` occurrences (sections 2 and 5) with `href="{{ path('app_contact') }}"` in `templates/legal/mentions-legales.html.twig`

**Checkpoint**: All site entry points to the contact page are correctly wired — footer and mentions légales link to `/contact`.

---

## Phase 6: User Story 4 - Panneau latéral "Avant d'écrire" fonctionnel (Priority: P4)

**Goal**: The sidebar on `/contact` has working links to Suggestions, the Moderation Dashboard, and Conditions d'utilisation (temporary `#`).

**Independent Test**: Access `/contact`, click each sidebar link → "Suggérer un livre" navigates to `/suggestions`, "Salle de modération" navigates to `/moderation`, "Conditions d'utilisation" stays on the page (`#`).

### Implementation for User Story 4

- [X] T014 [US4] Update sidebar link targets in `templates/contact/contact.html.twig`: "Suggérer un livre" → `href="{{ path('suggestions_index') }}"`, "Salle de modération" → `href="{{ path('moderation_dashboard') }}"`, "Conditions d'utilisation" → `href="#"` (temporary — route `app_cgu` is out of scope for this ticket) (depends on T006)

**Checkpoint**: All four user stories are fully functional and independently testable.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Visual verification, test suite pass, and quickstart validation.

- [X] T015 [P] Verify visual rendering of `/contact` matches `design/contact.html` at component level (layout, spacing, colours, typography, dark mode) — load the page in a browser and compare against the design file
- [X] T016 Run full PHPUnit test suite (`php bin/phpunit`) and confirm all tests pass with no regressions in existing tests
- [X] T017 Run quickstart validation: `symfony server:start`, open `http://localhost:8000/contact`, `bin/console debug:router | grep contact` — verify routes `app_contact` (GET) and `app_contact_send` (POST) are listed

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately; T001 and T002 are independent [P]
- **Foundational (Phase 2)**: Depends on Phase 1 (T002 must be done before DI works in T003)
- **User Story 1 (Phase 3)**: Depends on Phase 2 (T003 must exist for T008)
- **User Story 2 (Phase 4)**: Depends on Phase 3 (T007 must exist for T009; T006 must exist for T010/T011)
- **User Story 3 (Phase 5)**: Depends only on `app_contact` route existing (T007 — after Phase 3 checkpoint); T012 and T013 are independent [P] of each other
- **User Story 4 (Phase 6)**: Depends on T006 (template created in Phase 3)
- **Polish (Phase 7)**: Depends on all user story phases complete

### User Story Dependencies

- **US1 (P1)**: Requires Foundational (T003). No dependency on US2/US3/US4.
- **US2 (P2)**: Requires US1 controller (T007) and template (T006).
- **US3 (P3)**: Requires US1 route `app_contact` (T007). Independent of US2 and US4.
- **US4 (P4)**: Requires US1 template (T006). Independent of US2 and US3.

### Within Each User Story

- Tests (T004, T005) must be written **before** implementation — verify they FAIL
- T006 and T007 are parallel (different files)
- T008 depends on T007 (same file: ContactController.php)
- T009 → T010 → T011 are sequential within US2 (same files)
- T012 and T013 are parallel (different files)

---

## Parallel Opportunities

### Phase 1
```
T001 (.env/.env.dist) ║ T002 (services.yaml)   # different files
```

### Phase 3 — Tests first (write and ensure they FAIL)
```
T004 (ContactMailerServiceTest) ║ T005 (ContactControllerTest)   # different files
```

### Phase 3 — Implementation
```
T006 (contact.html.twig) ║ T007 (ContactController GET)   # different files
# then:
T008 (ContactController POST)   # depends on T003 + T007
```

### Phase 5
```
T012 (Footer.html.twig) ║ T013 (mentions-legales.html.twig)   # different files
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (T001, T002)
2. Complete Phase 2: Foundational (T003) — CRITICAL, blocks POST handling
3. Write tests Phase 3: T004, T005 (ensure they FAIL)
4. Implement Phase 3: T006, T007, T008
5. **STOP and VALIDATE**: `php bin/phpunit tests/` — US1 tests pass, `/contact` is publicly usable

### Incremental Delivery

1. Setup + Foundational → email service wired
2. US1 → public contact form end-to-end (**MVP!**)
3. US2 → pre-fill for connected users (UX improvement)
4. US3 → internal linking updated (site discoverability)
5. US4 → sidebar links functional (user guidance)
6. Polish → visual check + full test pass

### Parallel Team Strategy

With two developers after Phase 2 completes:
- Developer A: US1 (T004–T008), then US2 (T009–T011)
- Developer B: US3 (T012–T013), then US4 (T014)

---

## Notes

- [P] tasks operate on different files — no merge conflicts
- Each user story is independently completable and testable after its checkpoint
- T004 and T005 must FAIL before implementation (TDD)
- Raison whitelist (9 values) and label mapping are defined in `data-model.md` — use them verbatim in both `ContactMailerService` and controller validation
- The JS in `design/contact.html` is **copied verbatim** for validation logic; only the submit handler is extended (not rewritten)
- Route `app_cgu` is out of scope — sidebar "Conditions d'utilisation" uses `#` temporarily
- No Doctrine migration — `ContactMessage` is a DTO only, no persistence
