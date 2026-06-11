# Feature Specification: Système de Suivi — Créateurs & Collections

**Feature Branch**: `025-follow-collections-contributors`

**Created**: 2026-06-10

**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Suivre un Créateur depuis la page Créateurs (Priority: P1)

Un utilisateur connecté consulte la page Créateurs et clique sur le bouton "♡ SUIVRE" d'une carte Créateur. L'icône bascule immédiatement vers "♥ SUIVI" (état rempli) sans rechargement. S'il reclique, l'abonnement est annulé et l'icône revient à l'état vide.

**Why this priority**: C'est la porte d'entrée principale du système de suivi. Sans cette relation, aucune notification ne peut être générée.

**Independent Test**: Peut être testé en isolation — un utilisateur suit un créateur, on vérifie que la relation est enregistrée et que le bouton reflète le bon état.

**Acceptance Scenarios**:

1. **Given** un utilisateur connecté sur la page Créateurs, **When** il clique sur "♡ SUIVRE" d'une carte, **Then** le bouton bascule immédiatement en "♥ SUIVI" sans rechargement de page et la relation est persistée.
2. **Given** un utilisateur connecté ayant déjà suivi un créateur, **When** il clique sur "♥ SUIVI", **Then** le bouton revient à "♡ SUIVRE" et la relation est supprimée.
3. **Given** un visiteur non connecté, **When** il clique sur "♡ SUIVRE", **Then** une modale de connexion s'affiche avec le message "Connectez-vous pour suivre ce créateur et recevoir des alertes." et un bouton CTA "Se connecter".
4. **Given** un utilisateur connecté clique sur "♡ SUIVRE" et le serveur retourne une erreur, **When** la réponse HTTP échoue, **Then** le bouton revient à "♡ SUIVRE" et un toast affiche "Une erreur est survenue. Votre action n'a pas été enregistrée." pendant 4 secondes (cf. FR-003).

---

### User Story 2 — Suivre une Collection depuis sa page détaillée (Priority: P1)

Un utilisateur connecté consulte la page d'une Collection et clique sur "♡ SUIVRE". Le bouton bascule immédiatement vers "♥ SUIVI" et la relation est enregistrée. Le même bouton permet de se désabonner.

**Why this priority**: Symétrique au suivi Créateur — les deux relations alimentent le même moteur de notifications.

**Independent Test**: Un utilisateur suit une collection, on vérifie la persistance et l'état du bouton sans dépendance au suivi Créateur.

**Acceptance Scenarios**:

1. **Given** un utilisateur connecté sur la page d'une Collection, **When** il clique sur "♡ SUIVRE", **Then** le bouton bascule en "♥ SUIVI" instantanément et la relation est persistée.
2. **Given** un utilisateur connecté ayant déjà suivi une collection, **When** il reclique sur "♥ SUIVI", **Then** le bouton revient à "♡ SUIVRE" et la relation est supprimée.
3. **Given** un visiteur non connecté, **When** il clique sur "♡ SUIVRE", **Then** une modale de connexion s'affiche avec le message "Connectez-vous pour suivre cette collection et recevoir des alertes." et un bouton CTA "Se connecter".
4. **Given** un utilisateur connecté clique sur "♡ SUIVRE" et le serveur retourne une erreur, **When** la réponse HTTP échoue, **Then** le bouton revient à "♡ SUIVRE" et un toast affiche "Une erreur est survenue. Votre action n'a pas été enregistrée." pendant 4 secondes (cf. FR-003).

---

### User Story 3 — Recevoir une notification lors d'une nouvelle publication (Priority: P2)

Lorsqu'un modérateur publie une nouvelle fiche Livre (statut → PUBLIÉ), tous les utilisateurs qui suivent au moins un Créateur ou la Collection associés à ce livre reçoivent une notification in-app. Chaque utilisateur ne reçoit qu'une seule notification même s'il suit plusieurs entités liées au même livre.

**Why this priority**: C'est la valeur centrale de la fonctionnalité. Les relations sans notifications n'ont pas de valeur pour l'utilisateur.

**Independent Test**: Publier un livre lié à un créateur suivi et une collection suivie par le même utilisateur — vérifier qu'une seule notification est générée.

**Acceptance Scenarios**:

1. **Given** un livre est publié avec un Auteur suivi par Alice, **When** le statut du livre passe à PUBLIÉ, **Then** Alice reçoit une notification "Le créateur [Nom] que tu suis a été ajouté à une nouvelle fiche." avec le titre du livre et un lien vers sa page.
2. **Given** un livre est publié dans une Collection suivie par Bob, **When** le statut passe à PUBLIÉ, **Then** Bob reçoit une notification "Une nouvelle fiche vient d'enrichir la collection [Nom] que tu suis." avec lien vers le livre.
3. **Given** Carol suit à la fois l'Auteur et la Collection d'un nouveau livre, **When** le livre est publié, **Then** Carol reçoit exactement une (1) notification, pas deux.
4. **Given** un livre est publié sans Créateur suivi ni Collection suivie, **When** le statut passe à PUBLIÉ, **Then** aucune notification n'est générée.

---

### User Story 4 — Filtrer la liste par entités suivies (Priority: P2)

Un utilisateur connecté peut activer le toggle "Uniquement ceux que je suis" dans la barre latérale de la liste Créateurs ou Collections pour filtrer l'affichage aux seules entités qu'il suit. Le toggle est absent pour les visiteurs non connectés.

**Why this priority**: Complément direct du suivi — permet à l'utilisateur de retrouver rapidement les entités qu'il suit.

**Independent Test**: Peut être testé indépendamment des notifications — un utilisateur avec des suivis actifs active le toggle et vérifie que seules les entités suivies apparaissent.

**Acceptance Scenarios**:

1. **Given** un utilisateur connecté sur la page Créateurs avec au moins un créateur suivi, **When** il active le toggle "Uniquement ceux que je suis", **Then** seuls les créateurs suivis sont affichés et l'URL contient `?onlyFollowed=1`.
2. **Given** un utilisateur connecté sur la page Créateurs sans aucun créateur suivi, **When** il active le toggle, **Then** un état vide est affiché avec le message "Vous ne suivez encore personne. Découvrez les créateurs !" et un lien vers la liste complète (toggle désactivé).
3. **Given** un utilisateur connecté sur la page Collections avec au moins une collection suivie, **When** il active le toggle, **Then** seules les collections suivies sont affichées et l'URL contient `?followed=true`.
4. **Given** un utilisateur connecté sur la page Collections sans aucune collection suivie, **When** il active le toggle, **Then** un état vide est affiché avec le message "Vous ne suivez encore aucune collection. Découvrez les collections !" et un lien vers la liste complète (toggle désactivé).
5. **Given** un visiteur non connecté sur la page Créateurs ou Collections, **When** la page se charge, **Then** le toggle "Uniquement ceux que je suis" n'est pas rendu dans l'interface.

---

### Edge Cases

- Que se passe-t-il si un utilisateur suit un créateur puis que ce créateur est supprimé ? La suppression du Créateur déclenche une suppression en cascade (ON DELETE CASCADE) de toutes ses entrées `UserFollowedContributors` — aucune relation orpheline, aucune erreur.
- Que se passe-t-il si un utilisateur suit une collection puis que cette collection est supprimée ? La suppression de la Collection déclenche une suppression en cascade (ON DELETE CASCADE) de toutes ses entrées `UserFollowedCollections` — aucune relation orpheline, aucune erreur.
- Que se passe-t-il si le même livre est republié (repasse de PUBLIÉ à BROUILLON puis PUBLIÉ) ? Un champ `followNotificationSentAt` (datetime nullable) sur l'entité `Book` est vérifié avant tout dispatch : si non-null, le job ne génère aucune notification. Ce champ est positionné à la date courante lors du premier dispatch réussi — aucun mécanisme de réinitialisation en scope.
- Un utilisateur sans compte tente de suivre — redirection vers authentification avec retour prévu vers la page d'origine après connexion.
- Un livre publié n'a ni Collection ni Créateur suivi par personne — aucune notification, aucune erreur.
- Utilisateur connecté active le toggle "Uniquement ceux que je suis" sur la page Créateurs sans aucun créateur suivi — afficher un état vide avec CTA "Vous ne suivez encore personne. Découvrez les créateurs !" et un lien vers la liste complète (sans toggle).
- Utilisateur connecté active le toggle "Uniquement ceux que je suis" sur la page Collections sans aucune collection suivie — afficher un état vide avec CTA "Vous ne suivez encore aucune collection. Découvrez les collections !" et un lien vers la liste complète (sans toggle).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Le système DOIT permettre à un utilisateur connecté de s'abonner (follow) et de se désabonner (unfollow) d'un Créateur.
- **FR-002**: Le système DOIT permettre à un utilisateur connecté de s'abonner et de se désabonner d'une Collection.
- **FR-003**: Le changement d'état du bouton "Suivre/Suivi" DOIT être reflété visuellement en moins de 100ms après le clic (Optimistic Update, cf. SC-001), sans attendre la confirmation du serveur. Le bouton DOIT être désactivé (non-cliquable) pendant la durée de la requête HTTP pour prévenir les doubles soumissions. En cas d'erreur serveur, le bouton DOIT revenir à son état précédent et un toast d'erreur DOIT afficher "Une erreur est survenue. Votre action n'a pas été enregistrée." (durée d'affichage : 4 secondes).
- **FR-004**: Le système DOIT bloquer l'action de suivi pour les visiteurs non connectés et leur présenter une invitation à se connecter.
- **FR-005**: Le système DOIT enregistrer la relation de suivi entre un Utilisateur et un Créateur (UserFollowedContributors).
- **FR-006**: Le système DOIT enregistrer la relation de suivi entre un Utilisateur et une Collection (UserFollowedCollections).
- **FR-007**: Lors du passage d'une fiche Livre au statut PUBLIÉ, le système DOIT émettre un événement asynchrone (via job/queue) pour identifier tous les utilisateurs abonnés à au moins une entité (Créateur ou Collection) liée à ce livre, sans bloquer la transaction de publication.
- **FR-008**: Le système DOIT dédoublonner les destinataires : un utilisateur ne reçoit qu'une seule notification par événement de publication, quel que soit le nombre d'entités suivies liées au livre.
- **FR-009**: Le job asynchrone DOIT générer une notification in-app pour chaque utilisateur ciblé, en utilisant le système de notifications existant (cf. feature 017). Les templates de message sont définis en §FR-010.
- **FR-010**: La notification DOIT contenir : le type "Nouveauté / Suivi", un message dynamique selon le scénario (Créateur ou Collection), le titre du livre publié en sous-texte, et une URL de redirection vers la page du livre. En cas de suivi simultané du Créateur ET de la Collection d'un même livre, le template "Créateur" est prioritaire. En cas de suivi simultané de plusieurs Créateurs du même livre (ex. Auteur et Illustrateur tous deux suivis), la priorité suit l'ordre de rôle : Auteur > Illustrateur > Traducteur — seul le premier Créateur éligible selon cet ordre est utilisé dans le message.
- **FR-011**: Le clic sur une notification dans le menu déroulant DOIT rediriger l'utilisateur vers la page détaillée du livre publié.
- **FR-012**: Le système DOIT implémenter un toggle "Uniquement ceux que je suis" dans la barre latérale des pages liste Créateurs et Collections, filtrant les résultats aux seules entités suivies par l'utilisateur connecté. Le toggle DOIT être masqué (non rendu) pour les visiteurs non connectés. L'état actif du toggle DOIT être persisté via le paramètre URL pour maintenir le filtre au rechargement de page (`?onlyFollowed=1` pour la page Créateurs — aligné sur `ContributorFilterState` existante ; `?followed=true` pour la page Collections — nouveau `CollectionListFilterState`). Si l'utilisateur n'a aucun suivi actif lors de l'activation du toggle, afficher l'état vide correspondant (cf. §Edge Cases et §US4).
- **FR-013**: Le job de notification DOIT vérifier `followNotificationSentAt` sur `Book` avant tout dispatch ; si non-null, aucune notification n'est émise. Le job DOIT positionner `followNotificationSentAt` à la date courante **avant** de créer les notifications individuelles (verrou optimiste). Conséquence acceptée : une panne entre le positionnement du flag et la création des notifications peut entraîner des notifications manquées pour certains utilisateurs — ce cas est préféré aux doublons en cas de retry. Le flag `followNotificationSentAt` est permanent — aucun mécanisme de réinitialisation n'est en scope ; une republication ne génère jamais de nouvelle notification.

### Key Entities

- **UserFollowedContributors**: Relation entre un Utilisateur et un Créateur qu'il suit. Attributs : identifiant utilisateur, identifiant créateur, date d'abonnement (UTC). Contrainte : UNIQUE(user_id, contributor_id). Suppression en cascade (ON DELETE CASCADE) à la suppression du Créateur.
- **UserFollowedCollections**: Relation entre un Utilisateur et une Collection qu'il suit. Attributs : identifiant utilisateur, identifiant collection, date d'abonnement (UTC). Contrainte : UNIQUE(user_id, collection_id). Suppression en cascade (ON DELETE CASCADE) à la suppression de la Collection. *(Entité existante en base sous le nom `UserCollectionSubscription` — aucune nouvelle entité à créer pour FR-006.)*
- **Notification**: Entité existante — enrichie avec un nouveau type "Nouveauté / Suivi". Payload : message, sous-texte (titre du livre), URL cible. Périmètre : in-app uniquement — notifications email et push hors scope de cette version.
- **Livre (Book)**: Entité existante — le changement de statut vers PUBLIÉ déclenche l'événement. Enrichie avec `followNotificationSentAt` (datetime nullable) pour prévenir les doublons lors d'une republication.
- **Créateur (Contributor)**: Entité existante — Auteur, Illustrateur, Traducteur associés à un Livre.
- **Collection**: Entité existante — regroupement de Livres.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Le retour visuel du bouton "Suivre/Suivi" apparaît en moins de 100ms après le clic (Optimistic Update côté client).
- **SC-002**: 100% des utilisateurs abonnés à au moins une entité liée à un livre nouvellement publié reçoivent une notification, sous réserve du bon fonctionnement du système Messenger (hors panne de job — cf. FR-013 tradeoff accepté).
- **SC-003**: 0% des utilisateurs reçoivent plus d'une notification pour la même publication (pas de doublon).
- **SC-004**: Un visiteur non connecté voit la modale de connexion dans 100% des cas lorsqu'il tente de suivre.
- **SC-005**: Les relations de suivi sont disponibles pour alimenter les filtres de liste dans l'interface (toggle "Uniquement ceux que je suis").

## Assumptions

- Le système de notifications in-app existant (développé dans le ticket 017) est opérationnel et peut être étendu avec de nouveaux types de notifications.
- Un Livre peut être lié à plusieurs Créateurs (Auteur, Illustrateur, Traducteur) et à au plus une Collection.
- Le statut PUBLIÉ est le seul déclencheur d'événement — les mises à jour de fiches déjà publiées ne génèrent pas de nouvelle notification.
- Les notifications sont générées de façon **asynchrone** via Symfony Messenger déclenché par l'événement de publication ; la transaction de changement de statut n'attend pas leur envoi. En cas d'échec, le message est retenté 3 fois puis placé en Dead Letter Queue (visible via `messenger:failed`).
- Aucune re-notification lors d'une republication (PUBLIÉ → autre statut → PUBLIÉ) : le job vérifie `followNotificationSentAt` sur `Book` avant dispatch — si déjà positionné, aucune notification n'est émise.
- L'interface du bouton "Suivre" est déjà présente visuellement dans les maquettes — seule la logique fonctionnelle est à implémenter.
- Les actions follow/unfollow sont des routes Symfony (pas une API REST découplée) — l'utilisateur courant est résolu via le security context Symfony (`$this->getUser()`).
- Le filtre "Uniquement ceux que je suis" dans la barre latérale est **in-scope** : le toggle et le filtrage côté API et UI font partie de ce ticket (voir FR-012).
- Les Créateurs suivis et les Collections suivies sont des listes séparées (pas d'entité générique "favori").
- Le job de notification charge tous les destinataires en une seule requête DB (pas de batching). Acceptable pour le volume actuel — à réévaluer si le nombre d'abonnés par Créateur dépasse 1 000.
- La latence de livraison des notifications dépend du temps de traitement de la queue Messenger — aucun SLA de livraison n'est défini pour cette version.
- L'intervalle de retry entre les 3 tentatives (délai et backoff) est configurable dans la configuration du transport Symfony Messenger — la valeur exacte est définie en plan.md.
- Le monitoring des échecs de job se fait via `messenger:failed` (commande Symfony standard) — aucune alerte automatique n'est en scope pour cette version.
- Les actions follow/unfollow sont idempotentes côté application : si la relation existe déjà, l'appel retourne succès silencieusement (la contrainte UNIQUE DB sert de filet de sécurité).

## Clarifications

### Session 2026-06-10

- Q: FR-012 (filtre "Uniquement ceux que je suis") est-il in-scope ou deferred ? → A: In-scope — implémenter le toggle et le filtrage côté API et UI dans ce ticket.
- Q: Comportement de l'optimistic update (FR-003) en cas d'erreur serveur ? → A: Rollback visuel + toast d'erreur.
- Q: Terme canonique pour l'action de suivi des Collections — "Ajouter aux favoris" ou "Suivre/Suivi" ? → A: "Suivre / Suivi" unifié pour Créateurs ET Collections.
- Q: Déclenchement des notifications lors de la publication — synchrone ou asynchrone ? → A: Asynchrone via job/queue, sans bloquer la transaction de publication.
- Q: Suppression d'un Créateur avec abonnés actifs — quel mécanisme de nettoyage ? → A: ON DELETE CASCADE sur `UserFollowedContributors`.
- Q: Mécanisme d'autorisation pour les actions follow/unfollow — validation explicite ou sécurité framework ? → A: Symfony uniquement — l'utilisateur connecté est récupéré via `$this->getUser()` (security context), aucun user_id en paramètre de requête.
- Q: État vide du toggle "Uniquement ceux que je suis" (FR-012) quand l'utilisateur n'a aucun suivi actif ? → A: Message vide + CTA : "Vous ne suivez encore personne. Découvrez les créateurs !" avec lien vers la liste complète.
- Q: Contrainte d'unicité DB sur les tables de suivi ? → A: UNIQUE(user_id, contributor_id) sur `UserFollowedContributors` et UNIQUE(user_id, collection_id) sur `UserFollowedCollections` — enforced en DB + vérification applicative avant insert.
- Q: Politique de retry/failure du job Messenger de notification ? → A: Retry automatique (3 tentatives) via Symfony Messenger, puis Dead Letter Queue — échecs visibles via `messenger:failed`.
- Q: Mécanisme de prévention des doublons de notification lors d'une republication ? → A: Champ `followNotificationSentAt` (datetime nullable) ajouté sur `Book` — le job vérifie ce champ avant dispatch et le positionne après envoi réussi (FR-013).
- Q: Template de notification utilisé quand un utilisateur suit à la fois le Créateur ET la Collection d'un même livre publié (cas Carol, dedup FR-008) ? → A: Template "Créateur" prioritaire sur template "Collection".
- Q: Visibilité du toggle "Uniquement ceux que je suis" (FR-012) pour les visiteurs non connectés ? → A: Toggle masqué entièrement pour les guests (non rendu en Twig).
- Q: Timing du positionnement de `followNotificationSentAt` (FR-013) — avant ou après création des notifications individuelles ? → A: Avant (verrou optimiste) — bloque les retries en cas de panne mais évite les doublons de notification.
- Q: Stratégie de dispatch pour gros volumes d'abonnés (batching vs requête unique) ? → A: Requête unique — pas de batching pour cette version, à réévaluer au-delà de 1 000 abonnés par Créateur.
- Q: Paramètre URL du toggle "Uniquement ceux que je suis" — `?followed=true` unifié ou paramètres distincts par page ? → A: Deux paramètres distincts : `?onlyFollowed=1` pour Créateurs (aligne sur `ContributorFilterState` déjà présente en base de code) ; `?followed=true` pour Collections (nouveau `CollectionListFilterState`). FR-012 a été mis à jour en conséquence.
- Q: Entité DB pour le suivi des Collections (FR-006) — nouvelle entité ou existante ? → A: `UserCollectionSubscription` est l'entité existante (feature antérieure) — elle sert de "UserFollowedCollections" dans la spec. Aucune nouvelle entité à créer ; utiliser le `UserCollectionSubscriptionRepository` existant et y ajouter les méthodes manquantes (`findFollowedCollectionIds`, `findRecipientsForBook`).
