# Feature Specification: Menu Profil Utilisateur Responsive

**Feature Branch**: `013-user-profile-menu`

**Created**: 2026-05-31

**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Accès au menu profil (Priority: P1)

Un utilisateur connecté clique sur son avatar dans la barre de navigation. Sur desktop, un menu déroulant apparaît sous l'avatar. Sur mobile, un panneau bottom-sheet surgit depuis le bas de l'écran avec une animation slide-up. L'utilisateur peut refermer le menu en cliquant en dehors, en effectuant un swipe vers le bas (mobile), ou en recliquant sur l'avatar.

**Why this priority**: Point d'entrée unique vers toutes les actions utilisateur ; sans ce comportement, rien du reste du composant n'est accessible.

**Independent Test**: Cliquer sur l'avatar en version desktop vérifie l'ouverture/fermeture du dropdown ; reproduire en viewport mobile vérifie le drawer avec animation.

**Acceptance Scenarios**:

1. **Given** l'utilisateur est connecté et sur n'importe quelle page, **When** il clique sur son avatar, **Then** le menu s'ouvre (dropdown sur desktop, bottom-sheet animé depuis le bas sur mobile)
2. **Given** le menu est ouvert, **When** l'utilisateur clique en dehors du composant (backdrop), **Then** le menu se ferme
3. **Given** le menu est ouvert sur mobile, **When** l'utilisateur effectue un swipe vers le bas, **Then** le menu se ferme
4. **Given** le menu est ouvert, **When** l'utilisateur reclique sur l'avatar, **Then** le menu se ferme
5. **Given** le menu est ouvert, **When** l'utilisateur appuie sur Escape, **Then** le menu se ferme et le focus retourne sur l'avatar déclencheur
6. **Given** le menu est ouvert, **When** l'utilisateur navigue avec Tab/flèches, **Then** le focus se déplace entre les items du menu sans sortir du composant

---

### User Story 2 - En-tête utilisateur et badge de rôle (Priority: P2)

L'utilisateur ouvre le menu et voit son prénom, son nom, son avatar avec indicateur de statut, ainsi qu'un message de bienvenue "BONJOUR". Si l'utilisateur est modérateur ou administrateur, un badge de rôle est affiché. Un utilisateur standard ne voit aucun badge.

**Why this priority**: L'en-tête établit le contexte d'identité et conditionne la confiance de l'utilisateur ; les badges RBAC évitent toute divulgation de rôle involontaire.

**Independent Test**: Se connecter avec trois types de comptes (standard, modérateur, administrateur) et vérifier la présence/absence du badge à chaque fois.

**Acceptance Scenarios**:

1. **Given** l'utilisateur est connecté avec un rôle standard, **When** il ouvre le menu, **Then** l'en-tête affiche "BONJOUR", pseudo, avatar et pastille de statut, et aucun badge de rôle n'est présent dans le DOM
2. **Given** l'utilisateur est connecté avec le rôle Modérateur, **When** il ouvre le menu, **Then** un badge "● MODÉRATEUR" est affiché dans l'en-tête
3. **Given** l'utilisateur est connecté avec le rôle Administrateur, **When** il ouvre le menu, **Then** un badge "● ADMINISTRATEUR" est affiché dans l'en-tête

---

### User Story 3 - Navigation personnelle (Priority: P2)

L'utilisateur peut accéder à son profil public et à son tableau de bord de contributions depuis le menu. Chaque lien affiche une métadonnée dynamique alignée à droite : le rang/titre pour "Mon Profil", le compteur de suggestions validées pour "Mes Suggestions".

**Why this priority**: Navigation vers les pages les plus consultées par l'utilisateur après ouverture du menu.

**Independent Test**: Vérifier que les liens redirigent vers les bonnes pages et que les valeurs dynamiques correspondent aux données de l'utilisateur connecté.

**Acceptance Scenarios**:

1. **Given** le menu est ouvert, **When** l'utilisateur consulte la section navigation personnelle, **Then** il voit "Mon Profil" avec son rang actuel (ex : "AVENTURIER") aligné à droite et "Mes Suggestions" avec son compteur de validations (ex : "17 VALIDÉES") aligné à droite
2. **Given** le menu est ouvert, **When** l'utilisateur clique sur "Mon Profil", **Then** il est redirigé vers sa page de profil public
3. **Given** le menu est ouvert, **When** l'utilisateur clique sur "Mes Suggestions", **Then** il est redirigé vers son tableau de bord de contributions

---

### User Story 4 - Outils de modération (accès restreint) (Priority: P3)

Un utilisateur ayant le rôle Modérateur ou Administrateur voit une section "OUTILS DE MODÉRATION" avec un lien "Salle de Modération" indiquant le nombre de tâches en attente. Cette section est totalement absente du DOM pour un utilisateur standard.

**Why this priority**: Fonctionnalité critique de sécurité (RBAC) mais concerne une minorité d'utilisateurs.

**Independent Test**: Inspecter le DOM avec un compte standard (section absente) puis avec un compte modérateur (section présente avec compteur).

**Acceptance Scenarios**:

1. **Given** l'utilisateur a le rôle standard, **When** il ouvre le menu, **Then** le titre "OUTILS DE MODÉRATION" et le bouton "Salle de Modération" sont absents du DOM
2. **Given** l'utilisateur a le rôle Modérateur ou Administrateur, **When** il ouvre le menu, **Then** la section "OUTILS DE MODÉRATION" est présente avec le lien "Salle de Modération" affichant le compteur de tâches en attente (ex : "23 À RELIRE")
3. **Given** la section est visible, **When** l'utilisateur clique sur "Salle de Modération", **Then** il est redirigé vers le back-office de modération

---

### User Story 5 - Bascule de thème (Priority: P3)

L'utilisateur peut changer le thème global de l'interface (mode sombre / parchemin) via un toggle switch dans la section préférences. L'état du toggle reflète le thème actuellement actif.

**Why this priority**: Préférence visuelle importante mais non bloquante pour les flux principaux.

**Independent Test**: Activer le toggle, recharger la page et vérifier que le thème persiste et que l'état du switch correspond.

**Acceptance Scenarios**:

1. **Given** le menu est ouvert, **When** l'utilisateur consulte la section préférences, **Then** il voit un toggle "Thème Parchemin" dont l'état reflète le thème actuellement appliqué
2. **Given** le toggle est en position "désactivé", **When** l'utilisateur l'active, **Then** le thème de l'interface bascule vers le mode parchemin/clair et le changement persiste entre les sessions
3. **Given** le toggle est en position "activé", **When** l'utilisateur le désactive, **Then** le thème revient en mode sombre et le changement persiste

---

### User Story 6 - Déconnexion (Priority: P1)

L'utilisateur peut se déconnecter via un bouton de déconnexion stylisé en alerte (rouge) avec la mention "SORTIR" alignée à droite. L'action ferme la session et redirige l'utilisateur.

**Why this priority**: Action de sécurité fondamentale accessible depuis le menu profil.

**Independent Test**: Cliquer sur "Se déconnecter" et vérifier que la session est détruite et la redirection effectuée.

**Acceptance Scenarios**:

1. **Given** le menu est ouvert, **When** l'utilisateur clique sur "Se déconnecter", **Then** sa session est fermée et il est redirigé vers la page d'accueil ou de connexion
2. **Given** le menu est ouvert, **When** l'utilisateur observe le bouton de déconnexion, **Then** il est stylisé en rouge/alerte avec la mention "SORTIR" visible à droite

---

### ~~User Story 7 - Actions annexes mobile~~ (supprimée)

*Supprimée : le bottom-sheet mobile affiche uniquement le contenu du menu standard, sans footer supplémentaire. FR-014 mis à jour en conséquence.*

---

### Edge Cases

- Que se passe-t-il si le Repository Symfony ne peut pas charger les données dynamiques (rang, compteur de suggestions, compteur de modération) ? → Afficher "—" (tiret) silencieusement à la place de la valeur, sans message d'erreur visible.
- Que se passe-t-il si l'avatar n'est pas disponible ? → Afficher un avatar par défaut (initiales ou icône générique).
- Que se passe-t-il si la session expire pendant que le menu est ouvert ? → Au clic sur un lien, rediriger vers la page de connexion.
- Que se passe-t-il si l'utilisateur a plusieurs rôles (ex : Modérateur ET Administrateur) ? → Afficher le badge du rôle le plus élevé (Administrateur prioritaire).
- Le design de référence (dossier design au niveau de la navbar) fait autorité pour tous les détails visuels non spécifiés ici.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Le composant DOIT s'ouvrir en mode dropdown ancré sous l'avatar sur les écrans ≥ 720px (desktop).
- **FR-002**: Le composant DOIT s'ouvrir en mode bottom-sheet avec animation slide-up depuis le bas sur les écrans < 720px (mobile), conformément à `design/assets/menus.css`.
- **FR-003**: Le drawer mobile DOIT présenter une poignée/barre de drag en haut du panneau.
- **FR-004**: Le composant DOIT se fermer au clic sur le backdrop, au swipe vers le bas (mobile, détecté via événements touch natifs touchstart/touchmove/touchend sans dépendance externe), ou au re-clic sur l'avatar déclencheur.
- **FR-005**: L'en-tête DOIT afficher "BONJOUR", le pseudo, l'avatar et une pastille de statut d'activité pour tout utilisateur connecté. La pastille est purement décorative : couleur et style définis par les maquettes uniquement, aucune logique dynamique ni appel API.
- **FR-006**: Le badge de rôle (MODÉRATEUR ou ADMINISTRATEUR) NE DOIT être rendu dans le DOM que si l'utilisateur possède effectivement ce rôle ; aucun badge pour un utilisateur standard.
- **FR-007**: La section "Mon Profil" DOIT afficher le rang/titre de l'utilisateur aligné à droite, chargé depuis le Repository Symfony au rendu de la page (aucun appel API asynchrone, aucun skeleton).
- **FR-008**: La section "Mes Suggestions" DOIT afficher le compteur de suggestions validées aligné à droite, chargé depuis le Repository Symfony au rendu de la page (aucun appel API asynchrone, aucun skeleton).
- **FR-009**: La section "OUTILS DE MODÉRATION" et son contenu NE DOIVENT être rendus dans le DOM que si l'utilisateur a le rôle Modérateur ou Administrateur.
- **FR-010**: L'item "Salle de Modération" DOIT afficher le compteur de tâches en attente aligné à droite, chargé depuis le Repository Symfony au rendu de la page (aucun appel API asynchrone, aucun skeleton).
- **FR-011**: L'item "Thème Parchemin" DOIT être un toggle switch dont l'état reflète le thème actif (persisté en localStorage).
- **FR-012**: L'activation/désactivation du toggle DOIT changer le thème global de l'interface et persister ce choix via localStorage (aucun appel API requis, préférence locale uniquement).
- **FR-013**: Le bouton "Se déconnecter" DOIT être stylisé en alerte (rouge) avec la mention "SORTIR" et DOIT invoquer la logique de déconnexion existante dans le projet (réutiliser le bouton/handler existant — ne pas réimplémenter).
- **FR-014**: Sur mobile, le bottom-sheet affiche uniquement le contenu du menu (en-tête, navigation, modération conditionnelle, préférences, déconnexion) — aucun footer sticky supplémentaire.
- **FR-015**: Les items "Paramètres" et "Aide & raccourcis" présents sur la maquette NE DOIVENT PAS être intégrés dans le DOM.
- **FR-016**: Le composant DOIT respecter strictement les maquettes desktop et mobile situées dans le dossier design au niveau de la navbar. Exception : le lien "Mon Profil" utilise `href="#"` comme placeholder — la route de profil public n'existe pas encore ; elle sera câblée dans la feature qui implémente cette page.
- **FR-017**: Le déclencheur (avatar) DOIT porter les attributs `aria-haspopup="menu"`, `aria-expanded` reflétant l'état ouvert/fermé, et `aria-controls` pointant vers l'identifiant du conteneur menu.
- **FR-018**: Le menu DOIT être navigable au clavier : Tab/Shift+Tab parcourt les items, les touches fléchées (haut/bas) permettent la navigation entre items, Escape ferme le menu et restitue le focus au déclencheur.
- **FR-019**: Le drawer mobile DOIT implémenter un piège de focus (focus trap) tant qu'il est ouvert.
- **FR-020**: Chaque item de menu DOIT avoir un rôle ARIA approprié (`role="menuitem"`) et le conteneur `role="menu"`.
- **FR-021**: Le composant DOIT être implémenté avec Symfony UX (Twig Component) pour le rendu server-side ; un Stimulus controller est utilisé uniquement si un comportement JavaScript interactif (ouverture/fermeture, swipe, focus trap) l'exige.

### Key Entities

- **Utilisateur** : Entité principale — prénom, nom, avatar, statut d'activité, rôle(s), rang/titre, compteur de suggestions validées.
- **Rôle** : Attribut de l'utilisateur (standard, Modérateur, Administrateur) conditionnant l'affichage de certains éléments du menu.
- **Thème** : Préférence visuelle persistée (parchemin/clair ou sombre) — reflétée par le toggle et appliquée globalement.
- **Tâches de modération** : Compteur de tâches en attente exposé aux modérateurs/administrateurs.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un utilisateur peut ouvrir et fermer le menu profil en moins de 2 secondes, quelle que soit la taille d'écran.
- **SC-002**: L'animation d'ouverture du drawer mobile est fluide et perçue comme instantanée (transition CSS ≤300ms, pas de saccade visible).
- **SC-003**: 100% des éléments RBAC restreints (badge de rôle, section modération) sont absents du DOM pour les utilisateurs sans le rôle correspondant.
- **SC-004**: Le changement de thème via le toggle est appliqué à l'interface en moins d'une seconde et persiste après rechargement de page.
- **SC-005**: La mise en page respecte visuellement les maquettes de référence sur les deux breakpoints (desktop et mobile) à 100%.
- **SC-006**: La déconnexion redirige l'utilisateur dans moins de 2 secondes après le clic.

## Clarifications

### Session 2026-05-31

- Q: Où la préférence de thème (parchemin/sombre) est-elle persistée ? → A: localStorage uniquement (pas de synchronisation serveur, préférence locale par appareil)
- Q: Comment les données dynamiques (rang, compteurs) sont-elles chargées ? → A: Via Repository Symfony au rendu de la page (server-side) — pas de fetch API asynchrone, pas de lazy-load, pas de skeleton
- Q: Quel niveau d'accessibilité clavier est requis ? → A: Navigation clavier complète (Tab, flèches, Escape, piège de focus sur drawer mobile, ARIA complet)
- Q: Quel breakpoint sépare le dropdown desktop du drawer mobile ? → A: 720px — desktop ≥ 720px (dropdown), mobile < 720px (drawer), conforme aux media queries de menus.css
- Q: Comment détecter le swipe vers le bas sur mobile (fermeture du bottom-sheet) ? → A: Événements touch natifs (touchstart/touchmove/touchend), sans dépendance externe
- Q: Que représente la pastille de statut d'activité et comment est-elle déterminée ? → A: Purement décorative — couleur fixe définie par les maquettes, aucune logique dynamique ni appel API
- Q: Que afficher quand un appel API lazy-load échoue (rang, compteurs) ? → A: Valeur neutre "—" (tiret) à la place du compteur/rang, silencieux — aucun message d'erreur visible (si erreur Repository, fallback "—" en Twig)
- Q: Les données dynamiques (rang, compteurs) sont-elles chargées via API ou via Repository ? → A: Via Repository Symfony au rendu de la page — pas de fetch API, pas de lazy-load, pas de skeleton
- Q: Quelle approche JavaScript pour le composant menu ? → A: Symfony UX (Twig Components) en priorité, Stimulus controller si comportement interactif nécessaire
- Q: Vers quelle page rediriger après déconnexion ? → A: Réutiliser la logique du bouton de déconnexion existant dans le projet — ne pas réimplémenter

## Assumptions

- Les données d'identité (prénom, nom, avatar, rôle) proviennent du contexte d'authentification existant. Les compteurs dynamiques (rang, suggestions validées, tâches de modération) sont chargés depuis les Repository Symfony au rendu de la page — aucun appel API asynchrone, aucun skeleton requis.
- Le système de rôles RBAC est opérationnel (cf. spec `004-rbac-roles-permissions`).
- Le système de thème global (toggle parchemin/sombre) persiste la préférence exclusivement via localStorage ; aucune synchronisation serveur n'est requise.
- Les maquettes de référence (dossier design, niveau navbar) sont les sources de vérité visuelles pour les détails CSS non spécifiés ici.
- Sur desktop, le breakpoint séparant dropdown et bottom-sheet est **720px** : ≥ 720px → dropdown ancré, < 720px → bottom-sheet depuis le bas (conforme à `design/assets/menus.css`).
- Un utilisateur ne peut avoir qu'un seul rôle actif au plus parmi Modérateur et Administrateur ; si plusieurs rôles coexistent, le badge affiche le plus élevé (Administrateur > Modérateur).
