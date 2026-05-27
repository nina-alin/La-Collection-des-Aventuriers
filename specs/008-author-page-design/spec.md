# Feature Specification: Intégration du Design de la Page Auteur

**Feature Branch**: `008-author-page-design`

**Created**: 2026-05-26

**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Consulter le profil d'un auteur (Priority: P1)

Un visiteur (connecté ou non) accède à la page `/authors/{slug}` d'un auteur. Il voit le portrait encadré (ou un placeholder stylisé), la plaque dorée avec le nom, le tableau des attributs (prénom, nom, pseudonyme optionnel, nationalité), le bloc dates de vie (naissance / décès avec calcul d'âge), et la biographie avec lettrine.

**Why this priority**: C'est le cœur de la page — l'identité visuelle de l'auteur. Sans cela, la page n'a pas de valeur.

**Independent Test**: Naviguer vers `/authors/joe-dever` et vérifier que les données de la base (firstName, lastName, pseudo, nationality, birthDate, deathDate, biography, portraitImage) sont toutes affichées dans la mise en page à deux colonnes de la maquette.

**Acceptance Scenarios**:

1. **Given** un auteur avec une photo de portrait, **When** on visite sa page, **Then** l'image s'affiche dans le cadre stylisé avec les ornements de coin.
2. **Given** un auteur sans photo de portrait, **When** on visite sa page, **Then** un placeholder graphique (silhouette) s'affiche à la place.
3. **Given** un auteur avec un pseudonyme, **When** on affiche la liste des attributs, **Then** la ligne "Pseudonyme" est visible avec la valeur.
4. **Given** un auteur sans pseudonyme (pseudo = null), **When** on affiche la liste des attributs, **Then** la ligne "Pseudonyme" est absente.
5. **Given** un auteur décédé (deathDate renseigné), **When** on visite sa page, **Then** l'année de décès et l'âge au décès calculé s'affichent dans le bloc dates.
6. **Given** un auteur encore vivant (deathDate = null), **When** on visite sa page, **Then** l'année de naissance et l'âge actuel calculé s'affichent, sans bloc décès.
7. **Given** un auteur avec biographie, **When** on affiche la page, **Then** la première lettre de la biographie est rendue en lettrine (agrandie, stylisée via CSS), et le reste du texte suit normalement.
8. **Given** un auteur sans biographie, **When** on visite sa page, **Then** le bloc biographie affiche le texte "Biographie non disponible." à la place du parchemin.
9. **Given** un auteur sans date de naissance (`birthDate = null`), **When** on visite sa page, **Then** le bloc dates de vie n'est pas affiché.

---

### User Story 2 — Parcourir la bibliographie d'un auteur (Priority: P2)

Un visiteur consulte la colonne droite "SA BIBLIOGRAPHIE". Il voit le titre de section avec le compteur total de fiches, les boutons de filtre par saga, les menus de tri/vue, et la grille de cartes de livres. Chaque carte affiche la couleur de fond (par saga), le numéro de référence et d'édition, le titre, l'éditeur/collection, et le statut collection de l'utilisateur connecté.

**Why this priority**: La bibliographie est la valeur principale de la page pour un collecteur — voir d'un coup d'œil toutes les œuvres d'un auteur et leur statut dans sa propre collection.

**Independent Test**: Pour un auteur ayant des œuvres dans plusieurs sagas, vérifier que les pills de filtrage sont générées dynamiquement avec les bons compteurs, et que les cartes de livres affichent les bons statuts pour un utilisateur connecté.

**Acceptance Scenarios**:

1. **Given** un auteur avec 32 contributions, **When** on affiche l'en-tête de bibliographie, **Then** "SA BIBLIOGRAPHIE · 32 fiches" est visible.
2. **Given** un utilisateur connecté avec 14 livres dans sa collection parmi les 32, **When** on affiche l'en-tête, **Then** "14 dans ta collection" est visible.
3. **Given** un utilisateur non connecté, **When** on affiche l'en-tête, **Then** seul le total est affiché (pas de compteur collection personnel).
4. **Given** un auteur ayant des œuvres dans les sagas "Loup Solitaire" (28) et "Légendes de Magnamund" (3), **When** on affiche les filtres, **Then** apparaissent les pills "TOUT · 32", "LOUP SOLITAIRE · 28", "LÉGENDES DE MAGNAMUND · 3".
5. **Given** un livre appartenant à un utilisateur connecté, **When** sa carte est affichée, **Then** le badge vert "DANS MA COLLECTION" s'affiche en footer de carte.
6. **Given** un livre sur la liste d'achats de l'utilisateur, **When** sa carte est affichée, **Then** le texte orange "LISTE D'ACHATS" s'affiche en footer de carte.
7. **Given** un livre non possédé par l'utilisateur, **When** sa carte est affichée, **Then** le texte gris "NON POSSÉDÉ" s'affiche en footer de carte.
8. **Given** n'importe quelle carte de livre, **When** elle est affichée, **Then** ni le bouclier de note communautaire ni aucun score numérique n'est visible.
9. **Given** un auteur avec des œuvres dans plusieurs sagas, **When** on clique la pill "LOUP SOLITAIRE", **Then** la page se recharge avec `?saga=loup-solitaire`, seules les cartes de cette saga sont affichées, et la pill "LOUP SOLITAIRE" est `aria-pressed="true"`.
10. **Given** la grille de livres affichée, **When** on sélectionne "Alphabétique" dans le contrôle Trier, **Then** la page se recharge avec `?sort=alpha` et les cartes sont ordonnées par titre croissant.
11. **Given** la page auteur visitée sans paramètre `?sort=`, **When** la grille de livres est affichée, **Then** les cartes sont ordonnées par `frenchPublicationYear` croissant (tri chronologique) et l'option "Chronologique" est indiquée comme active.
12. **Given** la vue grille affichée, **When** on clique le bouton "Vue · Liste", **Then** le conteneur bascule en mode liste (classe CSS modifiée), sans rechargement de page.
13. **Given** un auteur sans aucune contribution, **When** on visite sa page, **Then** la colonne bibliographie affiche uniquement le message "Aucune œuvre répertoriée." sans grille ni pills de filtrage.
14. **Given** la page visitée avec un paramètre `?sort=` invalide ou absent, **When** la grille est affichée, **Then** le tri chronologique (`frenchPublicationYear` ASC) est appliqué par défaut.

---

### User Story 3 — Vérification des exclusions de la maquette (Priority: P3)

Un visiteur (connecté ou non) visite la page auteur et ne voit aucune trace du bloc "Favoris" (cœur rouge) ni de la section "Contemporains & Pairs".

**Why this priority**: Les exclusions formelles sont un critère d'acceptation explicite. La présence de ces blocs constitue un échec de la feature.

**Independent Test**: Inspecter le HTML rendu de la page auteur et confirmer l'absence de `.seal-row`, `.seal-btn`, `.also-strip` et de tout texte "Contemporains" ou "Mes Favoris".

**Acceptance Scenarios**:

1. **Given** n'importe quel auteur, **When** on visite sa page, **Then** aucun bouton de type "Sceller cet auteur à Mes Favoris" n'est présent dans le DOM rendu.
2. **Given** n'importe quel auteur, **When** on visite sa page, **Then** aucune section "Contemporains & Pairs" ou grille d'auteurs similaires n'est présente dans le DOM rendu.

---

### Edge Cases

- Que se passe-t-il si l'auteur n'a aucune contribution (bibliographie vide) ? → Afficher un message "Aucune œuvre répertoriée." dans la colonne bibliographie.
- Que se passe-t-il si l'auteur n'a qu'une seule saga ? → Une seule pill saga apparaît en plus du bouton "TOUT".
- Que se passe-t-il si la biographie est très courte (1 ou 2 mots) ? → La lettrine s'applique sur le premier caractère, même si le texte est bref.
- Que se passe-t-il si birthDate est null ? → Le bloc dates est absent ou affiche uniquement les données disponibles sans calculer d'âge.
- Que se passe-t-il si le slug ne correspond à aucun auteur ? → La page retourne une 404.
- Que se passe-t-il si `?saga=` contient une valeur inconnue (saga invalide) ? → Toutes les contributions sont affichées (comportement identique à "TOUT"), pill "TOUT" active.

## Clarifications

### Session 2026-05-26

- Q: When a filter pill is clicked, what should happen? → A: Server-side filtering — clicking a pill reloads the page with a `?saga=` query param
- Q: "Trier" and "Vue" toolbar buttons — scope in this feature? → A: Both functional — sort (chronologique / alphabétique) via server-side `?sort=` param; view toggle (grille / liste) via client-side JS class toggle
- Q: How should book cards render in "Vue · Liste" mode? → A: Compact horizontal rows (title, saga, year) — CSS only, no new design artifact
- Q: Where should filter/sort logic live (saga + sort query params)? → A: New `ContributorRepository` method — `findContributionsBySlug(slug, sagaFilter, sortOrder)` returns filtered/sorted contributions; controller reads `?saga=` and `?sort=` from Request and delegates to repo
- Q: Should `Book.status` (BookStatus enum) appear on author page cards alongside collection status? → A: Yes — show `BookStatus` badge on card (PENDING / PUBLISHED / REJECTED) AND collection status (CollectionEntry placeholder) in card footer. Note: "INÉDIT" in the initial answer was illustrative, not an actual enum value.
- Q: How to handle saga→color mapping for unknown sagas? → A: Derive all mappings from mockup; fall back to neutral default color for unmapped sagas (prevents breakage for future sagas)

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Le template `templates/contributeur/author_show.html.twig` DOIT adopter la mise en page deux colonnes de la maquette `design/pages/auteur.html` (colonne profil à gauche, colonne bibliographie à droite).
- **FR-002**: La colonne profil DOIT afficher le portrait (`portraitImage`) dans un cadre stylisé ou un placeholder graphique si absent.
- **FR-003**: La plaque nominative DOIT afficher `firstName` et `lastName` de l'auteur.
- **FR-004**: La liste des attributs DOIT afficher prénom, nom, et nationalité. La ligne pseudonyme DOIT s'afficher uniquement si `pseudo` n'est pas null.
- **FR-005**: Le bloc dates DOIT afficher l'année de naissance et l'âge (calculé dynamiquement). Si `deathDate` est renseigné, afficher l'année de décès et l'âge au décès calculé. Si `birthDate` est null, le bloc dates de vie n'est pas affiché. Note : les champs `birthPlace` et `deathPlace` ne sont pas présents dans l'entité Contributor — la localisation ne sera pas affichée dans la version dynamique.
- **FR-006**: La biographie DOIT s'afficher dans le bloc parchemin avec la lettrine CSS sur la première lettre, uniquement si `biography` n'est pas null. Si `biography` est null, le bloc biographie affiche le texte "Biographie non disponible." à la place du parchemin. En dessous de 1100px, le corps de biographie DOIT être replié par défaut (max-height 280px avec fondu de bas) et un bouton "Lire la suite" DOIT permettre de le déplier via toggle JavaScript inline (ajout/suppression de la classe `.is-collapsed` sur `.bio-body`). À partir de 1100px, le corps de biographie est entièrement affiché sans toggle.
- **FR-007**: L'en-tête de bibliographie DOIT afficher le titre "SA BIBLIOGRAPHIE" avec le compteur dynamique du nombre total de contributions de l'auteur. Si l'auteur n'a aucune contribution, la colonne bibliographie affiche le message "Aucune œuvre répertoriée." à la place de la grille de livres et des pills de filtrage.
- **FR-008**: Pour un utilisateur authentifié, l'en-tête de bibliographie DOIT afficher un compteur du nombre de ces œuvres présentes dans sa collection personnelle (placeholder "0 dans ta collection" tant que la feature collection n'est pas intégrée).
- **FR-009**: Les pills de filtrage DOIT être générées dynamiquement : un bouton "TOUT · [total]" et un bouton par saga distincte présente dans les contributions de l'auteur, avec le nombre d'œuvres par saga. Cliquer sur une pill recharge la page avec le paramètre `?saga=<slug-saga>` ; la pill active est marquée `aria-pressed="true"`. Le bouton "TOUT" supprime le paramètre. Si `?saga=` contient une valeur inconnue (saga invalide), toutes les contributions sont affichées et la pill "TOUT" est active. Le slug de saga pour `?saga=` est dérivé dynamiquement en slugifiant `book.saga` (ex : "Loup Solitaire" → `loup-solitaire`) ; le filtre dans `findContributionsBySlug` compare la valeur slugifiée de `book.saga` contre `sagaFilter`. Le filtrage est délégué à `ContributorRepository::findContributionsBySlug(slug, sagaFilter, sortOrder)` — le contrôleur lit les params Request et appelle cette méthode.
- **FR-010**: Chaque carte de livre DOIT afficher : la couleur de fond dérivée de la saga, la référence courte (abréviation de saga dérivée de `book.saga` + numéro de volume — ex : "LS nº1"), les données d'édition (`editionInfo` + `frenchPublicationYear`), le titre, l'éditeur, la collection, le badge `BookStatus` du livre (PENDING / PUBLISHED / REJECTED — affiché tel quel ou avec un label court), et le statut collection de l'utilisateur en footer (possédé / wishlist / non possédé — placeholder CollectionEntry). Le texte affiché en footer pour un livre non présent dans la collection est "NON POSSÉDÉ". L'abréviation de saga est dérivée d'une correspondance statique définie dans le template Twig (ex : "Loup Solitaire" → "LS") ; pour toute saga absente du mapping, les deux ou trois premières majuscules du nom de saga sont utilisées comme fallback.
- **FR-011**: La page DOIT NE PAS contenir le bloc "Sceller cet auteur à Mes Favoris" (ni son HTML, ni son CSS spécifique).
- **FR-012**: La page DOIT NE PAS contenir la section "Contemporains & Pairs".
- **FR-013**: Aucune carte de livre ne DOIT afficher une note ou un score communautaire (bouclier, chiffre de notation).
- **FR-014**: Le contrôle "Trier" DOIT proposer deux options : "Chronologique" (tri par `frenchPublicationYear` ASC, défaut) et "Alphabétique" (tri par `title` ASC). Le tri actif est transmis via le paramètre `?sort=chrono|alpha` et persiste en combinaison avec `?saga=`. L'option active est indiquée visuellement (bouton ou option mise en évidence). En l'absence du paramètre `?sort=`, le tri chronologique est appliqué par défaut. Le tri est géré dans `ContributorRepository::findContributionsBySlug` — le contrôleur passe les deux paramètres simultanément.
- **FR-015**: Le contrôle "Vue" DOIT permettre de basculer entre "Grille" (défaut) et "Liste" via un toggle JavaScript côté client (ajout/suppression de classe CSS sur le conteneur `.books-grid`). En mode "Liste", chaque livre s'affiche en ligne horizontale compacte (titre, saga, année) — CSS uniquement, sans nouvelle maquette. L'état sélectionné est indiqué visuellement (bouton actif).

### Key Entities

- **Contributor**: Auteur affiché — fournit firstName, lastName, pseudo, nationality, birthDate, deathDate, biography, portraitImage, contributions.
- **Contribution**: Lien entre un Contributor et un Book pour un rôle donné — fournit accès au Book et à la saga via `book.saga`.
- **Book**: Œuvre répertoriée — fournit title, slug, saga (string libre), volumeNumber, frenchPublicationYear, editionInfo, editor, collection, status (`BookStatus` enum : PENDING / PUBLISHED / REJECTED — affiché en badge sur chaque carte de bibliographie).
- **CollectionEntry** *(future)* : Entrée de collection d'un utilisateur — détermine le statut (possédé / wishlist) d'un livre pour l'utilisateur connecté. Placeholder pour cette feature.

### Non-Functional Requirements

- **NFR-001**: La mise en page DOIT être responsive. En dessous de 1100px, les deux colonnes s'empilent verticalement (colonne profil d'abord, colonne bibliographie ensuite). À partir de 1100px, la mise en page deux colonnes s'active (`minmax(360px, 440px)` + `1fr`). En dessous de 1100px, le bouton "Lire la suite / Replier" de la biographie est visible et le corps de biographie est initialement replié à 280px de hauteur maximale. À partir de 1100px, la biographie est entièrement dépliée et le bouton est caché.
- **NFR-002**: La page DOIT respecter les exigences d'accessibilité minimales suivantes : (a) L'image de portrait DOIT avoir un attribut `alt` descriptif (ex: "Portrait de [prénom] [nom]") ; le placeholder DOIT avoir `alt=""`. (b) La section biographie DOIT être identifiée comme région via `aria-label` ou élément sémantique approprié. (c) La grille de cartes de livres DOIT utiliser une liste sémantique (`<ul>/<li>` ou `role="list"`). (d) Les contrôles "Trier" et "Vue" DOIT être accessibles au clavier avec des labels appropriés. (e) Les pills de filtrage DOIT être regroupées dans un `role="group"` avec un label ("Filtrer par saga").

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: La page auteur affiche l'ensemble des données personnelles de l'auteur sans rechargement ni requête supplémentaire visible — toutes les données sont présentes au chargement initial.
- **SC-002**: Les pills de filtrage reflètent exactement les sagas et compteurs calculés depuis la base de données — zéro écart entre le nombre affiché et le nombre de cartes dans la grille.
- **SC-003**: Le HTML source de la page ne contient aucun élément des blocs "Favoris" et "Contemporains" — vérifiable par inspection du DOM ou recherche textuelle.
- **SC-004**: La page est visuellement identique à la maquette `design/pages/auteur.html` pour les sections intégrées (profil gauche + bibliographie droite), aux données dynamiques près.
- **SC-005**: La page se charge dans un temps acceptable pour un utilisateur sur connexion standard (moins de 2 secondes pour un auteur avec jusqu'à 50 contributions).

## Assumptions

- La feature de collection utilisateur (CollectionEntry) n'est pas encore implémentée : le statut "possédé/wishlist" des cartes sera un placeholder statique ("NON POSSÉDÉ" pour tous) jusqu'à l'intégration de cette feature.
- Le contrôleur existant `/authors/{slug}` (`ContributorController::authorShow`) est réutilisé tel quel ou enrichi minimalement — aucune refonte du routage n'est nécessaire.
- La couleur de fond de chaque carte de livre est dérivée du nom de la saga via une correspondance statique définie dans le template, en s'appuyant sur les mappings présents dans `design/pages/auteur.html`. Pour toute saga absente du mapping, une couleur neutre par défaut est appliquée silencieusement (aucune erreur visible).
- Le bloc "Lire la suite / Replier la biographie" (collapse sur mobile) est conservé tel quel de la maquette — il requiert le JavaScript inline présent dans la maquette, sans dépendance externe.
- La navigation principale (header, breadcrumb, bottom-nav mobile) s'appuie sur le layout `base.html.twig` existant — seul le contenu spécifique à la page auteur est intégré.
- Le calcul de l'âge (vivant ou au décès) est réalisé côté PHP dans le contrôleur (`authorShow`) : `$contributorAge` et `$contributorAgeAtDeath` sont passés comme variables Twig. Cette approche est préférée à un calcul Twig pour la lisibilité et la testabilité.
- Le numéro de référence de l'auteur visible dans la maquette (ex: "AUT-0018") est absent de l'entité Contributor — le `.portrait-eyebrow` affiche le slug du contributeur (ex: "joe-dever") à la place de la référence numérique.
- Le numéro de référence de livre visible dans la maquette (ex: "LCA-0118") n'existe pas dans l'entité Book. La référence courte dynamique sera construite à partir d'une abréviation de saga (mapping statique Twig) + `volumeNumber` (ex: "LS nº1"). Une abréviation fallback (2-3 premières majuscules du nom de saga) est utilisée pour les sagas absentes du mapping.
- Les champs `birthPlace` et `deathPlace` ne sont pas présents dans l'entité Contributor — la localisation affichée dans la maquette (ex: "Hammersmith / Londres") ne sera pas disponible dans la version dynamique.

## Repository Interface Contract

La méthode suivante DOIT être créée dans `ContributorRepository` :

```php
/**
 * @return array{
 *   contributor: Contributor,
 *   filteredContributions: list<Contribution>,
 *   sagaGroups: list<array{slug: string, name: string, count: int}>,
 *   totalCount: int
 * }|null
 */
findContributionsBySlug(
  slug: string,
  sagaFilter: ?string,   // saga slugifiée (ex: 'loup-solitaire'), null = pas de filtre
  sortOrder: string      // 'chrono' (défaut) | 'alpha'
): ?array
```

- **Retour** : tableau avec `contributor`, `filteredContributions` (filtrées/triées), `sagaGroups` (liste complète non filtrée pour les pills), et `totalCount` — ou `null` si le slug ne correspond à aucun contributeur.
- **Filtrage** : si `sagaFilter` est non-null, seules les contributions dont `slugify(book.saga) === sagaFilter` sont incluses. Une valeur inconnue retourne toutes les contributions (comportement identique à null).
- **Tri** : `chrono` → `frenchPublicationYear` ASC (NULL en dernier) ; `alpha` → `title` ASC.
- **Contrôleur** : `authorShow` lit `?saga=` et `?sort=` depuis la `Request` et les passe à cette méthode. La logique métier reste dans le repository, pas dans le contrôleur.
