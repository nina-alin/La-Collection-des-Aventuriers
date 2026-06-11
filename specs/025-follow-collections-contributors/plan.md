# Implementation Plan: Système de Suivi — Créateurs & Collections

**Branch**: `025-follow-collections-contributors` | **Date**: 2026-06-10 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/025-follow-collections-contributors/spec.md`

## Summary

Implémentation du système de suivi (follow/unfollow) pour les Créateurs et les Collections, avec mise à jour optimiste côté client, notifications in-app asynchrones via Symfony Messenger déclenchées à la publication d'un Livre, déduplication des destinataires, et filtre "Uniquement ceux que je suis" dans les pages liste. Une nouvelle page liste Collections (`/collections`) doit être créée.

## Technical Context

**Language/Version**: PHP 8.2+

**Primary Dependencies**: Symfony 7.2 LTS, Doctrine ORM 3.6, Stimulus (via symfony/stimulus-bundle), Bootstrap (existing design system)

**Storage**: PostgreSQL (via Platform.sh) — Doctrine ORM exclusif

**Testing**: PHPUnit (existing `tests/` tree; `phpunit.dist.xml`)

**Target Platform**: Platform.sh (`.platform.app.yaml`, `.platform/routes.yaml`, `.platform/services.yaml`)

**Project Type**: Web application (Symfony monolith, Twig-rendered frontend with Stimulus progressive enhancement)

**Performance Goals**: Bouton follow ≤ 100 ms retour visuel (FR-003, SC-001) via optimistic update Stimulus

**Constraints**: Zéro doublon notification par publication par utilisateur (SC-003); transaction de publication non bloquée (async Messenger)

**Scale/Scope**: Batch unique (pas de chunking) jusqu'à ~1 000 abonnés par Créateur (Assumption)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| # | Principle | Assessment | Verdict |
|---|-----------|------------|---------|
| I | Complémentarité Stricte | Système de suivi = gestion personnelle de collection. Pas de forum, pas de news. | ✅ PASS |
| II | Architecture Symfony LTS | Controllers minces + Services + Doctrine ORM. Dépendances DI. `.platform.*` mis à jour si infra change (Messenger déjà présent — pas de nouveau service infra). | ✅ PASS |
| III | Workflow Validation Contenu | Follow/Unfollow ne crée pas de contenu éditorial. `UserFollowedContributor` et notifications sont des données personnelles → pas de workflow PENDING. | ✅ PASS |
| IV | RBAC / CSRF | Routes follow/unfollow : `#[IsGranted('ROLE_USER')]` + CSRF token. Visiteurs non-connectés → modal login. | ✅ PASS |
| V | Couverture Tests | Nouveaux tests requis : FollowController, BookFollowJobHandler, ContributorRepository (onlyFollowed), CollectionListController. | ✅ PASS (à tenir) |

## Project Structure

### Documentation (this feature)

```text
specs/025-follow-collections-contributors/
├── plan.md              # Ce fichier
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
└── tasks.md             # Phase 2 output (/speckit-tasks)
```

### Source Code (repository root)

```text
src/
├── Controller/
│   ├── FollowController.php                  [NEW] AJAX toggle follow/unfollow
│   └── CollectionListController.php          [NEW] /collections list page
├── Dto/
│   └── CollectionListFilterState.php         [NEW] filter DTO for collections list
├── Entity/
│   ├── UserFollowedContributor.php           [NEW] relation User ↔ Contributor
│   └── Book.php                              [MODIFY] +followNotificationSentAt
├── Entity/Enum/
│   └── NotificationType.php                  [MODIFY] +FOLLOW_NOVELTY
├── EventListener/
│   └── BookPublishedFollowListener.php       [NEW] dispatches BookFollowJob on publish
├── Messenger/
│   ├── Message/
│   │   └── BookFollowJob.php                 [NEW] carries bookId (UUID string)
│   └── Handler/
│       └── BookFollowJobHandler.php          [NEW] computes recipients + creates notifications
├── Repository/
│   ├── UserFollowedContributorRepository.php [NEW]
│   └── ContributorRepository.php            [MODIFY] implement onlyFollowed filter
assets/
└── controllers/
    └── follow_controller.js                  [NEW] Stimulus optimistic update
migrations/
└── Version*.php                              [NEW] user_followed_contributor + followNotificationSentAt
templates/
├── collections/                              [NEW DIR]
│   └── index.html.twig                      [NEW] collections list page
├── createurs/
│   └── index.html.twig                      [MODIFY] wire follow buttons + login modal + followed toggle
├── collection/
│   └── show.html.twig                       [MODIFY] wire follow button to AJAX
└── components/
    └── _follow_login_modal.html.twig         [NEW] modal connexion partagée
```

**Structure Decision**: Symfony monolith existant. Pas de micro-frontend. Stimulus pour progressive enhancement (pattern déjà utilisé). Nouveau `FollowController` dédié aux endpoints AJAX pour éviter de coupler la logique follow aux controllers métier existants (`CollectionController`, `CreateursController`).

## Complexity Tracking

> Pas de violations Constitution — section non applicable.

---

## Phase 0: Research

### R-001 — Messenger retry configuration

**Decision**: Symfony Messenger utilise la configuration par défaut (3 tentatives, backoff 1s/2s/4s) car le fichier `config/packages/messenger.yaml` ne définit pas de bloc `retry_strategy`. La DLQ (`failed`) est déjà configurée.

**Action plan**: Ajouter un bloc `retry_strategy` explicite dans `messenger.yaml` pour documenter le comportement :
```yaml
transports:
  async:
    dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
    retry_strategy:
      max_retries: 3
      multiplier: 2
      delay: 1000
```

**Rationale**: Les specs mentionnent "3 tentatives" — les rendre explicites évite toute surprise si les defaults Symfony changent.

---

### R-002 — Entité UserFollowedContributor vs UserCollectionSubscription

**Observation**: `UserCollectionSubscription` existe déjà et couvre `UserFollowedCollections` de la spec. L'entité est complète (UNIQUE, CASCADE, `createdAt`). Les routes subscribe/unsubscribe sur `CollectionController` utilisent POST form + redirect.

**Decision**:
- **Pas de renommage** de `UserCollectionSubscription` (migration risquée, inutile).
- Nouvelle entité `UserFollowedContributor` pour les Créateurs (align spec naming, cohérent avec le fait que l'entité Créateur s'appelle `Contributor`).
- Les routes existantes `collection_subscribe` / `collection_unsubscribe` sont **conservées** comme fallback non-JS. Nouveaux endpoints AJAX dans `FollowController` pour les deux types.

---

### R-003 — Pattern optimistic update (FR-003)

**Decision**: Stimulus controller `follow_controller.js` avec pattern :
1. Clic → toggle état visuel immédiat (`aria-pressed`, classe CSS)
2. Désactiver bouton (`disabled`)
3. `fetch POST` vers endpoint AJAX avec CSRF token
4. Succès → conserver état togglé, réactiver bouton
5. Erreur → rollback état, réactiver bouton, afficher toast (4s)

**CSRF token**: Passé dans `data-follow-token-value` (rendu Twig côté serveur). Token name : `follow_contributor_{id}` et `follow_collection_{id}`.

**Endpoints JSON**:
- `POST /follow/contributor/{id}` → `{"followed": true/false, "token": "<new_csrf_token>"}`
- `POST /follow/collection/{id}` → `{"followed": true/false, "token": "<new_csrf_token>"}`

Réponse inclut un nouveau token CSRF pour permettre des toggles successifs sans rechargement.

---

### R-004 — Déduplication des notifications (FR-008, FR-013)

**Decision**: Un seul message Messenger `BookFollowJob(bookId)` dispatché par `BookPublishedFollowListener`. Le handler `BookFollowJobHandler` :
1. Charge le `Book` et vérifie `followNotificationSentAt` (si non-null → early return)
2. Set `followNotificationSentAt = now()` et flush (verrou optimiste — FR-013)
3. Calcule les destinataires et leur template prioritaire :
   - Requête unique : tous les users qui suivent au moins un Contributor ou la Collection du livre
   - Pour chaque user : détermine le template selon priorité (Auteur > Illustrateur > Traducteur > Collection)
4. Crée une `Notification` par user (type `FOLLOW_NOVELTY`, sourceId = `"follow_book_{bookId}"`)
5. Batch persist + flush

La contrainte `UNIQUE(user_id, source_id)` sur `Notification` sert de filet de sécurité supplémentaire.

**Pas de `NotificationMessage` async** dans ce handler (les notifications sont créées directement en DB depuis le job — le job lui-même est async). Utiliser `NotificationMessage` pour chaque user serait N messages dans la queue, introduisant des fenêtres de race condition.

---

### R-005 — Filtre "Uniquement ceux que je suis" — Contributor

**Observation**: `ContributorFilterState.onlyFollowed` existe déjà dans le DTO (bool). Le paramètre URL actuel est `?onlyFollowed=1`. La spec dit `?followed=true` mais accepte la convention existante (`?onlyFollowed=1` → spec-compliant car la valeur logique est correcte).

**Decision**: Garder `?onlyFollowed=1` pour les Créateurs (migration URL inutile — le DTO existant est stable). Ajouter dans `ContributorRepository.applyFilters()` le JOIN sur `UserFollowedContributor` lorsque `onlyFollowed = true`.

**Signature update**: `findPaginatedFiltered(ContributorFilterState $state, ?User $user = null)` — l'utilisateur courant est passé depuis le controller pour la clause `WHERE f.user = :user`.

---

### R-006 — Filtre "Uniquement ceux que je suis" — Collections

**Observation**: Pas de page liste Collections existante. La barre de navigation a un lien `href="#"` vers "Les collections". La spec (FR-012, US4) exige cette page.

**Decision**: Créer `/collections` route + `CollectionListController` + `CollectionListFilterState` DTO + `collections/index.html.twig`. Design inspiré de la page Créateurs (`/createurs`) pour cohérence UX. Filtre `?followed=true` pour la liste Collections (distinct du `?onlyFollowed=1` des Créateurs pour rester spec-compliant).

---

### R-007 — Modal connexion pour visiteurs (FR-004)

**Observation**: `modal_controller.js` et `components/Modal.html.twig` existent. Le bouton follow sur les cartes Créateurs est actuellement `type="button"` sans logique. Pour les visiteurs, un clic doit ouvrir une modal.

**Decision**: Le Stimulus `follow_controller.js` vérifie si `data-follow-logged-in-value` est `false` et ouvre une modal de connexion via `window.dispatchEvent(new CustomEvent('follow:login-required'))`. Un composant `_follow_login_modal.html.twig` (inclus dans les pages Créateurs et Collections) écoute cet event.

Alternativement (plus simple) : attribut `data-follow-login-url` sur le bouton, et le controller redirige si non-connecté. Mais la spec exige une **modale**, pas une redirection.

---

### R-008 — Messenger routing pour BookFollowJob

**Decision**: Ajouter dans `messenger.yaml` :
```yaml
routing:
  'App\Messenger\Message\BookFollowJob': async
```

---

## Phase 1: Design & Contracts

### data-model.md → voir [data-model.md](data-model.md)

### contracts/ → voir [contracts/](contracts/)

### quickstart.md → voir [quickstart.md](quickstart.md)

---

## Récapitulatif des fichiers à créer / modifier

### Nouveaux fichiers

| Fichier | Rôle |
|---------|------|
| `src/Entity/UserFollowedContributor.php` | Entité relation User ↔ Contributor |
| `src/Repository/UserFollowedContributorRepository.php` | Requêtes follow contributor |
| `src/Controller/FollowController.php` | Endpoints AJAX POST follow/unfollow |
| `src/Controller/CollectionListController.php` | Route GET /collections |
| `src/Dto/CollectionListFilterState.php` | DTO filtre liste Collections |
| `src/EventListener/BookPublishedFollowListener.php` | Dispatch `BookFollowJob` sur publish |
| `src/Messenger/Message/BookFollowJob.php` | Message Messenger |
| `src/Messenger/Handler/BookFollowJobHandler.php` | Handler : calcul recipients + notifications |
| `assets/controllers/follow_controller.js` | Stimulus optimistic update |
| `migrations/VersionXXX.php` | Table `user_followed_contributor` + `follow_notification_sent_at` |
| `templates/collections/index.html.twig` | Page liste Collections |
| `templates/components/_follow_login_modal.html.twig` | Modal connexion pour guests |

### Fichiers modifiés

| Fichier | Modification |
|---------|-------------|
| `src/Entity/Book.php` | + champ `followNotificationSentAt: ?\DateTimeImmutable` |
| `src/Entity/Enum/NotificationType.php` | + case `FOLLOW_NOVELTY = 'follow_novelty'` |
| `src/Repository/ContributorRepository.php` | Impl. `onlyFollowed` dans `applyFilters()` + signature `?User` |
| `src/Service/ContributeurService.php` | Passer `?User` aux méthodes repository |
| `src/Controller/CreateursController.php` | Passer `$this->getUser()` au service + `followedIds` au template |
| `config/packages/messenger.yaml` | Retry strategy explicite + routing `BookFollowJob` |
| `templates/createurs/index.html.twig` | Stimulus follow controller + modal + toggle sidebar |
| `templates/collection/show.html.twig` | Stimulus follow controller (remplace form POST) |
| `CLAUDE.md` | Pointer vers ce plan |

## Complexity Tracking

> Pas de violations Constitution.
