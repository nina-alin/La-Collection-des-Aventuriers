# Feature Specification: Système de Notifications In-App

**Feature Branch**: `017-inapp-notifications`

**Created**: 2026-06-02

**Status**: Draft

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Consulter et marquer les notifications (Priority: P1)

Un utilisateur connecté voit un badge rouge sur l'icône de cloche indiquant le nombre de notifications non lues. Il clique sur la cloche pour ouvrir le panneau, parcourt ses notifications regroupées par période (aujourd'hui / plus anciennes), puis clique sur une notification pour être redirigé vers la page concernée. La notification est automatiquement marquée comme lue et le panneau se ferme.

**Why this priority**: C'est le flux principal — sans ce mécanisme, les utilisateurs ne peuvent ni voir ni consommer leurs notifications. Toute la valeur du système repose sur cette interaction.

**Independent Test**: Peut être testé en injectant des notifications non lues en base, en ouvrant le panneau depuis la navbar et en vérifiant le badge, l'affichage groupé, le marquage individuel, la redirection et la fermeture automatique.

**Acceptance Scenarios**:

1. **Given** l'utilisateur a 3 notifications non lues, **When** il charge n'importe quelle page, **Then** la cloche affiche un badge rouge avec le chiffre "3"
2. **Given** le panneau est fermé, **When** l'utilisateur clique sur la cloche, **Then** le panneau s'ouvre et affiche les notifications groupées par période
3. **Given** le panneau est ouvert, **When** l'utilisateur clique en dehors ou reclique sur la cloche, **Then** le panneau se ferme
4. **Given** une notification est non lue, **When** l'utilisateur clique dessus, **Then** la pastille disparaît, le compteur baisse de 1, l'utilisateur est redirigé vers la cible, et le panneau se ferme
5. **Given** toutes les notifications sont lues, **Then** le badge disparaît de la cloche

---

### User Story 2 - Tout marquer comme lu (Priority: P2)

Un utilisateur avec plusieurs notifications non lues clique sur "Tout marquer lu" dans l'en-tête du panneau. Toutes les pastilles disparaissent immédiatement et le badge de la cloche passe à zéro.

**Why this priority**: Action de confort essentielle pour les utilisateurs ayant accumulé plusieurs alertes. Réduit la friction de gestion.

**Independent Test**: Peut être testé avec N notifications non lues : cliquer "Tout marquer lu" et vérifier que badge = 0 et toutes les pastilles sont absentes, sans rechargement de page.

**Acceptance Scenarios**:

1. **Given** 5 notifications non lues existent, **When** l'utilisateur clique "Tout marquer lu", **Then** toutes les pastilles disparaissent instantanément et le badge passe à 0
2. **Given** le marquage global a été effectué, **When** l'utilisateur recharge la page, **Then** l'état "tout lu" est persisté

---

### User Story 3 - Affichage différencié par type de notification (Priority: P2)

Le système affiche 4 types de notifications avec des visuels distincts, permettant à l'utilisateur d'identifier immédiatement la nature de l'événement avant même de lire le texte.

**Why this priority**: La différenciation visuelle réduit le temps de traitement cognitif et guide l'attention vers les alertes prioritaires (ex: modération en attente).

**Independent Test**: Peut être testé en créant une notification de chaque type et en vérifiant que chaque item affiche l'icône et la couleur correctes selon le design.

**Acceptance Scenarios**:

1. **Given** une notification de type "contribution validée", **When** elle s'affiche, **Then** l'icône est une coche (✓), fond vert success
2. **Given** une notification de type "activité suivie" (livre/collection), **When** elle s'affiche, **Then** l'icône affiche les initiales de la collection sur fond dégradé cuir/mousse
3. **Given** une notification de type "modération en attente", **When** elle s'affiche, **Then** l'icône est un triangle d'alerte, fond warning ; visible uniquement pour les profils modérateur
4. **Given** une notification de type "progression de rang", **When** elle s'affiche, **Then** l'icône est une étoile, fond success

---

### User Story 4 - Page historique complète (Priority: P3)

L'utilisateur clique sur "Voir toutes les notifications" en pied du panneau et accède à une page dédiée listant l'intégralité de ses notifications passées, avec pagination.

**Why this priority**: Complète le système pour les utilisateurs actifs ayant besoin de retrouver une ancienne notification hors panneau.

**Independent Test**: Peut être testé en naviguant vers la route `/notifications` et en vérifiant que toutes les notifications (lues et non lues) de l'utilisateur sont affichées avec pagination.

**Acceptance Scenarios**:

1. **Given** l'utilisateur a 50 notifications, **When** il accède à la page historique, **Then** les notifications sont paginées et toutes accessibles
2. **Given** la page historique, **When** l'utilisateur clique sur une notification, **Then** elle est marquée lue et l'utilisateur redirigé vers la cible

---

### User Story 5 - Gestion des préférences de notification (Priority: P3)

L'utilisateur clique sur "PRÉFÉRENCES" en pied du panneau et est redirigé vers la section paramètres de son compte où il peut activer ou désactiver chaque type de notification.

**Why this priority**: Permet à l'utilisateur de ne recevoir que les alertes pertinentes, améliorant la qualité perçue du système.

**Independent Test**: Peut être testé en désactivant un type (ex: progression de rang), en déclenchant cet événement, et en vérifiant qu'aucune notification n'est créée pour l'utilisateur concerné.

**Acceptance Scenarios**:

1. **Given** l'utilisateur désactive les notifications de progression, **When** il change de rang, **Then** aucune notification de type "progression" n'est générée
2. **Given** l'utilisateur réactive un type désactivé, **When** l'événement se produit, **Then** les notifications reprennent

---

### Edge Cases

- Que se passe-t-il si le panneau est ouvert et qu'une nouvelle notification arrive en temps réel ? (Le compteur ne se met pas à jour dynamiquement pour la v1 — le badge reflète l'état au chargement de la page)
- Que se passe-t-il si un utilisateur non-modérateur reçoit une notification de type "modération" suite à un bug ? Elle ne doit pas être affichée.
- Que se passe-t-il si la cible d'une notification n'existe plus (livre supprimé) ? La notification affiche un message d'erreur générique plutôt que de rediriger vers une 404.
- Que se passe-t-il si l'utilisateur n'a aucune notification ? Le panneau affiche un état vide avec une icône et un message "Aucune notification pour le moment".
- Que se passe-t-il si la liste dépasse 50 notifications dans le panneau ? Seules les 20 plus récentes sont affichées ; le lien "Voir toutes" permet d'accéder au reste.
- Que se passe-t-il si la requête de chargement du panneau échoue ? Skeleton loader affiché pendant le fetch ; en cas d'erreur : toast d'erreur + état vide dans le panneau.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: La navbar DOIT afficher un badge numérique rouge sur l'icône de cloche indiquant le nombre exact de notifications non lues pour l'utilisateur connecté
- **FR-002**: Le badge DOIT disparaître lorsque le compteur de non-lues atteint zéro
- **FR-003**: Un clic sur la cloche DOIT ouvrir/fermer le panneau notifications ; un clic en dehors DOIT le fermer
- **FR-004**: Le panneau DOIT afficher les notifications regroupées en deux sections chronologiques : "Nouvelles · aujourd'hui" et "Plus anciennes" — la frontière "aujourd'hui" est calculée selon le **fuseau horaire du profil utilisateur**
- **FR-005**: Chaque notification DOIT afficher : une icône typée (ou initiales de collection), le message, l'horodatage relatif, et une pastille marron/brand si non lue
- **FR-006**: Le système DOIT gérer 4 types de notifications avec visuels distincts : `contribution_validated` (success), `book_activity` (info illustré), `moderation_pending` (warning), `rank_up` (success étoile) — `comment_activity` est hors périmètre v1
- **FR-007**: Les notifications de type `moderation_pending` DOIVENT être visibles uniquement pour les utilisateurs ayant le rôle modérateur
- **FR-008**: Un clic sur une notification DOIT marquer celle-ci comme lue, rediriger vers la cible, et fermer le panneau
- **FR-009**: Le bouton "Tout marquer lu" DOIT marquer toutes les notifications de l'utilisateur comme lues et remettre le badge à zéro instantanément
- **FR-010**: Le pied de panneau DOIT contenir un lien "Voir toutes les notifications" vers la page historique et un bouton "PRÉFÉRENCES" vers les paramètres du compte
- **FR-011**: La page historique (`/notifications`) DOIT lister toutes les notifications de l'utilisateur en ordre chronologique inversé (plus récentes en premier), avec pagination, sans filtre par type
- **FR-012**: Les paramètres de compte DOIVENT permettre d'activer/désactiver chaque type de notification indépendamment
- **FR-013**: Une notification désactivée par préférence NE DOIT PAS être créée lors de l'événement déclencheur
- **FR-014**: Le panneau DOIT afficher un état vide avec message informatif si aucune notification n'existe
- **FR-015**: Le panneau DOIT limiter l'affichage aux 20 notifications les plus récentes ; les suivantes sont accessibles via la page historique
- **FR-016**: Le design défini dans `design/pages/profil.html` (classes CSS `.notif-*`, `.menu-card`, `.menu-head`, `.menu-foot`) DOIT être reproduit fidèlement
- **FR-017**: Pendant le chargement du contenu du panneau, un **skeleton loader** (rangées fantômes) DOIT être affiché à la place de la liste
- **FR-018**: En cas d'échec du chargement, un **toast d'erreur** DOIT s'afficher et le panneau DOIT présenter l'état vide avec message informatif
- **FR-019**: Lorsqu'un utilisateur désactive un type de notification via ses préférences, toutes ses notifications **non lues** de ce type DOIVENT être **supprimées immédiatement** en base

### Key Entities

- **Notification**: Alerte destinée à un utilisateur spécifique ; attributs : destinataire, type, message formaté, URL cible, état lu/non-lu, date de création, `sourceId` (clé d'idempotence, ex: `contribution_validated:42`) ; contrainte d'unicité DB sur `(user_id, source_id)` ; le handler Messenger ignore silencieusement les doublons (UniqueConstraintViolation swallowed)
- **NotificationType**: Enumération des 4 types gérés (`contribution_validated`, `book_activity`, `moderation_pending`, `rank_up`) — `comment_activity` hors périmètre v1
- **NotificationPreference**: Entité Doctrine séparée liée à `User` en relation OneToOne ; stocke un booléen par type de notification (`contribution_validated`, `book_activity`, `moderation_pending`, `rank_up`) ; créée avec toutes les préférences à `true` par défaut lors de la création de l'utilisateur
- **UserCollectionSubscription**: Entité de liaison `User ↔ Collection` (junction table) ; contrainte d'unicité `(user_id, collection_id)` ; créée/supprimée via les actions abonnement/désabonnement depuis la page collection. Prerequisite pour les notifications `book_activity`.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: L'utilisateur voit son badge de cloche mis à jour à chaque chargement de page en moins de 200 ms de rendu perçu
- **SC-002**: Le marquage individuel d'une notification (clic) est reflété sans rechargement de page
- **SC-003**: L'action "Tout marquer lu" est complète (visuellement) en moins de 300 ms
- **SC-004**: 100% des événements système déclencheurs génèrent la notification correspondante si la préférence est activée
- **SC-005**: Aucune notification de type `moderation_pending` n'est visible pour un utilisateur sans rôle modérateur
- **SC-006**: La page historique affiche l'intégralité des notifications avec pagination fonctionnelle
- **SC-007**: La désactivation d'un type de notification en préférences empêche toute création ultérieure de ce type pour l'utilisateur

---

## Assumptions

- Le système de rôles existant (RBAC, feature 004) est utilisé pour filtrer les notifications de modération
- Le système de suggestions/contributions existant (entity `Suggestion`, `Contribution`) fournit les événements pour `contribution_validated`
- Le système de suivi de collections/livres existant fournit les événements pour `book_activity` : une notification est envoyée aux utilisateurs qui suivent une collection lorsqu'un **nouveau livre est ajouté** à celle-ci ; le dispatcher envoie **un `NotificationMessage` par abonné** (fan-out immédiat) — Messenger traite en parallèle. **Deux templates de message** existent selon la source (référence design `profil.html`) : ajout singulier (`{auteur} a publié une nouvelle fiche dans une collection que tu suis ({collection}).`) et ajout en lot (`La collection {collection} a été enrichie de {N} nouvelles fiches.`)
- Le système de gamification (entity `ContributorLevel`) fournit les événements pour `rank_up` : la notification est déclenchée **uniquement lors du passage à un nouveau palier nommé** (ex: Bronze → Argent), pas à chaque gain de points
- Pour la v1, le badge est mis à jour au chargement de page (pas de WebSocket/push temps réel) ; le compteur `unread_count` est injecté via TwigExtension ou EventSubscriber comme variable globale Twig — zéro round-trip AJAX
- **Le panneau notifications est un Live Component Symfony UX** : re-rendu serveur à chaque ouverture (pas de cache client entre ouvertures successives dans la même session) ; garantit cohérence après marquage lu ou nouvelles notifications ; les actions "marquer lu" et "tout marquer lu" sont des LiveAction sur ce composant
- La page "Préférences" est une section de la page profil/paramètres existante, pas une nouvelle page standalone
- Les notifications sont persistées en base de données (pas de système de file externe)
- Un utilisateur peut accumuler un maximum de 500 notifications en base ; les plus anciennes au-delà de ce seuil sont **supprimées inline dans le handler Messenger** : après chaque insertion, si `COUNT(notifications WHERE user = X) > 500`, supprimer les plus anciennes (ORDER BY created_at ASC LIMIT N)
- La cible d'une notification peut être résolue en URL côté serveur au moment de la création (pas de résolution dynamique client-side)
- **La création de notifications passe par Symfony Messenger (messages asynchrones)** : chaque domaine dispatche un message dédié (ex: `NotificationMessage`) ; le handler crée la notification en base de façon découplée de l'opération principale
- **Points de dispatch** : aucun domain event n'existe actuellement dans le code — ils seront **définis dans cette feature**. Points d'injection identifiés : `ModerationService::approve(WorkEntry)` → `ContributionValidatedEvent` (destinataire : `WorkEntry::author`) + `BookAddedToCollectionEvent` (destinataires : abonnés de la collection) ; `SuggestionController` (POST create entity) → `ModerationPendingEvent` (destinataires : modérateurs) ; point de dispatch `RankUpEvent` à définir lors du passage de palier (après changement du compteur de contributions validées). Des **Event Listeners dédiés** écouteront ces events et dispatcheront le `NotificationMessage` via Messenger — zéro modification du code métier existant
- **Isolation cross-user** : `NotificationRepository` filtre systématiquement par utilisateur authentifié (`user = $security->getUser()`) — aucune query par ID seul n'est exposée ; pas de Voter nécessaire

---

## Clarifications

### Session 2026-06-02

- Q: Comment le badge de cloche (compteur non-lues) est-il injecté dans la navbar ? → A: TwigExtension ou EventSubscriber injecte une variable globale `unread_count` dans chaque réponse Twig (rendu serveur, zéro round-trip AJAX)
- Q: Quel mécanisme technique pour fetcher/afficher le contenu du panneau notifications ? → A: Live Component Symfony UX
- Q: Fan-out strategy pour `book_activity` (N abonnés) ? → A: Dispatcher envoie un `NotificationMessage` par destinataire (fan-out immédiat) — Messenger traite en parallèle
- Q: Isolation cross-user — comment garantir qu'un utilisateur ne lit/modifie que ses propres notifications ? → A: `NotificationRepository` filtre systématiquement par utilisateur authentifié — aucune query par ID seul exposée
- Q: Points de dispatch dans le code existant — où dispatchter `NotificationMessage` ? → A: Symfony EventDispatcher — les services existants émettent des domain events (`ContributionValidatedEvent`, etc.) et des listeners dédiés dispatchent le `NotificationMessage` (zéro modification des services existants)

- Q: Comment les notifications doivent-elles être créées lors d'un événement déclencheur ? → A: Symfony Messenger async messages — chaque domaine dispatche un `NotificationMessage`, le handler crée la notification en base de façon découplée
- Q: Comment stocker les préférences de notification ? → A: Entité Doctrine séparée `NotificationPreference` en OneToOne avec `User`, un booléen par type, toutes à `true` par défaut à la création du compte
- Q: Déduplication des notifications — duplicats possibles si même événement se répète ? → A: Champ `sourceId` avec contrainte unique `(user_id, source_id)` en DB ; handler Messenger idempotent (ignore UniqueConstraintViolation)
- Q: Re-fetch du panneau entre ouvertures successives dans la même session ? → A: Re-fetch serveur à chaque ouverture (pas de cache client)
- Q: Mécanisme de nettoyage des notifications au-delà de 500 ? → A: Suppression inline dans le handler Messenger après insertion — DELETE oldest si COUNT > 500
- Q: Quels événements déclenchent une notification `book_activity` et qui la reçoit ? → A: Uniquement l'ajout d'un nouveau livre à une collection suivie — les abonnés de cette collection reçoivent la notification
- Q: État de chargement et gestion d'erreur du panneau ? → A: Skeleton loader pendant le fetch ; toast d'erreur + état vide fallback en cas d'échec
- Q: Granularité du déclencheur `rank_up` ? → A: Uniquement au passage d'un nouveau palier nommé (ex: Bronze → Argent), pas à chaque gain de points
- Q: Page historique — ordre et filtres ? → A: Liste chronologique inversée (plus récentes en premier), pagination, sans filtre par type

### Session 2026-06-02 - Checklist Review

- Q: Quel fuseau horaire détermine la frontière "aujourd'hui" dans le groupement du panneau ? → A: Fuseau horaire du profil utilisateur
- Q: Que se passe-t-il avec les notifications non lues d'un type désactivé par l'utilisateur ? → A: Elles sont **supprimées** immédiatement (→ FR-019)
- Q: Le type `comment_activity` est-il dans le périmètre v1 ? → A: **Hors périmètre** — retiré de la spec
- Q: Les domain events (`ContributionValidatedEvent`, etc.) existent-ils dans le code ? → A: Non — aucun domain event existant ; ils seront définis dans cette feature ; points de dispatch identifiés (voir Assumptions)
- Q: Le design montre deux templates de message `book_activity` (singulier vs. lot) — lequel est correct ? → A: Le **design est la source de vérité** — les deux templates sont requis
