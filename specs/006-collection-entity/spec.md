# Feature Specification: Entité Collection et Vue Détail

**Feature Branch**: `006-collection-entity`

**Created**: 2026-05-25

**Status**: Draft

**Input**: User description: "Entité Collection (série de livres-jeux), vue détail, relation One-to-Many avec Livre, mise à jour des vues Livre"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Consulter la fiche d'une collection (Priority: P1)

Un visiteur accède à la page détail d'une collection depuis l'URL `/collections/{slug}`. Il voit les informations globales de la série (nom, description, genre, créateurs, année de création, éditeur historique, statut) et la liste complète des livres de la série, triés par numéro d'ordre officiel.

**Why this priority**: Sans cette page, les liens de collection sur les fiches livre sont des impasses. C'est le pivot central de la navigation thématique.

**Independent Test**: Naviguer vers `/collections/defis-fantastiques` → page affiche nom, description, genre, créateurs, statut et liste des livres ordonnés. Valide indépendamment de la mise à jour des fiches livre.

**Acceptance Scenarios**:

1. **Given** une collection en base avec des livres liés, **When** l'utilisateur accède à `/collections/{slug}`, **Then** la page affiche le nom français, le nom original (si présent), la description, le genre, la liste des créateurs, l'année de création, l'éditeur historique, le statut et la liste des livres triés par `volumeNumber` croissant.
2. **Given** une collection sans livre lié, **When** l'utilisateur accède à sa page, **Then** la page affiche les infos de la collection avec une section "Aucun livre disponible" à la place de la liste.
3. **Given** un slug inexistant, **When** l'utilisateur accède à l'URL, **Then** la page retourne HTTP 404.
4. **Given** une collection avec `imageLogo` renseigné, **When** l'utilisateur consulte la page, **Then** le logo de la gamme est affiché dans le header de la fiche.

---

### User Story 2 - Naviguer depuis une fiche livre vers sa collection (Priority: P2)

Depuis la fiche d'un livre appartenant à une collection, l'utilisateur peut cliquer sur le nom de la collection pour accéder à la page de cette collection. Le fil d'Ariane (breadcrumb) reflète la hiérarchie Collection → Livre.

**Why this priority**: Améliore la navigation contextuelle sans bloquer la vue collection. Dépend du modèle de données défini en P1.

**Independent Test**: Sur une fiche livre liée à une collection, cliquer le lien collection → redirige vers `/collections/{slug}`. Le breadcrumb affiche : `CATALOGUE / {Nom Collection (lien)} / {Titre Livre}`.

**Acceptance Scenarios**:

1. **Given** un livre avec une collection liée, **When** l'utilisateur consulte la fiche livre, **Then** le nom de la collection est cliquable et pointe vers `/collections/{slug}`.
2. **Given** un livre avec une collection liée, **When** l'utilisateur consulte la fiche livre, **Then** le fil d'Ariane affiche : `CATALOGUE / {Nom Collection (lien)} / {Titre Livre}`.
3. **Given** un livre sans collection (hors-série), **When** l'utilisateur consulte la fiche livre, **Then** le fil d'Ariane affiche : `CATALOGUE / {Titre Livre}` (pas de segment collection).
4. **Given** un livre avec une collection liée, **When** l'utilisateur consulte le tableau Fiche Technique, **Then** le champ "Saga / Volume" affiche le lien cliquable vers `/collections/{slug}` en plus du nom et du numéro de tome.

---

### Edge Cases

- Que se passe-t-il si un livre `hors-série` n'a pas de `collection_id` ? → Le lien collection n'est pas affiché sur la fiche livre ; le breadcrumb utilise le chemin sans collection.
- Comment afficher les créateurs multiples (tableau JSON) ? → Inline séparés par virgules (ex : "Steve Jackson, Ian Livingstone").
- Que se passe-t-il si `nomOriginal` est absent ? → Le champ n'est pas affiché sur la fiche collection.
- Que se passe-t-il si `imageLogo` est absent ? → Le placeholder `placeholder-cover.svg` existant est réutilisé (même asset que les couvertures de livres).
- Que se passe-t-il si `createurs` est un tableau vide `[]` ? → La ligne "Créateurs" est masquée (même comportement que les champs optionnels absents).
- Que se passe-t-il si `anneeCreation` est dans le futur ? → Validé côté serveur ; années > année courante côté serveur rejetées.
- Comment trier les livres si plusieurs n'ont pas de `volumeNumber` ? → Les livres sans `volumeNumber` sont triés en dernier, puis par titre français (`titre`) alphabétique.
- Que se passe-t-il si le paramètre `?page=N` est hors bornes (> nombre de pages, ou ≤ 0) ? → HTTP 404 retourné.
- Que se passe-t-il si le paramètre `?page=N` n'est pas un entier (ex : `?page=abc`) ? → HTTP 404 retourné (même comportement que hors bornes).

## Clarifications

### Session 2026-05-25

- Q: Pagination des livres d'une collection → A: Pagination côté serveur, 20 livres par page
- Q: Implémentation des enums `genre` et `statut` → A: PHP 8.1 backed enums (`GenreCollection`, `StatutCollection`)
- Q: Visibilité des pages collection → A: Public, aucune authentification requise
- Q: Comportement si le slug change → A: Pas de redirect, ancien slug retourne 404
- Q: Niveau de couverture de tests → A: Tests fonctionnels Symfony (WebTestCase) + factories Foundry
- Q: Breadcrumb sur la page collection `/collections/{slug}` → A: `CATALOGUE / {Nom Collection}` (même patron que la fiche livre)
- Q: Validation du champ `createurs` (cardinalité minimale) → A: Tableau vide `[]` valide — aucune contrainte de cardinalité
- Q: Format `<title>` de la page collection → A: `{Nom Collection} — La Collection dont vous êtes le héros` (même patron que fiche livre)
- Q: Comportement slug si collision (nom renommé génère un slug déjà existant) → A: Appende suffixe numérique (`-2`, `-3`…) jusqu'à unicité — comportement standard SluggerInterface
- Q: Affichage `createurs` vide `[]` → A: Masquer la ligne (même patron que champs optionnels)
- Q: Placeholder `imageLogo` absent → A: Réutiliser `placeholder-cover.svg` existant
- Q: Tri alphabétique titre pour livres sans `volumeNumber` → A: Tri sur `titre` (titre français)
- Q: `?page=abc` (non-entier) → A: HTTP 404 (même que hors-bornes)
- Q: SC-001 performance s'applique à toutes les pages paginées → A: Oui, toutes les pages (page 1 et pages suivantes)
- Q: SEO pages paginées → A: Dans le scope — pages `?page=N≥2` : balise `<title>` suffixée `(page N)` + `<link rel="canonical">` pointant vers page 1
- Q: Down() migration → A: Oui, implémenter la méthode de rollback
- Q: Convention CSS badges genre/statut → A: Classes `badge-genre-{valeur}` et `badge-statut-{valeur}` à créer dans `_badges.scss`
- Q: Breadcrumb fiche livre avec collection → A: `CATALOGUE / {Nom Collection (lien)} / {Titre Livre}` (même patron que collection page)
- Q: Breadcrumb fiche livre sans collection → A: `CATALOGUE / {Titre Livre}` (inchangé par rapport à l'existant)
- Q: `genre` deviendra une entité séparée → A: Hors scope pour cette spec ; backed enum `GenreCollection` pour l'instant ; migration vers entité `Genre` prévue dans une future itération

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Le système DOIT créer une entité `Collection` avec les champs suivants :

  | Champ | Type | Nullable | Contraintes |
  |-------|------|----------|-------------|
  | `id` | UUID | Non | Clé primaire auto-générée |
  | `nom` | string(255) | Non | Unique en base |
  | `nomOriginal` | string(255) | Oui | — |
  | `slug` | string(255) | Non | Auto-généré depuis `nom` (SluggerInterface) ; unique en base ; régénéré à la modification de `nom` |
  | `description` | text | Non | — |
  | `genre` | `GenreCollection` (backed enum) | Non | Valeurs : `medieval-fantastique`, `science-fiction`, `horreur`, `espionnage`, `aventure`, `contemporain` ; stocké comme string en base. **Note** : implémenté comme backed enum pour cette spec ; prévu de migrer vers une entité `Genre` séparée dans une future itération. |
  | `createurs` | JSON array | Non | Tableau de noms de créateurs (ex : `["Steve Jackson", "Ian Livingstone"]`) ; défaut `[]` |
  | `anneeCreation` | int | Oui | Année sur 4 chiffres ; doit être ≤ année courante si renseignée |
  | `editeurHistorique` | string(255) | Oui | — |
  | `statut` | `StatutCollection` (backed enum) | Non | Valeurs : `en-cours`, `terminee`, `reeditee` ; stocké comme string en base |
  | `imageLogo` | string(255) | Oui | Chemin ou URL vers le logo officiel de la gamme |

  Index de base de données requis sur `slug` (lookup URL) et `nom` (unicité). La colonne `collection_id` sur la table `book` DOIT également porter un index (performance des jointures).

- **FR-002**: L'entité `Collection` DOIT être liée à l'entité `Livre` (Book) via une relation One-to-Many. La clé étrangère `collection_id` sur l'entité `Book` est nullable (cas des hors-séries). La suppression d'une `Collection` DOIT mettre `collection_id` à `NULL` sur les livres liés (`SET NULL` — pas de `CASCADE DELETE`).

- **FR-003**: Le slug de `Collection` DOIT être généré automatiquement via le composant `SluggerInterface` de Symfony à partir du champ `nom`, lors de la création et de la mise à jour. Le slug est régénéré si et seulement si `nom` a changé. En cas de collision (slug généré déjà existant en base), un suffixe numérique DOIT être appendé automatiquement jusqu'à unicité (ex : `defis-fantastiques-2`, `defis-fantastiques-3`).

- **FR-004**: La page `/collections/{slug}` DOIT afficher : nom français, nom original (si présent), description, genre (badge `.badge-genre-{valeur}` design system), liste des créateurs (inline séparés par virgules — ligne masquée si `createurs` est `[]`), année de création (si présente), éditeur historique (si présent, texte plain), badge de statut (`.badge-statut-{valeur}` design system), logo de gamme (chemin/URL depuis `imageLogo` si présent, sinon `placeholder-cover.svg`), et la liste des livres liés triés par `volumeNumber` croissant. Les livres sans `volumeNumber` apparaissent en dernier, triés par `titre` (titre français) alphabétique. La liste est paginée côté serveur : 20 livres par page, paramètre `?page=N` dans l'URL (défaut : page 1). Un paramètre `page` hors bornes ou non-entier retourne HTTP 404. Le fil d'Ariane DOIT afficher : `CATALOGUE / {Nom Collection}` (même patron que la fiche livre). La balise `<title>` DOIT suivre le format : `{Nom Collection} — La Collection dont vous êtes le héros` pour la page 1 (voir FR-012 pour les pages suivantes).

- **FR-005**: La liste des livres sur la page collection DOIT être récupérée via le `Doctrine\ORM\Tools\Pagination\Paginator` (compatible pagination + requête unique sans N+1). Chaque entrée de livre affiche a minima : couverture (ou placeholder), numéro de volume (si présent), titre français et lien vers la fiche livre `/livre/{slug}`.

- **FR-006**: La fiche livre (`/livre/{slug}`) DOIT être mise à jour pour que toutes les mentions de la collection soient des liens cliquables pointant vers `/collections/{slug}`. Cela inclut :
  - La ligne "Saga / Volume" dans le tableau Fiche Technique (FR-005 de la spec 005) : le nom de la collection devient un lien.
  - Tout autre affichage du nom de collection sur la fiche livre.

- **FR-007**: Le fil d'Ariane (breadcrumb) de la fiche livre DOIT être mis à jour pour suivre le même patron racine que la page collection (`CATALOGUE / …`) :
  - Si le livre a une collection : `CATALOGUE / {Nom Collection (lien vers /collections/{slug})} / {Titre Livre}`
  - Si le livre est hors-série (pas de collection) : `CATALOGUE / {Titre Livre}` (inchangé par rapport à l'existant)

- **FR-008**: La validation Symfony (`Symfony\Component\Validator\Constraints`) DOIT imposer :
  - `nom` : not blank, longueur max 255, unicité en base.
  - `slug` : not blank, longueur max 255, unicité en base (géré automatiquement, non modifiable par l'utilisateur).
  - `anneeCreation` : entier positif, valeur ≤ année courante (si renseigné).
  - `genre` : valeur valide de `GenreCollection::cases()` (PHP backed enum, validé via `#[Assert\Choice]`).
  - `statut` : valeur valide de `StatutCollection::cases()` (PHP backed enum, validé via `#[Assert\Choice]`).
  - `createurs` : aucune contrainte de cardinalité — tableau vide `[]` valide.

- **FR-009**: L'affichage sur la page collection DOIT utiliser strictement les variables CSS et utilitaires du design system du projet :
  - Badges de genre : classes CSS `.badge-genre-{valeur}` à créer dans `assets/styles/components/_badges.scss` pour chacune des 6 valeurs de `GenreCollection` (convention `badge-genre-medieval-fantastique`, etc.).
  - Badges de statut : classes CSS `.badge-statut-{valeur}` à créer dans `assets/styles/components/_badges.scss` pour chacune des 3 valeurs de `StatutCollection` (`badge-statut-en-cours`, `badge-statut-terminee`, `badge-statut-reeditee`).
  - Typographie, bordures et ombres des cartes livres : variables CSS existantes du design system (`--font-*`, `--radius-*`, `--border-*`, `--bg-*`, etc.).
  - Si une valeur d'enum n'a pas de mappage sémantique évident, utiliser le style "badge neutre" (`.badge` sans modificateur).

- **FR-010**: Le formulaire d'administration de création/édition de `Collection` est hors scope de cette spec. Les données de test sont créées via factories Foundry. La migration Doctrine pour la table `collection` et la colonne `collection_id` sur `book` est dans le scope, et DOIT inclure une méthode `down()` de rollback (drop table `collection` + suppression colonne `collection_id` sur `book`). La factory Foundry pour `Collection` DOIT définir des valeurs par défaut explicites pour tous les champs nullable : `nomOriginal: null`, `anneeCreation: null`, `editeurHistorique: null`, `imageLogo: null`, `createurs: []`. Les tests couvrent :
  - Tests fonctionnels HTTP (`WebTestCase`) pour les acceptance scenarios : page collection avec livres, page vide (aucun livre), 404 slug inexistant, pagination (paramètres hors-bornes et non-entier → 404), logo présent vs absent.
  - Tests du breadcrumb de la fiche livre : avec collection (lien cliquable), sans collection (breadcrumb simplifié).
  - Tests du tri des livres : livres avec `volumeNumber`, livres sans `volumeNumber` (vérification ordre titre français).
  - Factories Foundry pour les fixtures de test.

- **FR-011**: Les routes `/collections/{slug}` et `/collections/{slug}?page=N` DOIVENT être accessibles sans authentification (accès public). La configuration `security.yaml` DOIT inclure ces routes dans la section `access_control` avec `PUBLIC_ACCESS` (ou `IS_AUTHENTICATED_ANONYMOUSLY` selon la version Symfony en place).

- **FR-012**: SEO pour les pages paginées de la collection :
  - Pages `?page=N` avec N ≥ 2 : la balise `<title>` DOIT porter le suffixe ` (page N)`, ex : `Défis Fantastiques (page 2) — La Collection dont vous êtes le héros`.
  - Toutes les pages paginées DOIVENT inclure une balise `<link rel="canonical" href="/collections/{slug}">` pointant vers la page 1 (URL sans `?page=`).
  - La page 1 (avec ou sans `?page=1`) ne porte pas de suffixe ni de canonical particulier.

### Key Entities

- **Collection**: Représente une série de livres-jeux (ex : "Défis Fantastiques"). Entité pivot regroupant les métadonnées de la gamme et servant de point d'entrée pour la navigation thématique.
- **Book** (existant, spec 005): Mis à jour avec la relation `ManyToOne` vers `Collection` (nullable). La fiche livre et son breadcrumb sont mis à jour pour référencer la collection.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: La page `/collections/{slug}` affiche toutes les données de la collection et sa liste de livres en moins de 2 secondes (p95, cache chaud, conditions normales de charge). Ce critère s'applique à toutes les pages paginées (`?page=N`), pas uniquement à la page 1.
- **SC-002**: 100% des champs définis dans FR-001 sont persistés et restitués sans perte de données.
- **SC-003**: La liste des livres d'une collection est récupérée sans requête N+1 (Doctrine Paginator) — validé par le profiler Symfony. Chaque page contient au maximum 20 livres.
- **SC-004**: Un slug inexistant retourne HTTP 404 dans 100% des cas.
- **SC-005**: Toutes les mentions de collection sur les fiches livre sont des liens fonctionnels vers la page collection correspondante.
- **SC-006**: Le breadcrumb de la fiche livre reflète correctement la hiérarchie (avec ou sans collection) dans 100% des cas testés.
- **SC-007**: L'affichage est conforme au design system — validé par revue visuelle en comparaison avec les variables CSS du projet.

## Assumptions

- L'entité `Book` existe déjà (spec 005 mergée). La migration pour ajouter `collection_id` nullable à la table `book` est à générer dans cette spec. **Dépendance bloquante** : spec 005 doit être mergée et le PR correspondant fusionné sur `master` avant d'implémenter FR-002 et FR-006. Vérifier via `git log --oneline master | grep 005` avant de démarrer l'implémentation.
- Le slug est généré via `SluggerInterface` de Symfony (composant natif `symfony/string`), pas via Gedmo Sluggable — choix délibéré distinct de `Book` qui utilise Gedmo. Ce choix doit être documenté dans le code (commentaire sur la méthode de génération) pour éviter une normalisation future involontaire. En cas de collision, le suffixe numérique est appendé (ex : `-2`, `-3`) jusqu'à unicité. Si `nom` change, le slug est régénéré et l'ancien slug retourne HTTP 404 (pas de redirect 301 — hors scope).
- Les images (logo de collection) sont référencées par chemin/URL stocké en base ; pas de gestion d'upload dans cette spec (hors scope, données via fixtures). Le placeholder pour `imageLogo` absent est `placeholder-cover.svg` (même asset que les couvertures de livres).
- Le design system (`assets/styles/components/_badges.scss`) ne contient pas encore de classes `.badge-genre-*` ni `.badge-statut-*` — ces classes DOIVENT être créées dans le cadre de cette spec (FR-009). Les variables CSS de token (couleurs, spacing, typographie) existent déjà.
- Les valeurs d'enum `genre` et `statut` sont définies via PHP 8.1 backed enums (`GenreCollection`, `StatutCollection`) et synchronisées avec les contraintes de validation Symfony via `#[Assert\Choice(callback: [GenreCollection::class, 'cases'])]`. La migration vers une entité `Genre` séparée est prévue dans une future itération (hors scope ici).
- Il n'y a pas de page liste des collections (`/collections`) dans le scope de cette spec — uniquement la page détail d'une collection individuelle. Le fil d'Ariane de la fiche livre utilise `Catalogue / {Nom Collection} / {Titre}` sans lien intermédiaire vers une liste.
- La langue d'affichage est le français.
- Le support mobile est dans le scope (design system responsive, pas de breakpoints spécifiques à définir — variables existantes utilisées).
