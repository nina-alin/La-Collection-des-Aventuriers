# Feature Specification: Refonte Page Collection

**Feature Branch**: `014-collection-page-redesign`

**Created**: 2026-06-01

**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Consulter la page collection avec le nouveau design Hero (Priority: P1)

Un visiteur accède à `/collections/{slug}` et voit immédiatement l'identité visuelle forte de la collection : le grand macaron circulaire (logo), le titre français et le titre original (V.O.), ainsi que les quatre méta-données clés (tomes recensés, période de publication, note moyenne et statut éditorial). Deux actions sont visibles : "Ajouter aux favoris" (inactif visuellement, état non-favori) et "+ Suggérer un tome manquant".

**Why this priority**: C'est la section de premier impact visuel. Elle consolide les informations existantes dans une mise en forme fidèle au design de référence. Aucune dépendance externe nouvelle.

**Independent Test**: Naviguer vers `/collections/loup-solitaire` → le macaron est affiché en haut à gauche, le titre "Loup Solitaire" en grand, "Lone Wolf" (V.O.) dessous, les quatre pastilles méta sont peuplées avec les données réelles, les deux boutons d'action sont présents et non-fonctionnels.

**Acceptance Scenarios**:

1. **Given** une collection avec `imageLogo` renseigné, **When** l'utilisateur accède à la page, **Then** le macaron circulaire affiche le logo de la collection.
2. **Given** une collection sans `imageLogo`, **When** l'utilisateur accède à la page, **Then** le macaron affiche le placeholder standard.
3. **Given** une collection avec `nomOriginal`, **When** la page est rendue, **Then** le nom original est affiché sous le titre principal avec un libellé "V.O.".
4. **Given** n'importe quelle collection, **When** la page est rendue, **Then** les méta-données affichent : nombre total de tomes liés, plage d'années de publication, note moyenne calculée depuis les avis, statut éditorial.
5. **Given** n'importe quel utilisateur (connecté ou non), **When** la page est rendue, **Then** le bouton "Ajouter aux favoris" est visible avec l'état "non-favori" et ne déclenche aucune action.
6. **Given** n'importe quel utilisateur, **When** la page est rendue, **Then** le bouton "+ Suggérer un tome manquant" est visible mais ne déclenche aucune navigation (lien `#`).

---

### User Story 2 - Parcourir la grille des tomes avec le design actualisé (Priority: P1)

L'utilisateur voit la grille complète des tomes sous l'intitulé "LES TOMES — N VOLUMES". Chaque carte présente un fond coloré propre au tome, un filigrane diagonal, le titre en bas de la couverture et un indicateur de possession statique. Les méta-données sous la carte (identifiant, année, paragraphes, titre, note, auteurs) sont peuplées depuis les données réelles. La barre de filtres est présente ; les filtres "Possédés" et "Manquants" sont visuellement grisés (non-opérationnels).

**Why this priority**: La grille est le contenu central de la page. Son redesign est l'objectif principal de ce ticket.

**Independent Test**: Sur la page d'une collection, la grille affiche toutes les cartes selon le nouveau design. Chaque carte porte les données correctes du livre. Les filtres "Toutes", "Tri par Numéro" et "Tri par Note" fonctionnent côté client. "Possédés" et "Manquants" sont grisés sans erreur.

**Acceptance Scenarios**:

1. **Given** une collection avec des tomes, **When** la grille s'affiche, **Then** l'en-tête affiche "LES TOMES" et le nombre exact de tomes au format "N VOLUMES".
2. **Given** une carte de livre, **When** elle est rendue, **Then** la couleur de fond est dérivée du numéro du tome (algorithme déterministe basé sur `volumeNumber`).
3. **Given** une carte de livre, **When** elle est rendue, **Then** un filigrane diagonal est visible sur le fond de la couverture.
4. **Given** une carte de livre, **When** elle est rendue, **Then** le titre du livre est positionné en bas de la zone de couverture.
5. **Given** une carte de livre, **When** elle est rendue, **Then** un indicateur de possession statique est visible (point d'interrogation gris par défaut).
6. **Given** une carte de livre, **When** elle est rendue, **Then** les méta-données sous la carte affichent : identifiant numérique (volumeNumber), année de publication française, nombre de paragraphes, titre, note moyenne, et la liste des auteurs (contributeurs avec rôle auteur).
7. **Given** la barre de filtres, **When** l'utilisateur clique "Tri par Numéro", **Then** la grille se réorganise par `volumeNumber` croissant côté client.
8. **Given** la barre de filtres, **When** l'utilisateur clique "Tri par Note", **Then** la grille se réorganise par note moyenne décroissante côté client.
9. **Given** la barre de filtres, **When** l'utilisateur clique "Possédés" ou "Manquants", **Then** ces filtres restent visuellement grisés et ne modifient pas l'affichage.

---

### User Story 3 - Voir l'encart Complétion statique (Priority: P2)

L'utilisateur voit l'encart beige "Complétion" avec les valeurs d'exemple figées : 42,8 %, "12 / 28 tomes possédés", la barre de progression segmentée (12 blocs colorés + 16 blocs vides), et les deux textes : "Il vous manque 16 tomes pour boucler la saga [nom de la collection]." et "Mis à jour il y a 2 jours".

**Why this priority**: Composant purement visuel / statique, valeur d'UX pour la maquette. Aucun risque technique.

**Independent Test**: L'encart est visible sur toute page collection, affiche exactement les valeurs citées, et la barre de progression compte 28 segments (12 colorés + 16 vides). Le texte d'invite utilise le nom réel de la collection.

**Acceptance Scenarios**:

1. **Given** n'importe quelle page collection, **When** l'encart Complétion s'affiche, **Then** le pourcentage "42,8 %" est visible.
2. **Given** l'encart Complétion, **When** rendu, **Then** la barre de progression est segmentée en 28 blocs, dont 12 colorés et 16 vides.
3. **Given** l'encart Complétion, **When** rendu, **Then** le texte "Il vous manque 16 tomes pour boucler la saga [nom de la collection]." est affiché avec le nom réel de la collection.
4. **Given** l'encart Complétion, **When** rendu, **Then** le texte "Mis à jour il y a 2 jours" est affiché.

---

### User Story 4 - Consulter l'historique des éditeurs (Priority: P2)

Si une collection a été publiée par plusieurs éditeurs successifs (count > 1), l'utilisateur voit la section "ÉDITEURS SUCCESSIFS" sous forme de timeline verticale : période à gauche, badge éditeur au centre, détails en dessous. Si la collection n'a qu'un seul éditeur dans son historique, la section est masquée.

**Why this priority**: Nouvelle donnée backend requise, mais affichage conditionnel simple. La section est absente pour la majorité des collections actuelles (sans historique renseigné).

**Independent Test**: Créer une collection avec 2 entrées d'historique éditorial → la section "ÉDITEURS SUCCESSIFS" apparaît avec une timeline à 2 nœuds. Créer une collection avec 1 entrée → la section n'apparaît pas.

**Acceptance Scenarios**:

1. **Given** une collection sans entrée d'historique éditorial, **When** la page est rendue, **Then** la section "ÉDITEURS SUCCESSIFS" n'est pas présente dans le DOM.
2. **Given** une collection avec exactement 1 entrée d'historique éditorial, **When** la page est rendue, **Then** la section "ÉDITEURS SUCCESSIFS" n'est pas présente dans le DOM.
3. **Given** une collection avec 2 entrées ou plus, **When** la page est rendue, **Then** la section "ÉDITEURS SUCCESSIFS" est présente avec une timeline affichant autant de nœuds qu'il y a d'entrées.
4. **Given** un nœud de timeline, **When** rendu, **Then** il affiche : la période (année début – année fin ou "présent"), le badge avec le nom de l'éditeur, et les détails/notes de l'édition.
5. **Given** une entrée sans année de fin, **When** rendue en timeline, **Then** la période affiche "XXXX – présent".
6. **Given** une entrée avec nom d'édition renseigné, **When** rendue, **Then** le nom de l'édition est affiché dans ou sous le badge éditeur.
7. **Given** une entrée dont l'éditeur référencé a été supprimé, **When** rendue, **Then** le badge affiche "(éditeur inconnu)".

---

### User Story 5 - Découvrir les contributeurs récurrents de la collection (Priority: P3)

L'utilisateur voit la section "AUTEURS & ILLUSTRATEURS RÉCURRENTS" avec le compteur total de contributeurs uniques et les pilules triées par nombre d'occurrences décroissant. Chaque pilule affiche initiales/avatar, nom complet, rôle (en majuscules) et un badge numérique indiquant le nombre de tomes concernés.

**Why this priority**: Valeur d'information élevée, mais aucune donnée nouvelle à créer en base. Calcul à la volée depuis les données déjà chargées.

**Independent Test**: Sur la page Loup Solitaire, la section affiche "7 CONTRIBUTEURS" (valeur calculée), la pilule "Joe Dever" porte le badge "28" et le rôle "AUTEUR PRINCIPAL". Les contributeurs sont triés du plus au moins fréquent.

**Acceptance Scenarios**:

1. **Given** les tomes d'une collection chargés avec leurs contributions, **When** la section s'affiche, **Then** le compteur "N CONTRIBUTEURS" reflète le nombre de contributeurs uniques sur l'ensemble des tomes.
2. **Given** la liste des pilules, **When** rendue, **Then** les pilules sont triées par nombre d'occurrences décroissant (plus grand badge d'abord).
3. **Given** une pilule de contributeur, **When** rendue, **Then** elle affiche : initiales (1re lettre du prénom + 1re lettre du nom de famille, ex. "JD" pour "Joe Dever"), nom complet, rôle principal (libellé du `ContributionRole` en majuscules), et le nombre de tomes où le contributeur intervient dans ce rôle.
4. **Given** un contributeur avec plusieurs rôles différents, **When** ses occurrences sont comptées, **Then** chaque rôle est compté séparément (une pilule par rôle).
5. **Given** une collection sans aucune contribution enregistrée, **When** la section s'affiche, **Then** elle affiche "0 CONTRIBUTEURS" et la liste est vide.

---

### Edge Cases

- Que se passe-t-il si `averageRating` est nul (aucun avis) ? → La méta-donnée "Note moyenne" affiche "–" (dans le hero et dans chaque carte de la grille).
- Que se passe-t-il si `frenchPublicationYear` est absent sur tous les tomes ? → La méta-donnée "Période" affiche "–".
- Que se passe-t-il si `frenchPublicationYear` est absent sur CERTAINS tomes (mais pas tous) ? → La plage affiche min–max des années connues ; les tomes sans année sont ignorés dans le calcul.
- Que se passe-t-il si un tome n'a pas de `volumeNumber` ? → La couleur de fond utilise `0` comme fallback dans l'algorithme de couleur déterministe.
- Que se passe-t-il si une entrée d'historique éditorial référence un éditeur supprimé ? → L'entrée affiche "(éditeur inconnu)" à la place du badge.
- Que se passe-t-il avec la pagination lorsque la page collection affiche la grille ? → La grille chargée sur la page correspond aux tomes de la page courante (pagination existante conservée).
- Que se passe-t-il si une collection n'a aucun tome ? → La grille affiche l'en-tête "LES TOMES — 0 VOLUMES" avec une zone de grille vide.
- Que se passe-t-il si deux entrées d'historique éditorial ont la même `startYear` ? → Affichage dans l'ordre croissant de `id` (ordre d'insertion).
- Que se passe-t-il si deux entrées d'historique éditorial ont des périodes qui se chevauchent ? → Les entrées sont affichées telles quelles, triées par `startYear` ; aucun traitement spécial.
- Que se passe-t-il si un contributeur n'a qu'un seul nom (sans nom de famille) ? → Les initiales utilisent les 2 premières lettres du nom unique (ex. "Jo" pour "Joe").
- Que se passe-t-il si deux tomes ont la même note ou le même `volumeNumber` lors d'un tri client ? → L'ordre relatif des cartes est préservé (tri stable).

## Requirements *(mandatory)*

### Functional Requirements

**Hero Section**

- **FR-001**: La page DOIT afficher le logo de collection dans un macaron circulaire, avec fallback vers le placeholder si absent. Les dimensions visuelles et le design du macaron sont définis par `design/pages/collection.html`.
- **FR-002**: La page DOIT afficher les méta-données suivantes dans le hero : nombre total de tomes liés, plage d'années de publication française (min–max des tomes ayant une année connue ; "–" si aucun tome n'a d'année), note moyenne calculée depuis les avis des tomes comme **moyenne des notes moyennes de chaque tome** (biais intentionnel, suffisant pour UX ; afficher "–" si aucun avis), valeur du statut éditorial.
- **FR-003**: La page DOIT afficher un bouton "Ajouter aux favoris" avec l'état visuel "non-favori" ; aucune action backend ne DOIT être déclenchée au clic (ni au clavier, ni au toucher).
- **FR-004**: La page DOIT afficher un bouton "+ Suggérer un tome manquant" ; toute interaction (clic, clavier, toucher) NE DOIT pas déclencher de navigation (cible `#`).

**Completion Section**

- **FR-005**: La page DOIT afficher l'encart "Complétion" avec les valeurs statiques codées en dur : 42,8 %, 12/28, barre 28 segments (12 colorés), texte "Il vous manque 16 tomes pour boucler la saga {{ collection.name }}.", texte "Mis à jour il y a 2 jours". Les valeurs numériques sont statiques ; seul le nom de la collection est dynamique via Twig.
  > **TODO — dette technique** : cet encart sera rendu dynamique dans un ticket ultérieur (possession réelle de l'utilisateur connecté). Lors de ce ticket, les valeurs chiffrées sont volontairement figées.

**Books Grid**

- **FR-006**: L'en-tête de section DOIT afficher "LES TOMES" et le count réel au format "N VOLUMES", y compris "0 VOLUMES" si la collection n'a aucun tome (grille vide affichée).
- **FR-007**: Chaque carte livre DOIT utiliser une couleur de fond déterministe basée sur le `volumeNumber` du tome, selon les valeurs CSS définies dans `design/pages/collection.html` (source de vérité). Si `volumeNumber` est absent, utiliser `0` comme valeur de fallback dans l'algorithme.
- **FR-008**: Chaque carte livre DOIT afficher un filigrane diagonal sur la zone de couverture. Les propriétés visuelles (opacité, angle, contenu) sont définies par `design/pages/collection.html`.
- **FR-009**: Chaque carte livre DOIT afficher un indicateur de possession statique (état "inconnu", point d'interrogation gris).
- **FR-010**: Les métadonnées sous chaque carte DOIT inclure : `volumeNumber`, `frenchPublicationYear`, `paragraphs`, `title`, note moyenne du livre (afficher "–" si aucun avis), noms des auteurs (rôle AUTEUR).
- **FR-011**: Le filtre "Tous" DOIT être actif par défaut au chargement initial de la page et afficher tous les tomes de la page courante **triés par `volumeNumber` croissant** (ordre serveur).
- **FR-012**: Les tris "Tri par Numéro" et "Tri par Note" DOIVENT réorganiser la grille côté client sans rechargement. En cas d'égalité sur le critère de tri, l'ordre relatif des cartes est préservé (tri stable).
- **FR-013**: Les filtres "Possédés" et "Manquants" DOIVENT être visuellement grisés (disabled) et ne DOIVENT pas modifier l'affichage.

**Publishing History Section (new backend)**

- **FR-014**: Une nouvelle entité `CollectionPublishingHistory` DOIT exister avec les champs : `id` (UUID), `collection` (FK → Collection, obligatoire), `editor` (FK → Editor existant, obligatoire), `startYear` (smallint, obligatoire), `endYear` (smallint, nullable), `editionName` (varchar 255, nullable), `details` (text, nullable).
- **FR-015**: La section "ÉDITEURS SUCCESSIFS" NE DOIT s'afficher que si la collection possède strictement plus d'un enregistrement actif dans cet historique.
- **FR-016**: La timeline DOIT afficher les entrées triées par `startYear` croissant. En cas d'égalité sur `startYear`, trier par `id` croissant (ordre d'insertion).
- **FR-017**: Chaque nœud de timeline DOIT afficher : période ("XXXX – YYYY" ou "XXXX – présent" si `endYear` est null), badge éditeur (nom de l'éditeur, ou "(éditeur inconnu)" si l'éditeur référencé a été supprimé), nom de l'édition si renseigné, notes/détails si renseignés. Les périodes se chevauchant sont affichées telles quelles sans traitement spécial.

**Recurring Contributors Section**

- **FR-018**: La section DOIT calculer, via une **requête dédiée SQL/DQL** portant sur l'intégralité des tomes de la collection (toutes pages confondues), le nombre de personnes uniques contributrices et leur fréquence par rôle.
- **FR-019**: Le compteur de titrage DOIT afficher "N CONTRIBUTEURS" (personnes uniques sur l'intégralité de la collection, toutes pages confondues ; un contributeur avec plusieurs rôles compte pour 1 dans ce compteur). Afficher "0 CONTRIBUTEURS" si aucune contribution n'est enregistrée.
- **FR-020**: Chaque pilule DOIT afficher : initiales (1re lettre du prénom + 1re lettre du nom de famille, ex. "JD" pour "Joe Dever" ; si un seul nom disponible, utiliser ses 2 premières lettres, ex. "Jo" pour "Joe"), nom complet, libellé du rôle en majuscules, badge numérique (nombre de tomes dans ce rôle). La liste des pilules est non bornée (toutes les pilules sont affichées).
- **FR-021**: Les pilules DOIVENT être triées par nombre d'occurrences décroissant. En cas d'égalité, l'ordre est indéfini (tri stable acceptable).

### Non-Functional Requirements

- **NFR-001 — Responsive** : Toutes les sections de la page (Hero, Complétion, Grille, Éditeurs, Contributeurs) DOIVENT être utilisables sur mobile. Les breakpoints et mises en page responsive sont définis par `design/pages/collection.html`.
- **NFR-002 — Accessibilité** : Tous les éléments interactifs (boutons hero, barre de filtres, pilules contributeurs) DOIVENT avoir des attributs ARIA appropriés (label, role, state). La navigation clavier DOIT être fonctionnelle sur l'ensemble de la page. Le contraste et la lisibilité DOIVENT respecter WCAG 2.1 AA.
- **NFR-003 — Technologie JS** : Les tris et filtres côté client SONT implémentés via **Symfony UX (Turbo/Stimulus)** — contrainte obligatoire. Un Stimulus controller dédié est créé si une logique JS spécifique est requise.

### Key Entities

- **CollectionPublishingHistory** (nouvelle) : lie une `Collection` à un `Editor` ; champs : `id` (UUID), `collection` (FK → Collection, CASCADE), `editor` (FK → Editor, SET NULL), `startYear` (smallint, obligatoire), `endYear` (smallint, nullable), `editionName` (varchar 255, nullable), `details` (text, nullable).
- **Collection** (existante) : inchangée structurellement ; méta "note moyenne" calculée depuis `Book.reviews`.
- **Contributor** / **Contribution** (existantes) : exploitées côté template pour le calcul des récurrents.
- **Editor** (existante) : `src/Entity/Editor.php` — aucune modification requise dans ce ticket.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: La page collection se charge en moins de 2 secondes pour une collection de 30 tomes (inclut la requête d'agrégation des contributeurs et le rendu Twig complet).
- **SC-002**: Le design de la page est visuellement conforme au fichier `design/pages/collection.html` à 100% des sections définies (Hero, Complétion, Grille, Éditeurs, Contributeurs). Validation : revue manuelle par l'auteur du ticket, comparaison section par section avec le fichier design.
- **SC-003**: Tous les filtres et tris client (Toutes, Tri Numéro, Tri Note) fonctionnent sans rechargement et sans erreur console (Chrome, Firefox, Safari, Edge — deux dernières versions stables).
- **SC-004**: La section "Éditeurs Successifs" est absente pour 100% des collections avec ≤ 1 éditeur dans l'historique, et présente pour 100% des collections avec ≥ 2 éditeurs.
- **SC-005**: Le calcul des contributeurs récurrents produit des résultats triés correctement (premier contributeur = plus grand badge) sur 100% des collections testées.

## Clarifications

### Session 2026-06-01

- Q: Quelle est la portée du calcul des contributeurs récurrents — page courante ou intégralité de la collection ? → A: Intégralité de la collection (requête dédiée, toutes pages confondues)
- Q: Quelle technologie JS pour les tris/filtres côté client ? → A: Symfony UX ; Stimulus si nécessaire
- Q: Source de vérité pour les couleurs de fond des cartes — formule HSL ou fichier design ? → A: Fichier design (`design/pages/collection.html`) est autoritatif
- Q: La note moyenne collection est-elle une moyenne de moyennes (intentionnel malgré le biais) ? → A: Oui, moyenne de moyennes — suffisant pour UX
- Q: Accès à la page `/collections/{slug}` — public ou authentifié ? → A: Public, aucune auth requise
- Q: "N CONTRIBUTEURS" — comptage par personnes uniques ou par paires (personne, rôle) ? → A: Personnes uniques ; un contributeur avec plusieurs rôles compte pour 1 dans le compteur
- Q: Tri actif — réinitialisé ou persisté lors de la pagination ? → A: Réinitialisé à "Toutes" à chaque changement de page
- Q: Format des initiales dans les pilules contributeurs — "JD" ou "Jo" ? → A: "JD" — 1re lettre du prénom + 1re lettre du nom de famille
- Q: `CollectionPublishingHistory` — population des données initiales : fixtures ou migration de seed ? → A: Fixtures uniquement (dev/test) ; aucune seed de prod dans ce ticket
- Q: Format d'affichage quand `averageRating` est nul — "–" ou "N/A" ? → A: "–" dans tous les contextes (hero et cartes de la grille)
- Q: Plage d'années hero quand `frenchPublicationYear` absent sur CERTAINS tomes — min/max des connus ou "–" ? → A: min–max des années connues ; tomes sans année ignorés dans le calcul
- Q: Filtre actif par défaut au chargement initial — "Toutes" ou autre ? → A: "Toutes" actif par défaut

### Session 2026-06-01 (checklist pre-planning review)

- Q: Le texte hardcodé "saga Kaï" dans l'encart Complétion s'affiche sur toutes les collections — acceptable ? → A: Non ; utiliser `{{ collection.name }}` pour le nom de la collection ; les valeurs chiffrées restent statiques
- Q: Ordre d'affichage par défaut dans l'état "Toutes" (aucun tri actif) ? → A: `volumeNumber` ASC (ordre serveur)
- Q: Stratégie d'implémentation pour la requête contributeurs (toutes pages) — eager loading ou requête dédiée ? → A: Requête dédiée SQL/DQL
- Q: Layout responsive / mobile en scope pour ce ticket ? → A: Oui, toutes les sections doivent être responsive
- Q: Initiales pour contributeur avec un seul nom (sans nom de famille) ? → A: 2 premières lettres du nom unique (ex. "Jo" pour "Joe")
- Q: Nombre maximum de pilules contributeurs affichées ? → A: Non borné — toutes les pilules sont affichées
- Q: SC-001 < 2s inclut-il la requête contributeurs ? → A: Oui, budget couvre le chargement complet de la page
- Q: Grille pour collection sans aucun tome ? → A: Afficher "LES TOMES — 0 VOLUMES" avec grille vide
- Q: Tri stable pour égalités dans les tris client ? → A: Oui, tri stable (ordre relatif préservé)
- Q: Encart Complétion — statique permanent ou dette technique ? → A: Dette technique ; sera dynamique dans un ticket ultérieur
- Q: Accessibilité en scope ? → A: Oui, accessibilité complète (ARIA + navigation clavier + WCAG 2.1 AA)
- Q: Périodes d'éditeurs qui se chevauchent ? → A: Possible ; afficher telles quelles, sans traitement spécial

## Assumptions

- La pagination des tomes est conservée telle quelle (20 tomes par page) ; les tris client opèrent sur la page courante uniquement et se réinitialisent à "Toutes" à chaque changement de page. **Risque** : si la taille de page change, le calcul des contributeurs (qui porte sur toutes les pages) n'est pas affecté — il utilise une requête dédiée indépendante de la pagination.
- Le calcul des "contributeurs récurrents" porte sur **l'intégralité des tomes de la collection** (toutes pages confondues), via une **requête dédiée SQL/DQL** (stratégie décidée). Cela garantit un décompte cohérent quel que soit la page courante affichée.
- La "note moyenne" de la collection dans le hero est calculée côté serveur comme moyenne des notes moyennes de chaque tome (moyenne de moyennes — biais intentionnel).
- Les couleurs de fond des cartes sont définies par le fichier design de référence (`design/pages/collection.html`) — source de vérité. La formule `hsl((volumeNumber * goldenAngle) % 360, 60%, 40%)` n'est qu'une approximation indicative ; les classes/valeurs CSS exactes du fichier design priment.
- Les tris et filtres côté client sont implémentés via **Symfony UX** (Turbo/Stimulus) — contrainte obligatoire (NFR-003).
- La page `/collections/{slug}` est **publique** — aucune authentification requise pour y accéder.
- La gestion des favoris et du suivi de possession utilisateur est hors-scope de ce ticket.
- Le design de référence strict est `design/pages/collection.html` ; toute ambiguïté visuelle (dimensions, couleurs, espacements, responsive breakpoints) se résout par lecture de ce fichier.
- L'entité `CollectionPublishingHistory` n'a pas d'interface d'administration dans ce ticket ; les données sont insérées via **fixtures uniquement** (dev/test). Aucune seed de production dans ce ticket.
- L'entité `Editor` (`src/Entity/Editor.php`) existe déjà — aucune modification requise dans ce ticket.
- L'encart Complétion est intentionnellement statique (valeurs chiffrées figées) ; seul `{{ collection.name }}` est dynamique. La dynamisation complète est une dette technique pour un ticket ultérieur.
- La navigation clavier sur JS-désactivé est hors-scope. La page requiert JavaScript pour les tris/filtres client.
