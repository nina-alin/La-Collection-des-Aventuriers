# Feature Specification: Gestion de la Bibliothèque Personnelle (Listes Livre)

**Feature Branch**: `016-personal-library-lists`

**Created**: 2026-06-01

**Status**: Draft

**Input**: Rendre fonctionnels les boutons de classement (Ma Collection, À lire, À acheter, Favoris) sur les fiches livre. Implémentation de la logique métier de toggle, des règles d'auto-cohérence et du retour visuel instantané.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Marquer un livre "Dans ma collection" (Priority: P1)

Un utilisateur connecté consulte la fiche d'un livre qu'il possède. Il clique sur "Ma Collection" pour l'ajouter à sa liste de livres possédés. Le bouton s'active visuellement instantanément et un toast de confirmation s'affiche. Un second clic sur ce même bouton retire le livre de la collection.

**Why this priority**: C'est le statut principal autour duquel gravitent les règles métier (auto-retrait "À acheter"). C'est aussi le statut le plus attendu par les utilisateurs.

**Independent Test**: Sur `/livres/{slug}` en tant qu'utilisateur connecté, cliquer "Ma Collection" → bouton actif, toast visible. Cliquer à nouveau → bouton inactif, toast "retiré". Tester également sur un livre absent de la base UserBook et sur un livre déjà présent.

**Acceptance Scenarios**:

1. **Given** un utilisateur connecté sur une fiche livre non présente dans sa collection, **When** il clique "Ma Collection", **Then** le bouton passe à l'état actif visuellement sans rechargement de page, et un toast s'affiche "Ajouté à votre collection".
2. **Given** un utilisateur connecté sur une fiche livre déjà dans sa collection (bouton actif), **When** il clique "Ma Collection", **Then** le bouton repasse à l'état inactif et un toast s'affiche "Retiré de votre collection".
3. **Given** un utilisateur connecté sur une fiche livre qui se trouve dans sa liste "À acheter", **When** il clique "Ma Collection" pour l'ajouter, **Then** le livre est simultanément retiré de la liste "À acheter" — le bouton "À acheter" passe à l'état inactif dans le même rendu.
4. **Given** un utilisateur non connecté, **When** il consulte une fiche livre, **Then** les 4 boutons d'action ne sont pas affichés (comportement existant inchangé).

---

### User Story 2 - Marquer un livre "À lire" (Priority: P2)

Un utilisateur connecté marque un livre comme "À lire" quel que soit son statut de possession actuel (il peut déjà le posséder ou prévoir de l'acheter). Le toggle "À lire" est totalement indépendant des autres statuts.

**Why this priority**: Fonctionnalité de wishlist de lecture, indépendante — ne déclenche aucune règle métier sur les autres boutons.

**Independent Test**: Marquer un livre comme "Dans ma collection" puis cliquer "À lire" → les deux boutons sont actifs simultanément. Cliquer "À lire" à nouveau → seul "Ma Collection" reste actif.

**Acceptance Scenarios**:

1. **Given** un utilisateur connecté sur une fiche livre dans sa collection (bouton "Ma Collection" actif), **When** il clique "À lire", **Then** "À lire" s'active sans modifier l'état de "Ma Collection".
2. **Given** un utilisateur connecté dont le livre est marqué "À acheter", **When** il clique "À lire", **Then** "À lire" s'active sans modifier l'état de "À acheter".
3. **Given** un utilisateur connecté avec "À lire" actif, **When** il clique "À lire", **Then** le statut est retiré et un toast "Retiré de la liste À lire" s'affiche.

---

### User Story 3 - Marquer un livre "À acheter" (Priority: P2)

Un utilisateur connecté ajoute un livre à sa liste d'achats. Ce statut est mutuellement exclusif avec "Dans ma collection" (on ne planifie pas d'acheter ce qu'on possède déjà), mais peut coexister avec "À lire".

**Why this priority**: Wishlist d'acquisition. La règle d'exclusion avec "Dans ma collection" est symétrique à celle déjà testée en US1.

**Independent Test**: Cliquer "À acheter" → actif. Puis cliquer "Ma Collection" → "À acheter" s'éteint automatiquement, "Ma Collection" s'allume.

**Acceptance Scenarios**:

1. **Given** un utilisateur connecté sur une fiche livre sans statut, **When** il clique "À acheter", **Then** le bouton s'active et un toast "Ajouté à la liste À acheter" s'affiche.
2. **Given** un utilisateur connecté dont le livre est marqué "Dans ma collection", **When** il clique "À acheter", **Then** "À acheter" s'active ET "Dans ma collection" s'éteint automatiquement.
3. **Given** un utilisateur connecté avec "À acheter" et "À lire" actifs, **When** aucune action sur "Ma Collection", **Then** les deux restent actifs simultanément (pas d'interférence).

---

### User Story 4 - Marquer un livre comme "Favori" (Priority: P3)

Un utilisateur connecté marque un livre comme coup de cœur. Ce statut est totalement indépendant des autres.

**Why this priority**: Dimension affective/éditoriale. Indépendante, aucune règle métier complexe.

**Independent Test**: Quel que soit l'état des autres boutons, cliquer "Favori" active/désactive uniquement ce bouton.

**Acceptance Scenarios**:

1. **Given** un utilisateur connecté sur n'importe quelle fiche livre, **When** il clique "Favori", **Then** le bouton s'active et un toast "Ajouté à vos favoris" s'affiche.
2. **Given** un utilisateur dont le livre est dans sa collection ET marqué favori, **When** il retire le livre de sa collection, **Then** le bouton "Favori" reste actif.
3. **Given** un utilisateur avec "Favori" actif, **When** il clique "Favori", **Then** le favori est retiré et un toast "Retiré de vos favoris" s'affiche.

---

### User Story 5 - Restitution contextuelle au chargement (Priority: P1)

Au chargement d'une fiche livre, les 4 boutons reflètent fidèlement l'état actuel du livre dans la bibliothèque de l'utilisateur connecté. Un livre déjà marqué doit apparaître "actif" dès le premier rendu, sans aucune interaction requise.

**Why this priority**: Sans cette restitution, l'utilisateur ne peut pas savoir ce qu'il a déjà classé. C'est un prérequis fonctionnel pour le toggle.

**Independent Test**: Ajouter un livre en base via script (ou action précédente) avec status=DANS_MA_COLLECTION et isFavorite=true, puis charger la fiche → "Ma Collection" et "Favori" sont actifs, "À lire" et "À acheter" sont inactifs.

**Acceptance Scenarios**:

1. **Given** un utilisateur connecté dont le livre est en base avec tous les statuts actifs, **When** il charge la fiche, **Then** les 4 boutons correspondants sont dans l'état actif.
2. **Given** un utilisateur connecté dont le livre n'a aucun statut (pas de UserBook en base), **When** il charge la fiche, **Then** les 4 boutons sont dans l'état inactif.
3. **Given** un utilisateur non connecté, **When** il charge la fiche, **Then** aucun bouton de statut n'est visible.

---

### Edge Cases

- Que se passe-t-il si l'action toggle échoue côté serveur (erreur réseau, 500) ? → L'état visuel du bouton est annulé (rollback), un toast d'erreur s'affiche, aucune donnée corrompue.
- Que se passe-t-il si l'utilisateur clique plusieurs fois rapidement sur le même bouton ? → Les clics en double sont protégés (désactivation du bouton pendant la requête), seul le dernier état validé est appliqué.
- Que se passe-t-il si un livre n'existe pas (URL directe) ? → HTTP 404, les actions ne s'appliquent pas.
- Que se passe-t-il si la session expire entre deux clics ? → L'action retourne HTTP 401/302, le toast indique une session expirée et invite à se reconnecter.
- Que se passe-t-il si un utilisateur non authentifié appelle directement l'endpoint de toggle ? → HTTP 401 retourné, aucune modification en base.
- L'activation simultanée "Dans ma collection" + "À lire" : est-ce possible dans le modèle de données actuel ? → Voir Assumptions — l'entité `UserBook` devra être étendue pour supporter cette indépendance.
- Que se passe-t-il si un utilisateur désactive le dernier statut actif d'un livre ? → L'enregistrement `UserBook` est supprimé de la base. Le prochain toggle sur ce livre créera un nouvel enregistrement.

## Requirements *(mandatory)*

### Scope

- Cette feature est limitée à la fiche livre (`/livres/{slug}`). L'extension aux cartes livre dans le catalogue est **hors scope** de ce ticket (partiellement adressée dans la feature 015).

### Functional Requirements

- **FR-001**: Le système DOIT permettre à un utilisateur connecté de toggler les 4 statuts d'un livre : "Dans ma collection", "À lire", "À acheter", "Mes favoris".
- **FR-002**: Chaque toggle DOIT produire un retour visuel immédiat (changement d'état du bouton) sans rechargement de page — via re-rendu du Live Component Symfony UX.
- **FR-003**: Chaque toggle réussi DOIT déclencher un toast non-bloquant avec un message contextualisé (ajout ou retrait, nom de la liste) — via `dispatchBrowserEvent('toast', …)` depuis le composant PHP, capturé par le Stimulus `toast-container_controller`.
- **FR-004**: Le système DOIT appliquer la règle d'auto-cohérence : ajouter "Dans ma collection" retire automatiquement "À acheter" et vice-versa.
- **FR-005**: Le système DOIT garantir que les statuts "À lire" et "Mes favoris" sont indépendants — leur état n'est jamais modifié par l'activation d'un autre statut.
- **FR-006**: Au chargement d'une fiche livre, le système DOIT restituer l'état réel de chaque statut pour l'utilisateur connecté.
- **FR-007**: Tout endpoint de modification de statut DOIT être protégé par authentification — une requête non authentifiée DOIT être rejetée (HTTP 401 ou redirection vers login).
- **FR-008**: Le système DOIT protéger contre les doubles-clics (idempotence ou lock pendant la requête en cours).
- **FR-009**: En cas d'échec de l'action, l'état visuel antérieur DOIT être restauré et un message d'erreur DOIT être affiché.
- **FR-010**: Le modèle de données DOIT supporter la coexistence de "Dans ma collection" (ou "À acheter") avec "À lire" pour un même livre et un même utilisateur. La migration supprimera les enregistrements `LU` et `PAS_DANS_MA_COLLECTION` (clean start défini).
- **FR-011**: Le composant `LibraryActionsComponent` DOIT être navigable au clavier — chaque bouton DOIT être atteignable via Tab, activable via Enter ou Espace, et disposer d'un état de focus visible (outline CSS non supprimé). Le groupe de boutons DOIT exposer `role="group"` avec un `aria-label` descriptif.
- **FR-012**: En cas de requêtes concurrentes sur le même toggle (même utilisateur, même livre, envois simultanés), le comportement attendu est **last-write-wins** : chaque requête lit l'état courant en base avant d'écrire ; l'état final en base est celui de la dernière écriture commitée. Aucun verrou pessimiste n'est requis. La contrainte d'unicité `(user_id, book_id)` garantit l'absence de doublons.

### Key Entities

- **UserBook** : Enregistrement liant un utilisateur à un livre. Porte les statuts `isOwned` (possédé), `isToRead` (à lire), `isToBuy` (à acheter), `isFavorite` (favori). Un seul enregistrement par paire (utilisateur, livre). Absent de la base si l'utilisateur n'a jamais interagi avec le livre, ou si tous ses statuts ont été désactivés (le record est supprimé dès que tous les booléens sont false).
- **User** : Utilisateur authentifié, source de l'action.
- **Book** : Livre cible de l'action.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Le retour visuel suite à un clic sur un bouton de statut est perçu instantanément (moins de 300 ms de réponse serveur médiane — P50 — mesurée depuis l'entrée de la requête HTTP jusqu'à l'envoi de la réponse, dans des conditions normales de charge).
- **SC-002**: L'état des boutons au chargement d'une fiche livre correspond exactement à l'état persisté en base dans 100 % des cas testés.
- **SC-003**: La règle d'auto-cohérence "Ma Collection → suppression À acheter" est appliquée dans 100 % des cas (zéro livre à la fois dans "possédé" ET "à acheter" pour un même utilisateur).
- **SC-004**: Les endpoints de modification sont couverts par des tests fonctionnels vérifiant authentification, idempotence et règles métier.
- **SC-005**: Aucun état incohérent ne peut être créé via des appels directs aux endpoints (protection CSRF + authentification).

## Clarifications

### Session 2026-06-01

- Q: Que faire des enregistrements UserBook avec status `LU` ou `PAS_DANS_MA_COLLECTION` lors de la migration vers les champs booléens ? → A: Supprimer ces enregistrements (clean start, pas de mapping vers les nouveaux champs).
- Q: Quand tous les statuts d'un UserBook sont désactivés (tous false), que faire de l'enregistrement ? → A: Supprimer le record UserBook (absence = aucun statut actif, cohérent avec le modèle "créé à la première interaction").
- Q: Architecture des toggles — endpoint AJAX custom ou autre approche ? → A: Utiliser un Live Component Symfony UX. Les actions de toggle sont des méthodes PHP sur le composant, pas des endpoints REST. Le CSRF et le re-rendu sont gérés par Symfony UX automatiquement.
- Q: Comment les toasts sont-ils déclenchés depuis un Live Component ? → A: `dispatchBrowserEvent('toast', {message, type})` dans chaque méthode d'action PHP ; le Stimulus `toast-container_controller` existant écoute et affiche le toast.
- Q: Portée de rendu du Live Component dans la fiche livre ? → A: Composant limité aux 4 boutons (`actions-grid` uniquement) — prop: `Book $book`. Re-rendu minimal, couplage faible.

## Assumptions

- Les 4 boutons sont déjà présents dans le HTML de la fiche livre (`templates/livre/show.html.twig`) conditionnés par `{% if app.user %}` — seule la logique JS/backend est à implémenter.
- Le composant Toast existe déjà dans le design system du projet ou est suffisamment simple pour être ajouté sans dépendance externe.
- L'entité `UserBook` existante (`src/Entity/UserBook.php`) encode actuellement le statut sous forme d'un enum mutuellement exclusif (`UserBookStatus`) avec les valeurs DANS_MA_COLLECTION, A_ACHETER, A_LIRE, LU, PAS_DANS_MA_COLLECTION. La migration Doctrine remplacera la colonne `status` (enum) par 3 champs booléens (`isOwned`, `isToRead`, `isToBuy`) en plus du champ `isFavorite` déjà existant. Les enregistrements avec `status=LU` ou `status=PAS_DANS_MA_COLLECTION` seront supprimés lors de la migration (clean start, pas de mapping). Les enregistrements `DANS_MA_COLLECTION`, `A_ACHETER`, `A_LIRE` seront migrés vers les booléens correspondants.
- La règle d'exclusion est symétrique : "À acheter" activé supprime aussi "Dans ma collection" (cohérence bidirectionnelle).
- Les toasts sont affichés pendant 3 à 5 secondes puis disparaissent automatiquement (comportement standard non-bloquant).
- La gestion des toggles s'appuie sur un **Live Component Symfony UX** intégré dans la fiche livre. Portée : les 4 boutons uniquement (div `actions-grid`), prop unique `Book $book`. Le re-rendu côté serveur gère l'état visuel des boutons ; aucun endpoint REST custom n'est nécessaire. Les règles d'auto-cohérence (exclusion collection/achat) sont appliquées dans les méthodes d'action du composant. Les toasts sont déclenchés via `dispatchBrowserEvent('toast', {message, type})` depuis le composant PHP — capturés par le Stimulus `toast-container_controller` existant.
- La protection CSRF est assurée automatiquement par Symfony UX Live Component (token inclus dans chaque requête de re-rendu). Cette garantie s'applique uniquement aux requêtes transitant par le composant. Les appels HTTP directs ne bénéficient pas du token CSRF mais sont rejetés par `#[IsGranted]` de toute façon (défense en profondeur).
- L'utilisateur peut marquer des livres depuis la fiche livre (`/livres/{slug}`) — l'extension à la carte catalogue (cartes book card) est hors scope de ce ticket (déjà partiellement implémentée en 015 via Live Component).
- La navigation au clavier s'appuie sur le comportement natif des éléments `<button>` HTML (focusables, activables via Enter/Space) complété par Bootstrap 5. L'outline de focus ne doit pas être supprimé via CSS.
