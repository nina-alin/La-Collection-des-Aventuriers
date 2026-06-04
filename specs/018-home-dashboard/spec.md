# Feature Specification: Page d'Accueil (Dashboard)

**Feature Branch**: `018-home-dashboard`

**Created**: 2026-06-02

**Status**: Draft

**Input**: User description: "Spécifications Fonctionnelles : Page d'Accueil (Dashboard)"

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Consultation du tableau de bord personnel (Priority: P1)

À l'arrivée sur la page d'accueil, un utilisateur authentifié voit immédiatement la date du jour, une salutation à son nom, un sous-titre synthétisant ce qu'il a manqué depuis sa dernière connexion, ainsi que ses trois blocs de KPIs personnels (collection, pile de lecture, suggestions).

**Why this priority**: C'est le cœur du tableau de bord. Toutes les autres sections en dépendent. Un dashboard sans header personnalisé ni stats personnelles n'a pas de valeur.

**Independent Test**: On peut tester cette story en isolation en vérifiant l'affichage du header et des trois blocs KPIs avec des données utilisateur connues, sans les autres sections.

**Acceptance Scenarios**:

1. **Given** un utilisateur connecté avec prénom "Marius", **When** il accède à la page d'accueil, **Then** il voit "MARDI 15 MAI" (date réelle du jour en majuscules), "SALUTATIONS, MARIUS." et son sous-titre contextuel.
2. **Given** un utilisateur dont la collection contient 47 livres (+3 au cours des 30 derniers jours), **When** il consulte le bloc "MA COLLECTION", **Then** il voit "47" comme valeur principale et "+3 ce mois" comme sous-titre.
3. **Given** un utilisateur avec 12 livres dans sa pile de lecture et 7 dans sa liste d'achats, **When** il consulte le bloc "À LIRE", **Then** il voit "12" et "7 en chasse".
4. **Given** un utilisateur avec 5 contributions (3 en attente, 1 validée hier), **When** il consulte "MES SUGGESTIONS", **Then** il voit "5" et "3 en attente · 1 validée hier".
5. **Given** un modérateur avec 4 tâches en attente et 2 nouvelles fiches depuis sa dernière connexion, **When** il consulte le sous-titre contextuel, **Then** il voit un message agrégeant ces deux informations.
6. **Given** un utilisateur standard (non modérateur) avec 2 nouvelles fiches depuis sa dernière connexion, **When** il consulte le sous-titre contextuel, **Then** le message ne mentionne pas de tâches de modération.

---

### User Story 2 — Navigation rapide depuis le dashboard (Priority: P2)

L'utilisateur utilise la grille de cartes d'accès rapides comme point de départ pour naviguer vers les sections principales de l'application. Chaque carte affiche un sous-titre dynamique refletant l'état actuel de ses données.

**Why this priority**: La grille d'accès rapides transforme le dashboard en hub de navigation. Sans elle, l'utilisateur doit utiliser le menu principal pour chaque action.

**Independent Test**: Tester la grille en vérifiant que chaque carte est présente, que ses sous-titres sont exacts, et que les liens redirigent vers la bonne destination. La carte de modération peut être testée séparément avec deux comptes (standard et modérateur).

**Acceptance Scenarios**:

1. **Given** n'importe quel utilisateur authentifié, **When** il consulte la grille d'accès rapides, **Then** il voit exactement 4 cartes : "PARCOURIR LE WIKI", "MA BIBLIOTHÈQUE", "FAIRE UNE SUGGESTION", "LISTE D'ACHATS".
2. **Given** un utilisateur standard (non modérateur/admin), **When** il consulte la grille, **Then** la carte "ÉDITER UNE FICHE" est absente du DOM.
3. **Given** un utilisateur avec le rôle Modérateur ou Administrateur, **When** il consulte la grille, **Then** la carte "ÉDITER UNE FICHE" est présente.
4. **Given** un catalogue contenant 312 fiches et 87 auteurs, **When** l'utilisateur voit la carte "PARCOURIR LE WIKI", **Then** le sous-titre affiche "312 FICHES · 87 AUTEURS" (ou équivalent).
5. **Given** un utilisateur avec 7 livres dans sa liste d'achats, **When** il voit la carte "LISTE D'ACHATS", **Then** le sous-titre affiche "7 LIVRES EN CHASSE".
6. **Given** un utilisateur cliquant sur la carte "FAIRE UNE SUGGESTION", **When** il clique, **Then** il est redirigé directement vers le formulaire de création de fiche.

---

### User Story 3 — Découverte des nouveautés du catalogue (Priority: P3)

L'utilisateur consulte les 5 dernières fiches ajoutées ou mises à jour dans le catalogue global, avec suffisamment d'informations pour décider si une fiche l'intéresse, et peut cliquer pour accéder à son détail.

**Why this priority**: Fonctionnalité de découverte importante mais non bloquante. Le dashboard reste fonctionnel sans cette section.

**Independent Test**: Tester en vérifiant que les 5 fiches les plus récentes apparaissent avec toutes les données requises, et que le clic redirige vers la bonne page de détail.

**Acceptance Scenarios**:

1. **Given** un catalogue avec des fiches, **When** l'utilisateur consulte "LES NOUVEAUTÉS", **Then** il voit exactement 5 fiches, triées par date de modification décroissante.
2. **Given** une fiche "Le Seigneur des Anneaux" de J.R.R. Tolkien (1954, ref: LCA-0107), notée 4.5/5, ajoutée il y a 2 heures, **When** elle apparaît dans "LES NOUVEAUTÉS", **Then** toutes ces données sont visibles (miniature, titre, auteur, année, référence, note en étoiles + valeur, "IL Y A 2 H").
3. **Given** une fiche dans "LES NOUVEAUTÉS", **When** l'utilisateur clique dessus, **Then** il est redirigé vers la page de détail de ce livre.
4. **Given** un lien "TOUT VOIR ->", **When** l'utilisateur clique, **Then** il est redirigé vers le catalogue complet.

---

### User Story 4 — Suivi de l'activité communautaire (Priority: P4)

L'utilisateur consulte un fil d'activité chronologique présentant les actions récentes de la communauté : notations, publications de fiches, validations de suggestions et ajouts aux listes.

**Why this priority**: Enrichit l'expérience sociale mais n'est pas critique au fonctionnement du dashboard.

**Independent Test**: Tester avec des événements connus de chaque type et vérifier que le fil affiche les bons libellés, avatars, badges et horodatages.

**Acceptance Scenarios**:

1. **Given** un événement de type "social" (notation), **When** il apparaît dans le fil, **Then** il affiche les initiales de l'auteur, une phrase type "@clem a noté [Titre du livre]" avec les entités en évidence, et l'horodatage relatif.
2. **Given** un événement de type "contribution" (publication de fiche), **When** il apparaît dans le fil, **Then** la phrase descriptive mentionne l'utilisateur et le titre de la fiche publiée.
3. **Given** un événement de type "modération" concernant l'utilisateur connecté, **When** il apparaît dans le fil, **Then** la phrase est rédigée à la deuxième personne (ex: "Tu as validé la suggestion…") et affiche un badge de statut (ex: "● VALIDÉE").
4. **Given** un événement lié à une suggestion, **When** il apparaît dans le fil, **Then** un badge de statut contextuel est présent (ex: "● VALIDÉE", "● EN ATTENTE").
5. **Given** un lien "MON FIL ->", **When** l'utilisateur clique, **Then** il est redirigé vers la vue complète du fil d'activité.

---

### User Story 5 — Accès au forum communautaire (Priority: P5)

L'utilisateur voit une bannière d'invitation à rejoindre le forum communautaire en bas de la page et peut y accéder en un clic.

**Why this priority**: Simple call-to-action. Très faible complexité, peut être livré séparément.

**Independent Test**: Vérifier la présence de la bannière et que le clic sur "Y aller ->" redirige vers l'URL du forum.

**Acceptance Scenarios**:

1. **Given** n'importe quel utilisateur authentifié, **When** il atteint le bas de la page d'accueil, **Then** il voit le bloc "REJOINDRE LA TAVERNE DES AVENTURIERS" avec un bouton "Y aller ->".
2. **Given** l'utilisateur clique sur "Y aller ->", **When** le clic se produit, **Then** il est redirigé vers l'URL du forum.

---

### Edge Cases

- Que se passe-t-il si l'utilisateur n'a aucun livre dans sa collection ? Les blocs KPIs affichent "0" sans sous-titre d'erreur.
- Que se passe-t-il si l'utilisateur se connecte pour la première fois (pas de "dernière connexion") ? Le sous-titre contextuel affiche un message de bienvenue générique sans données d'activité manquée.
- Que se passe-t-il si le catalogue contient moins de 5 fiches ? "LES NOUVEAUTÉS" affiche le nombre de fiches disponibles (moins de 5).
- Que se passe-t-il si le fil d'activité est vide ? La section "ACTIVITÉ" affiche un message indiquant qu'il n'y a pas encore d'activité.
- Que se passe-t-il si l'utilisateur n'a aucune suggestion ? Le bloc "MES SUGGESTIONS" affiche "0" sans sous-titre de statut.
- Que se passe-t-il si un utilisateur perd son rôle modérateur entre deux sessions, ou en cours de session active ? La carte "ÉDITER UNE FICHE" disparaît au prochain chargement de page (que ce soit dans la même session ou lors d'une nouvelle connexion). Le contrôle de rôle est effectué à chaque rendu du dashboard.
- Que se passe-t-il si la requête de données d'une section échoue (exception repository) ? La section concernée affiche un bloc d'erreur inline ("Impossible de charger cette section") sans bloquer le rendu des autres sections. Chaque section est encapsulée dans un bloc try/catch indépendant dans le `DashboardService`.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Le système DOIT afficher la date du jour formatée en majuscules avec le nom du jour et la date. Le jour est zero-paddé sur deux chiffres ; l'année n'est pas affichée (ex : "MARDI 15 MAI", "LUNDI 05 JUIN").
- **FR-002**: Le système DOIT afficher une salutation personnalisée incluant le prénom ou pseudo de l'utilisateur connecté, en majuscules (ex : "SALUTATIONS, MARIUS.").
- **FR-003**: Le système DOIT afficher un sous-titre contextuel agrégeant, depuis la dernière connexion de l'utilisateur : le nombre de nouvelles fiches ajoutées au catalogue global, et — si l'utilisateur est modérateur ou administrateur — le nombre de suggestions en attente dans le système (toutes suggestions confondues, quel qu'en soit l'auteur ; même source de données que le sous-titre de la carte FR-012). Format utilisateur standard : `"[N] nouvelle(s) fiche(s) depuis ta dernière visite"`. Format modérateur/admin : `"[N] nouvelle(s) fiche(s) · [M] suggestion(s) en attente"`.
- **FR-004**: Le système DOIT afficher un bloc KPI "MA COLLECTION" indiquant le total de livres possédés et le delta d'ajouts sur une fenêtre glissante de 30 jours (ex : "+3 ce mois").
- **FR-005**: Le système DOIT afficher un bloc KPI "À LIRE" indiquant le nombre de livres dans la pile de lecture et le nombre de livres dans la liste d'achats (ex : "7 en chasse").
- **FR-006**: Le système DOIT afficher un bloc KPI "MES SUGGESTIONS" indiquant le total des contributions de l'utilisateur et un résumé des statuts récents. Le sous-titre affiche : le nombre de suggestions en attente, suivi — si au moins une suggestion a été validée dans les dernières 24 h — du compte avec horodatage relatif (ex : "3 en attente · 1 validée hier", "3 en attente · 2 validées aujourd'hui"). Si aucune suggestion n'a été validée dans les dernières 24 h, seul le nombre en attente est affiché.
- **FR-007**: Le système DOIT afficher une grille d'accès rapides contenant les 4 cartes standard suivantes, toujours visibles pour tout utilisateur authentifié : "PARCOURIR LE WIKI", "MA BIBLIOTHÈQUE", "FAIRE UNE SUGGESTION", "LISTE D'ACHATS".
- **FR-008**: La carte "PARCOURIR LE WIKI" DOIT afficher un sous-titre dynamique avec les compteurs globaux du catalogue (nombre total de fiches et d'auteurs).
- **FR-009**: La carte "MA BIBLIOTHÈQUE" DOIT afficher un sous-titre dynamique au format `[X] LIVRES · [Y] À LIRE` (total des livres possédés et taille de la pile de lecture).
- **FR-010**: La carte "LISTE D'ACHATS" DOIT afficher un sous-titre dynamique indiquant le nombre de livres dans la liste d'achats de l'utilisateur (ex : "7 LIVRES EN CHASSE").
- **FR-011**: La carte "FAIRE UNE SUGGESTION" DOIT être présentée avec un style visuel distinctif (mise en avant) et rediriger vers le formulaire de création de fiche.
- **FR-012**: Le système DOIT afficher la carte "ÉDITER UNE FICHE" (Outils de modération) UNIQUEMENT si l'utilisateur possède le rôle Modérateur ou Administrateur. Cette carte ne doit pas être rendue dans le DOM pour les utilisateurs sans ces rôles. La carte DOIT afficher un sous-titre dynamique indiquant le nombre global de suggestions en attente dans le système (ex : "3 EN ATTENTE"), en utilisant la même source de données que le compteur [M] de FR-003. La destination de la carte est `/suggestions`.
- **FR-013**: La section "LES NOUVEAUTÉS" DOIT afficher les 5 dernières fiches ajoutées ou mises à jour dans le catalogue global, triées par `updated_at` décroissant.
- **FR-014**: Chaque fiche dans "LES NOUVEAUTÉS" DOIT afficher : miniature de couverture (ou un placeholder par défaut si l'image est absente ou en erreur), titre (en gras), auteur, année de publication, référence (ex : "LCA-0107"), note moyenne sous forme d'étoiles et de valeur numérique (arrondie au 0,5 le plus proche ; les demi-étoiles sont affichées), et horodatage relatif (ex : "IL Y A 2 H", "HIER").
- **FR-015**: Un clic sur une fiche dans "LES NOUVEAUTÉS" DOIT rediriger l'utilisateur vers la page de détail de ce livre.
- **FR-016**: La section "LES NOUVEAUTÉS" DOIT comporter un lien "TOUT VOIR ->" redirigeant vers le catalogue complet.
- **FR-017**: La section "ACTIVITÉ" DOIT afficher un fil chronologique limité aux 10 événements les plus récents de la communauté, couvrant les 4 types : social (notation), contribution (publication de fiche), modération (action de l'utilisateur), personnel (ajout à une liste). La pagination et le chargement progressif ("load more") ne sont pas disponibles dans cette section ; la vue complète est accessible via "MON FIL ->" (FR-019).
- **FR-018**: Chaque événement dans "ACTIVITÉ" DOIT afficher : avatar avec les initiales de l'auteur de l'action, phrase descriptive avec les entités clés mises en évidence (noms d'utilisateurs, titres de livres), badge de statut contextuel si l'événement concerne une suggestion (ex : "● VALIDÉE", "● EN ATTENTE"), et horodatage relatif. Les événements de type "personnel" (ajout à une liste d'achats) sont visibles dans le flux global communautaire et rédigés à la troisième personne (ex : "@alice a ajouté X à sa liste d'achats") ; seuls les événements de type "modération" concernant l'utilisateur connecté sont rédigés à la deuxième personne.
- **FR-019**: La section "ACTIVITÉ" DOIT comporter un lien "MON FIL ->" redirigeant vers la vue complète du fil d'activité.
- **FR-020**: Le bas de page DOIT afficher une bannière "REJOINDRE LA TAVERNE DES AVENTURIERS" avec un bouton "Y aller ->" renvoyant vers le forum.
- **FR-021**: Le dashboard DOIT être rendu de manière responsive et mobile-first. La mise en page s'adapte aux différentes tailles d'écran. Les breakpoints et les adaptations visuelles spécifiques sont définis en phase de design.

### Non-Functional Requirements

- **NFR-001**: Les enregistrements `ActivityEvent` DOIVENT être purgés automatiquement 30 jours après leur création (`created_at`). La purge est déclenchée par une tâche planifiée mensuelle.

### Key Entities

- **DashboardHeader** : Agrégation de la date formatée, de la salutation personnalisée, du sous-titre contextuel (activité manquée depuis la dernière connexion). Dépend du profil utilisateur et de son rôle.
- **KPIBlock** : Libellé de catégorie, valeur principale (nombre), sous-titre dynamique. Trois instances : Collection, À lire, Suggestions.
- **QuickAccessCard** : Libellé, URL de destination, sous-titre dynamique, style visuel (standard ou mis en avant), règle de visibilité par rôle (RBAC).
- **BookEntry** (Nouveautés) : Miniature de couverture, titre, auteur, année, référence catalogue, note moyenne, horodatage relatif de la dernière modification.
- **ActivityEvent** : Type d'événement (social / contribution / modération / personnel), auteur de l'action (avatar/initiales), texte descriptif avec entités soulignées, badge de statut optionnel, horodatage relatif. Implémenté comme une entité Doctrine dédiée, alimentée par des Symfony event listeners à chaque action communautaire.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: L'utilisateur visualise l'intégralité de la page d'accueil (header + KPIs + accès rapides + flux) en moins de 3 secondes après connexion.
- **SC-002**: Les trois blocs KPIs reflètent exactement l'état réel de la collection, de la pile de lecture et des suggestions de l'utilisateur au moment de la visite.
- **SC-003**: La carte "ÉDITER UNE FICHE" est présente dans 100% des sessions de modérateurs/administrateurs et absente dans 100% des sessions d'utilisateurs standard.
- **SC-004**: Les 5 fiches affichées dans "LES NOUVEAUTÉS" correspondent systématiquement aux 5 entrées les plus récentes du catalogue au moment de la visite.
- **SC-005**: Le fil d'activité couvre les 4 types d'événements définis (social, contribution, modération, personnel) et chaque événement affiche toutes les données requises (avatar, texte, horodatage).
- **SC-006**: *(Aspirationnelle — non validable sans système analytics)* 90% des utilisateurs trouvent et utilisent la section d'accès rapides comme point de navigation principal. Aucun outil de collecte n'est prévu dans cette feature ; cet objectif pourra être mesuré si une solution analytics est intégrée ultérieurement.
- **SC-007**: Le sous-titre contextuel du header est absent ou générique pour les utilisateurs se connectant pour la première fois (aucun message d'erreur affiché).
- **SC-008**: La page d'accueil est conforme WCAG 2.1 niveau AA : navigation clavier complète sur tous les éléments interactifs (cartes, liens, boutons), attributs `alt` renseignés sur toutes les images de couverture, contraste des textes ≥ 4.5:1, et attributs ARIA appropriés sur les composants non-sémantiques.

## Assumptions

- L'utilisateur doit être authentifié pour accéder au tableau de bord. Les utilisateurs non connectés sont redirigés vers la page de connexion.
- La "dernière connexion" fait référence à la session précédente, pas à la session en cours.
- Pour les utilisateurs se connectant pour la première fois (pas d'historique de connexion), le sous-titre contextuel affiche un message de bienvenue générique.
- Le fil d'activité ("ACTIVITÉ") présente les événements de l'ensemble de la communauté (flux global), non limité à un réseau de contacts de l'utilisateur, car aucun système de "suivi" entre utilisateurs n'est défini.
- Le nombre d'événements affichés dans "ACTIVITÉ" est limité aux 10 plus récents (voir FR-017) ; la vue complète est accessible via "MON FIL ->".
- Les horodatages relatifs utilisent des paliers lisibles (ex : "IL Y A 5 MIN", "IL Y A 2 H", "HIER", "IL Y A 3 JOURS").
- L'URL du forum (bannière pied de page) est une valeur de configuration statique définie dans l'application.
- Les compteurs des cartes d'accès rapides et les KPIs sont calculés au chargement de la page (pas en temps réel via un flux de données persistant). Aucune mise en cache applicative ou HTTP n'est utilisée : des requêtes Doctrine fraîches sont émises à chaque visite, la performance étant assurée par les index de la base de données.
- La carte "MA BIBLIOTHÈQUE" utilise les mêmes compteurs que les blocs KPIs de la section header.
- Le dashboard est rendu entièrement côté serveur (SSR) via un `DashboardService` dédié qui agrège toutes les données avant l'envoi de la réponse HTTP. Il n'y a pas de chargement asynchrone côté client.
- Les données du fil d'activité communautaire sont stockées dans une entité `ActivityEvent` dédiée, distincte du système de notifications personnelles (`Notification`). Des Symfony event listeners alimentent cette table à chaque action communautaire pertinente. La durée de rétention et la purge sont définies dans NFR-001.

## Clarifications

### Session 2026-06-03

- Q: Que se passe-t-il si une section du dashboard est lente ou échoue à charger ? → A: Chaque section dispose d'un bloc d'erreur inline indépendant ; si une section échoue, elle affiche "Impossible de charger cette section" sans bloquer le reste de la page.
- Q: Quelle est la stratégie de rendu pour le dashboard (SSR vs chargement dynamique) ? → A: Rendu entièrement côté serveur via un `DashboardService` agrégateur ; pas de skeleton loaders ni de fetch côté client.
- Q: Quel sous-titre dynamique la carte "MA BIBLIOTHÈQUE" doit-elle afficher ? → A: `[X] LIVRES · [Y] À LIRE` (total possédés + pile de lecture).
- Q: Quel est le format exact du sous-titre contextuel du header pour un utilisateur standard ? → A: `"[N] nouvelle(s) fiche(s) depuis ta dernière visite"` ; pour les modérateurs/admins : `"[N] nouvelle(s) fiche(s) · [M] tâche(s) de modération en attente"`.
- Q: Comment les données du fil d'activité communautaire (section "ACTIVITÉ") doivent-elles être sourcées ? → A: Nouvelle entité `ActivityEvent` alimentée par des Symfony event listeners, distincte du système de notifications personnelles.
- Q: Faut-il une stratégie de mise en cache pour les requêtes agrégées du `DashboardService` ? → A: Non — requêtes DB fraîches à chaque chargement, sans couche de cache additionnelle (Doctrine + index DB suffisants pour tenir SC-001).
- Q: Quelle est la règle exacte pour la partie "validée(s)" du sous-titre KPI "MES SUGGESTIONS" ? → A: Compter les suggestions validées dans les dernières 24 h et afficher avec horodatage relatif — ex : "1 validée hier", "2 validées aujourd'hui" ; 0 validée → sous-titre n'affiche que les en-attente.
- Q: Quelle est la durée de rétention des enregistrements `ActivityEvent` avant purge automatique ? → A: 30 jours — purge mensuelle automatique.
- Q: Les événements de type "personnel" (ajout à une liste d'achats) dans le fil "ACTIVITÉ" sont-ils visibles à toute la communauté ou uniquement à l'utilisateur concerné ? → A: Global — visibles à toute la communauté, rédigés à la troisième personne (ex : "@alice a ajouté X à sa liste d'achats").
- Q: Y a-t-il des exigences d'accessibilité à respecter pour le dashboard ? → A: Oui — conformité WCAG 2.1 niveau AA.
- Q: SC-006 (90 % des utilisateurs utilisent les accès rapides) est-elle validable via un système analytics existant ? → A: Non — aspirationnelle ; aucun système analytics n'est en place, cette métrique est un objectif UX non mesuré pour l'instant.

### Session 2026-06-03 (implementation gate)

- Q: Les "tâches de modération en attente" dans FR-003 sont-elles les mêmes que les "suggestions en attente" de FR-006 ? → A: Oui — même source de données : count global des suggestions au statut "en attente", tous auteurs confondus.
- Q: Le calcul "+X ce mois" (FR-004) est-il une fenêtre glissante de 30 jours ou le mois calendaire en cours ? → A: Fenêtre glissante de 30 jours.
- Q: Quel champ sert de référence pour le tri de "LES NOUVEAUTÉS" (FR-013) ? → A: `updated_at`.
- Q: Le jour dans la date (FR-001) est-il zero-paddé ? → A: Oui — "LUNDI 05 JUIN".
- Q: Les étoiles de notation (FR-014) supportent-elles les demi-étoiles ? → A: Oui — arrondi au 0,5 le plus proche.
- Q: Que faut-il afficher si une miniature de couverture est absente ou cassée (FR-014) ? → A: Un placeholder par défaut.
- Q: La carte "ÉDITER UNE FICHE" a-t-elle un sous-titre dynamique et quelle est sa destination ? → A: Oui — sous-titre affichant le nombre global de suggestions en attente (même source que FR-003). Destination : `/suggestions`.
- Q: Si le rôle modérateur est révoqué en cours de session active, la carte disparaît-elle au prochain rechargement de page ou seulement à la reconnexion ? → A: Au prochain chargement de page de la même session.
- Q: Le dashboard est-il responsive / mobile-first ? → A: Oui — mobile-first. Breakpoints et adaptations visuelles définis en phase de design (FR-021).
- Q: La limite de 10 événements dans "ACTIVITÉ" (Assumptions) doit-elle être une exigence formelle ? → A: Oui — intégrée dans FR-017.
- Q: La purge des `ActivityEvent` (30 jours, Assumptions) doit-elle être une NFR formelle ? → A: Oui — ajoutée comme NFR-001.
- Q: Les paliers des horodatages relatifs doivent-ils être spécifiés dans la spec ? → A: Non — décision d'implémentation.
- Q: Les exigences d'accessibilité (SC-008) doivent-elles être détaillées par composant dans la spec ? → A: Non — décision d'implémentation.
