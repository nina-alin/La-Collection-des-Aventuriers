# Feature Specification: Page "Créateurs" — Galerie des Bâtisseurs

**Feature Branch**: `024-createurs-page`

**Created**: 2026-06-09

**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Parcourir la galerie des créateurs (Priority: P1)

Un visiteur ou utilisateur connecté accède à la page "Créateurs" depuis la navigation principale. Il voit une grille de cartes présentant les créateurs (auteurs, illustrateurs, traducteurs) avec leurs informations essentielles : avatar/initiales, nom, nationalité, rôles, description tronquée, collections associées, nombre d'ouvrages et note moyenne.

**Why this priority**: Point d'entrée principal de la fonctionnalité. Sans cette page, aucune autre interaction n'est possible.

**Independent Test**: Naviguer vers `/createurs`, vérifier que les cartes s'affichent avec les données correctes de la base.

**Acceptance Scenarios**:

1. **Given** l'utilisateur est sur n'importe quelle page, **When** il clique sur "Créateurs" dans la navigation, **Then** il arrive sur la galerie avec les cartes créateur paginées.
2. **Given** la page est chargée, **When** l'utilisateur consulte une carte, **Then** il voit : avatar (initiales ou photo), nom complet, nationalité · dates, badges de rôles, description (max 3 lignes tronquée), tags de 1-2 collections, compteur d'ouvrages, note moyenne, bouton "Suivre".
3. **Given** un créateur n'a pas de photo, **When** sa carte s'affiche, **Then** un avatar généré avec ses initiales est présenté.
4. **Given** la description d'un créateur dépasse 3 lignes, **When** la carte s'affiche, **Then** le texte est tronqué avec "..." à la fin.

---

### User Story 2 — Filtrer par métier et naviguer via l'index alphabétique (Priority: P2)

L'utilisateur utilise la barre de métiers (Tous / Auteurs / Traducteurs / Illustrateurs) et l'index alphabétique (A–Z) pour restreindre les résultats affichés.

**Why this priority**: Filtres principaux permettant de naviguer dans un catalogue potentiellement large.

**Independent Test**: Cliquer sur "Auteurs" → vérifier que seuls les auteurs s'affichent avec compteur mis à jour. Cliquer "A" → vérifier uniquement les noms commençant par A.

**Acceptance Scenarios**:

1. **Given** la page est chargée, **When** l'utilisateur clique sur "Auteurs", **Then** seuls les créateurs ayant le rôle Auteur sont affichés, le compteur reflète ce sous-ensemble.
2. **Given** un filtre de métier est actif, **When** l'index alphabétique s'affiche, **Then** les lettres sans créateur correspondant sont grisées et non cliquables.
3. **Given** l'utilisateur clique sur "B" dans l'index, **When** les résultats se mettent à jour, **Then** seuls les créateurs dont le nom commence par B sont affichés, et "B" apparaît en état actif.
4. **Given** aucun filtre alphabétique n'est actif, **When** la page charge, **Then** aucune lettre n'est sélectionnée par défaut.
5. **Given** des filtres actifs (métier + lettre), **When** une puce (chip) est supprimée, **Then** le filtre correspondant est retiré et la liste se met à jour.

---

### User Story 3 — Recherche autocomplete in-page (Priority: P2)

L'utilisateur tape dans la barre de recherche contextuelle de la page. Un panneau déroulant s'ouvre immédiatement avec les résultats catégorisés par métier, les occurrences recherchées mises en surbrillance.

**Why this priority**: Accès rapide ciblé à un créateur spécifique sans passer par les filtres.

**Independent Test**: Taper "jack" → vérifier dropdown avec sections "AUTEURS - N RÉSULTAT(S)" et/ou "ILLUSTRATEURS - N RÉSULTAT(S)", "JACK" surligné dans "Steve JACKSON".

**Acceptance Scenarios**:

1. **Given** l'utilisateur tape dans la barre de recherche, **When** au moins 1 caractère est saisi, **Then** un dropdown s'ouvre immédiatement sous le champ avec des résultats.
2. **Given** le dropdown est ouvert, **When** les résultats s'affichent, **Then** ils sont regroupés par rôle (ex: "AUTEURS — 2 RÉSULTATS"), la chaîne recherchée est surlignée dans chaque nom.
3. **Given** le dropdown est ouvert, **When** l'utilisateur clique sur un résultat, **Then** il est redirigé vers la fiche détail du créateur.
4. **Given** la recherche ne retourne aucun résultat, **When** le dropdown s'affiche, **Then** un message "Aucun résultat" est présenté.
5. **Given** chaque résultat dans le dropdown, **When** affiché, **Then** montre : avatar, nom, rôle, nombre d'ouvrages, collection principale, note moyenne.

---

### User Story 4 — Filtres avancés via le panneau latéral (Priority: P3)

L'utilisateur ouvre le panneau de filtres latéral et configure des critères avancés (collections, période d'activité, nationalité, nombre d'ouvrages, statut de suivi). Les choix ne s'appliquent qu'après validation explicite ("Appliquer").

**Why this priority**: Filtrage fin pour les utilisateurs avancés ; la galerie reste utilisable sans ce panneau.

**Independent Test**: Sélectionner une collection → cliquer "Appliquer" → vérifier que seuls les créateurs liés à cette collection apparaissent avec une puce correspondante.

**Acceptance Scenarios**:

1. **Given** l'utilisateur configure des filtres, **When** il n'a pas encore cliqué "Appliquer", **Then** les résultats de la grille ne changent pas (état brouillon).
2. **Given** l'utilisateur clique "Appliquer", **When** les filtres sont validés, **Then** la grille se met à jour et des puces (chips) supprimables apparaissent au-dessus des résultats.
3. **Given** l'utilisateur définit une période d'activité via le slider (ex: 1980–2000), **When** il clique "Appliquer", **Then** seuls les créateurs actifs dans cette période sont affichés.
4. **Given** l'utilisateur active "Uniquement ceux que je suis", **When** il clique "Appliquer", **Then** seuls les créateurs suivis par l'utilisateur connecté sont affichés.
5. **Given** des puces de filtres sont actives, **When** l'utilisateur clique le "×" d'une puce, **Then** ce filtre est supprimé et la liste se met à jour immédiatement.

---

### User Story 5 — Bouton "Suivre" statique (Priority: P3) *(hors périmètre fonctionnel)*

> **OUT OF SCOPE**: La logique "Suivre / Ne plus suivre" (persistance, API, `UserFollowing`) est hors périmètre de ce ticket. Le bouton "Suivre" est rendu statiquement (HTML/CSS uniquement) pour respecter le design de référence.

**Acceptance Scenarios**:

1. **Given** la carte créateur s'affiche, **When** rendue, **Then** un bouton "Suivre" statique est visible (aucun comportement fonctionnel attendu dans ce ticket).

---

### User Story 6 — Basculer entre Vue Grille et Vue Liste (Priority: P4)

L'utilisateur clique sur les boutons d'affichage pour passer de la vue grille (cartes verticales, défaut) à la vue liste (lignes horizontales condensées).

**Why this priority**: Confort d'affichage secondaire ; la vue grille couvre le besoin principal.

**Independent Test**: Cliquer icône liste → vérifier affichage en lignes horizontales condensées.

**Acceptance Scenarios**:

1. **Given** la page est chargée, **When** la vue grille est active (défaut), **Then** les créateurs s'affichent en cartes verticales.
2. **Given** l'utilisateur clique sur l'icône "Vue Liste", **When** l'affichage bascule, **Then** les créateurs s'affichent en lignes horizontales condensées.
3. **Given** l'utilisateur bascule de vue, **When** les filtres actifs sont préservés, **Then** les mêmes créateurs filtrés sont affichés dans la nouvelle vue.

---

### Edge Cases

- Créateur sans aucune contribution (0 ouvrages) : afficher "0 OUVRAGE" et note moyenne absente ou "–".
- Créateur sans nationalité ni dates : afficher uniquement les informations disponibles, ne pas afficher de séparateur "·" vide. Format conditionnel de la ligne biographique : `[nationalité][ · [dates]]` — chaque partie est omise si nulle. Si les deux sont nulles, la ligne entière est omise.
- Description null ou vide : ne pas afficher le bloc description (pas d'espace vide).
- Lettre de l'index sans résultats après changement de filtre : griser la lettre sans rechargement complet de la page.
- Pagination en bout de liste : désactiver "Suivant" sur la dernière page, "Précédent" sur la première.
- Base vide (aucun créateur) : afficher un message d'état vide explicite.
- Échec de l'endpoint `/createurs/search` (réseau, timeout, 500) : dropdown fermé silencieusement, aucun message d'erreur — l'utilisateur continue avec les autres filtres.

---

## Clarifications

### Session 2026-06-09

- Q: Quand plusieurs filtres sont actifs (rôle + alphabétique + panneau), quelle logique s'applique ? → A: AND — tous les filtres actifs doivent correspondre (réduction progressive).
- Q: L'entité `UserFollowing` doit-elle être créée ou adaptée depuis une existante ? → A: Hors périmètre — le bouton "Suivre" est rendu statiquement (HTML/CSS uniquement), sans logique backend ni persistance.
- Q: Quel comportement pour le debounce de la recherche autocomplete ? → A: Debounce 250 ms — requête déclenchée après un délai court pour éviter les appels à chaque frappe.
- Q: Les filtres actifs sont-ils reflétés dans l'URL ? → A: Oui — query params (ex: `?role=auteurs&letter=B&collection=12`) pour permettre partage et retour arrière.
- Q: L'entité qui stocke les évaluations d'ouvrages pour calculer la note moyenne par créateur est-elle existante ou à créer ? → A: Entité existante (`Rating` ou `Review` déjà en base) — réutiliser sans modification.
- Q: La recherche autocomplete est-elle gérée côté backend ou client-side ? → A: Endpoint Symfony dédié (ex: `GET /createurs/search?q=`) retournant JSON. Schéma de réponse par résultat : `slug`, `firstName`, `lastName`, `portraitImage` (nullable), `role`, `bookCount`, `mainCollection` (nullable), `averageScore` (nullable).
- Q: La pagination est-elle ajustable via un sélecteur UI ou une constante PHP ? → A: Constante PHP uniquement — le design montre 12 créateurs/page (ex: "13 à 24 sur 642"), sans sélecteur UI exposé.
- Q: Le tri ("Trier par") visible dans le design — backend ou client-side, et quelles options ? → A: Backend Symfony, paramètre `?sort=` dans l'URL. 4 options : A→Z, Nombre d'ouvrages, Note moyenne, Les plus suivis.
- Q: Comportement autocomplete si l'endpoint `/createurs/search` échoue ? → A: Échec silencieux — dropdown fermé/non ouvert, aucun message d'erreur affiché.
- Q: FR-026 liste "Les plus suivis" comme option de tri mais UserFollowing est hors périmètre — que faire ? → A: Retirer "Les plus suivis" du périmètre — 3 options de tri uniquement (A→Z, Nombre d'ouvrages, Note moyenne) ; option à ajouter lors de l'implémentation de UserFollowing.
- Q: Comment les lettres disponibles de l'index alphabétique sont-elles calculées selon les filtres actifs ? → A: Calculées par un service Symfony dédié, incluses dans le payload de la réponse principale (pas d'endpoint AJAX séparé, pas client-side).
- Q: Tri "A→Z" — ORDER BY sur quel champ ? → A: `lastName` uniquement (ORDER BY lastName ASC).
- Q: Le fichier `design/pages/createurs.html` est-il déjà créé (Assumption disait "à créer") ? → A: Oui, déjà créé et final — référence directe pour l'intégration Twig, aucune création nécessaire dans ce ticket.
- Q: Toggle "Uniquement ceux que je suis" pour visiteur non connecté — caché, grisé, ou redirect ? → A: Caché dans le panneau — le toggle n'est pas rendu pour les utilisateurs non connectés.
- Q: Feedback visuel pendant la mise à jour de la grille (métier, lettre, "Appliquer") ? → A: Skeleton cards — la grille affiche des placeholders animés pendant le rechargement.
- Q: Reset de la pagination lors d'un changement de filtre ou de tri ? → A: Reset systématique à page 1 — tout changement de filtre (métier, lettre, panneau) ou de tri remet `?page=1`.

---

## Requirements *(mandatory)*

### Functional Requirements

**Navigation**
- **FR-001**: Le menu principal DOIT inclure un lien "Créateurs" positionné entre "Catalogue" et "Suggestions".
- **FR-002**: Ce lien DOIT afficher un état actif (souligné) lorsque l'utilisateur se trouve sur la page Créateurs.

**Page & Structure**
- **FR-003**: La page DOIT respecter fidèlement le design défini dans `design/pages/createurs.html` (référence finale déjà disponible).
- **FR-004**: La page DOIT être paginée avec un bloc de navigation (Précédent, numéros de page, Suivant). Le bloc DOIT afficher les pages 1 à 4, puis une ellipse (…), puis la dernière page (ex: `1 2 3 4 … 54`). La page courante est visuellement mise en évidence. "Précédent" est désactivé sur la page 1 ; "Suivant" est désactivé sur la dernière page.
- **FR-025**: Les filtres actifs (rôle, lettre, panneau) DOIVENT être reflétés dans l'URL via query params pour permettre le partage et le retour arrière navigateur. Tout changement de filtre ou de tri DOIT remettre `?page=1` automatiquement. Valeurs acceptées : `?role=tous|auteur|traducteur|illustrateur`, `?letter=A–Z`, `?collection=ID` (entier), `?sort=az|ouvrages|note`, `?page=N`. La bascule de vue (grille/liste) n'est PAS reflétée dans l'URL (persistée en localStorage).
- **FR-028**: Les mises à jour de la grille suite aux filtres et au tri DOIVENT utiliser le pattern LiveComponent + redirect URL (même architecture que la page Catalogue). Les skeleton cards DOIVENT être affichées pendant la navigation Turbo Drive entre pages filtrées. Les skeleton cards reproduisent la forme d'une carte créateur (cercle avatar + 2 lignes de texte + bande footer) via des rectangles arrondis de couleur `var(--bg-sunken)` animés par un pulse CSS (`@keyframes pulse`, opacité 0.5→1→0.5, durée 1.4 s). Le nombre de skeleton cards affiché correspond au nombre de créateurs de la page précédente (ou 12 par défaut).
- **FR-029**: Lorsqu'aucun créateur ne correspond aux filtres actifs, la grille DOIT afficher un état vide avec le message "Aucun créateur ne correspond à vos filtres." et un bouton "Effacer les filtres" qui réinitialise tous les filtres actifs.

**Recherche autocomplete**
- **FR-005**: La saisie d'au moins 1 caractère dans la barre de recherche DOIT ouvrir un dropdown après un debounce de 250 ms. La suppression de tous les caractères DOIT fermer le dropdown. Le dropdown DOIT afficher au maximum 5 résultats par catégorie (rôle).
- **FR-005b**: L'autocomplete DOIT appeler un endpoint Symfony dédié `GET /createurs/search?q=` retournant du JSON — pas de filtrage client-side. En cas de requêtes concurrentes, seule la réponse de la dernière requête envoyée DOIT être appliquée (réponses obsolètes ignorées). La réponse JSON DOIT contenir pour chaque résultat : `slug`, `firstName`, `lastName`, `portraitImage` (nullable), `role` (rôle unique pour ce groupe), `bookCount`, `mainCollection` (nullable), `averageScore` (nullable). Un créateur ayant plusieurs rôles apparaît dans chaque groupe correspondant (ex: un Auteur-Illustrateur apparaît dans "Auteurs" ET dans "Illustrateurs") avec le rôle du groupe concerné dans le champ `role`.
- **FR-006**: Le dropdown DOIT regrouper les résultats par rôle avec le compteur par catégorie.
- **FR-007**: La chaîne recherchée DOIT être surlignée (highlight) dans chaque nom de résultat du dropdown.
- **FR-008**: Chaque résultat du dropdown DOIT afficher : avatar, nom, rôle, nombre d'ouvrages, collection principale, note moyenne.

**Barre de métiers**
- **FR-009**: Les boutons segmentés (Tous, Auteurs, Traducteurs, Illustrateurs) DOIVENT afficher le compteur dynamique de créateurs par catégorie. Ces compteurs reflètent le total global par rôle (tous les contributeurs en base, indépendamment des filtres actifs). Un contributeur avec plusieurs rôles est compté dans chaque groupe applicable. Les compteurs sont calculés via une requête dédiée `findRoleCounts()` et passés séparément au template (ne pas confondre avec `totalItems` du résultat paginé).
- **FR-010**: La sélection d'un métier DOIT déclencher une mise à jour de la grille avec affichage de skeleton cards pendant le chargement, puis désactiver les lettres orphelines de l'index alphabétique à réception de la réponse.

**Index alphabétique**
- **FR-011**: La barre A–Z DOIT griser et désactiver les lettres sans créateur correspondant selon les filtres actifs courants. Les lettres disponibles sont calculées par un service Symfony dédié et incluses dans le payload de la réponse principale (pas d'endpoint AJAX séparé, pas de dérivation client-side).
- **FR-012**: Aucune lettre ne DOIT être sélectionnée par défaut au chargement de la page.

**Panneau de filtres latéral**
- **FR-013**: Les filtres du panneau latéral NE DOIVENT PAS s'appliquer avant le clic explicite sur "Appliquer" (état brouillon). Aucun indicateur visuel de brouillon spécifique n'est requis ; le compteur dynamique dans le bouton "Appliquer · N" communique l'impact des filtres en attente.
- **FR-014**: Les filtres disponibles DOIVENT inclure : Collections (cases à cocher avec champ de recherche texte filtrant la liste de collections par nom — LiveProp `collectionSearch`), Période d'activité (slider double + raccourcis décennies ; se base sur `birthDate`/`deathDate` du Contributor), Nationalité (champ de recherche texte filtrant les nationalités par pays — LiveProp `nationalitySearch` — + cases à cocher), Nombre d'ouvrages (boutons de tranches prédéfinies : "1 — 5", "6 — 15", "16 — 30", "30 +"), "Uniquement ceux que je suis" (toggle visible uniquement pour les utilisateurs connectés — caché pour les visiteurs non authentifiés).
- **FR-015**: Chaque filtre appliqué (panneau, alphabétique, métier) DOIT générer une puce (chip) supprimable au-dessus des résultats. Format des puces : `[TYPE] [valeur]` où TYPE est un label court en majuscules (Métier, Collection, Période, Lettre, Nationalité, Ouvrages). Exemples : `Métier Auteurs`, `Collection Défis Fantastiques`, `Période Années 80`, `Lettre B`.
- **FR-016**: La suppression d'une puce DOIT retirer le filtre correspondant et mettre à jour la liste immédiatement.
- **FR-024**: La combinaison de filtres (rôle, alphabétique, panneau) DOIT utiliser une logique ET (AND) — un créateur n'apparaît que s'il satisfait TOUS les filtres actifs simultanément.

**Cartes créateur**
- **FR-017**: La carte DOIT afficher un avatar avec initiales si aucune photo n'est disponible, accompagné d'un indicateur du rôle principal (icône).
- **FR-018**: La description (`biography`) DOIT être tronquée à 3 lignes maximum via CSS `line-clamp: 3` avec "..." si elle dépasse. Si `biography` est null ou vide, le bloc description ne DOIT PAS être rendu (aucun espace réservé).
- **FR-019**: La carte DOIT afficher les badges de rôles dynamiquement (un badge par rôle détenu par le créateur).
- **FR-020**: La carte DOIT afficher jusqu'à 2 collections principales associées au créateur. La "collection principale" est déterminée par le nombre d'ouvrages auxquels le créateur a contribué dans chaque collection (top 2 par ordre décroissant). En cas d'égalité du nombre d'ouvrages, la collection la plus récente est retenue (UUID v7 chronologique — la collection avec le plus grand UUID gagne). Si le créateur n'appartient qu'à une seule collection, une seule puce est affichée.
- **FR-021**: Les statistiques (nombre d'ouvrages, note moyenne) DOIVENT être calculées dynamiquement depuis les contributions et évaluations associées. La note moyenne est la moyenne arithmétique simple (AVG) des scores de l'entité `Review` de tous les ouvrages auxquels le créateur a contribué. Les compteurs respectent la règle de pluralisation : 0 ou 1 = "OUVRAGE" (singulier), 2+ = "OUVRAGES" (pluriel).
- **FR-022**: *(hors périmètre)* Le bouton "Suivre" est rendu statiquement (HTML/CSS) — aucun comportement fonctionnel dans ce ticket.

**Tri des résultats**
- **FR-026**: Un sélecteur "Trier par" DOIT être présent dans la barre des résultats avec 3 options : A→Z (défaut), Nombre d'ouvrages, Note moyenne. Le tri A→Z utilise ORDER BY `lastName` ASC. *(L'option "Les plus suivis" est exclue de ce ticket — dépend de UserFollowing, hors périmètre.)*
- **FR-027**: Le tri DOIT être effectué côté backend Symfony via le paramètre `?sort=az|ouvrages|note` dans l'URL. Valeur par défaut (absence du paramètre) : `az` (ORDER BY lastName ASC).

**Bascule de vue**
- **FR-023**: Deux boutons DOIVENT permettre de basculer entre Vue Grille (défaut) et Vue Liste sans recharger la page.

### Key Entities

- **Contributor**: Représente un créateur (auteur, illustrateur, traducteur). Attributs clés : id, firstName, lastName, pseudo, slug, biography (→ description tronquée), nationality, birthDate, deathDate, portraitImage. Relations : contributions (vers Contribution[]).
- **Contribution**: Lien entre un Contributor et un Book avec un rôle (Author, Illustrator, Traductor). Utilisé pour dériver les rôles du créateur et le nombre d'ouvrages.
- **ContributionRole** (enum): `Author`, `Illustrator`, `Traductor` — pilote les filtres de métier et badges.
- **Collection**: Entité existante. Associée aux créateurs via leurs ouvrages. Utilisée pour les filtres et les tags de collections sur les cartes.
- **Review**: Entité existante (`App\Entity\Review`) stockant les évaluations par ouvrage (champ `score`, entier 1–10). La note moyenne d'un créateur est calculée via `AVG(score)` sur les Review de tous ses ouvrages — ne pas créer de nouveau schema dans ce ticket.
- **UserFollowing**: *(hors périmètre)* — bouton "Suivre" rendu statiquement, aucune entité à créer dans ce ticket.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un utilisateur trouve un créateur spécifique en moins de 30 secondes en utilisant la recherche autocomplete ou les filtres.
- **SC-002**: La galerie affiche correctement 100% des créateurs existants en base, sans créateur manquant ni doublon.
- **SC-003**: Les filtres (métier, alphabétique, panneau) combinés retournent des résultats cohérents et sans contradiction dans 100% des cas testés.
- **SC-004**: *(hors périmètre)* — bouton "Suivre" statique, pas de critère de persistance dans ce ticket.
- **SC-005**: Le design visuel de la page correspond à `design/pages/createurs.html` à 100% (pixel-perfect par rapport à la maquette de référence), y compris les skeleton cards de chargement. *Validation : revue visuelle manuelle par le designer — pas de test automatisé de régression visuelle dans ce ticket.*
- **SC-006**: La description tronquée n'excède jamais 3 lignes visible, quel que soit le contenu de la `biography`.

---

## Assumptions

- Le fichier de design `design/pages/createurs.html` est déjà créé et constitue la référence finale pour l'intégration Twig. Aucune création supplémentaire nécessaire dans ce ticket.
- Les rôles existants sont exactement : `Author`, `Illustrator`, `Traductor` (enum `ContributionRole`). Aucun nouveau rôle n'est créé dans ce ticket.
- La note moyenne d'un créateur est calculée comme la moyenne des évaluations de tous les ouvrages auxquels il a contribué.
- La "collection principale" d'un créateur est la collection à laquelle appartient le plus grand nombre de ses ouvrages.
- *(Sprint futur)* La fonctionnalité "Suivre" nécessitera que l'utilisateur soit connecté ; un utilisateur non connecté sera redirigé vers la page de connexion. Dans ce ticket, le bouton est uniquement rendu statiquement (HTML/CSS, FR-022).
- La pagination affiche un nombre fixe de créateurs par page (valeur par défaut : 12, constante PHP — le design montre "13 à 24 sur 642" soit 12/page). Pas de sélecteur UI de pagination.
- La vue Liste condense les informations (avatar, nom, rôles, compteur d'ouvrages, note) sans la description ni les tags de collections.
- Le filtre "Période d'activité" se base sur `birthDate` / `deathDate` du Contributor en l'absence d'un champ dédié "années d'activité".
- Les raccourcis décennies du slider (ex: "Années 80") correspondent à 1980–1989.
- L'état de la vue sélectionnée (grille/liste) EST persisté dans `localStorage` (`lca-createurs-view`) entre sessions — comportement défini par le design de référence. Si aucune valeur stockée, la vue grille est utilisée par défaut.
