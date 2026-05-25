# Feature Specification: Book Detail Page (Fiche Œuvre)

**Feature Branch**: `005-book-detail-view`

**Created**: 2026-05-24

**Status**: Draft

**Input**: User description: "Modèle de Données et Vue Détail de la Fiche Œuvre (Livre)"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Consulter la fiche d'un livre (Priority: P1)

Un visiteur ou membre authentifié accède à la page détail d'un livre depuis le catalogue. Il voit toutes les informations éditoriales (titre, auteurs, éditeur, ISBN, nombre de pages, paragraphes, résumé, couverture) présentées dans l'interface "vieux grimoire" définie par le design system.

**Why this priority**: Pivot central du site — sans fiche livre, aucune fonctionnalité de collection ou de liste d'envies n'est possible.

**Independent Test**: Naviguer vers `/livre/{slug}` avec un livre en base → page affiche titre, auteurs, éditeur, couverture, résumé et tableau fiche technique.

**Acceptance Scenarios**:

1. **Given** un livre publié en base, **When** l'utilisateur accède à son URL, **Then** la page affiche le badge de collection, le numéro de volume, le titre français et le titre original dans le header.
2. **Given** un livre publié en base, **When** l'utilisateur consulte le tableau fiche technique, **Then** il voit ISBN, pages, paragraphes, année de parution française, année originale, auteurs, illustrateurs, traducteur et éditeur.
3. **Given** un livre non publié (PENDING ou REJECTED), **When** un visiteur sans `ROLE_MODERATOR` accède à l'URL, **Then** la page retourne une erreur 404.

---

### User Story 2 - Parcourir la galerie d'images (Priority: P2)

L'utilisateur peut naviguer entre les vues de l'objet livre (Tome, Dos, Tranche, Pages, Carte) via un système d'onglets sur la fiche.

**Why this priority**: Enrichit l'expérience de consultation sans bloquer le MVP de la fiche.

**Independent Test**: Cliquer chaque onglet de la galerie → l'image correspondante s'affiche, l'onglet actif est visuellement distingué.

**Acceptance Scenarios**:

1. **Given** un livre avec images de galerie, **When** l'utilisateur clique sur l'onglet "Dos", **Then** l'image du dos du livre s'affiche à la place de la couverture.
2. **Given** un livre sans image pour un onglet donné, **When** l'utilisateur consulte la galerie, **Then** cet onglet n'est pas affiché dans la barre de navigation.

---

### User Story 3 - Accéder au lien partenaire "La Taverne" (Priority: P3)

L'utilisateur voit un bouton "En discuter sur la Taverne des Aventuriers" pointant vers le forum partenaire, affirmant la complémentarité des deux sites.

**Why this priority**: Engagement partenaire contractuel ; visible sans nécessiter d'authentification.

**Independent Test**: Cliquer le bouton Taverne → ouvre le lien externe dans un nouvel onglet.

**Acceptance Scenarios**:

1. **Given** n'importe quelle fiche livre publiée, **When** l'utilisateur clique "En discuter sur la Taverne", **Then** le lien partenaire s'ouvre dans un nouvel onglet navigateur.

---

### User Story 4 - Préparer les actions de collection (Priority: P4)

La barre d'action interactive affiche les boutons "Ma Collection", "À lire", "À acheter" et "Favori" comme ancres visuelles prêtes pour la spec 006 (gestion de collection membre).

**Why this priority**: Prépare l'intégration collection sans l'implémenter — doit être présent visuellement dès le MVP de la fiche.

**Independent Test**: La barre d'action est visible sur toute fiche publiée avec les 4 boutons rendus dans le design system ; les boutons ne déclenchent pas d'action fonctionnelle pour l'instant.

**Acceptance Scenarios**:

1. **Given** un livre publié, **When** l'utilisateur consulte la fiche, **Then** les 4 boutons d'action sont visibles et stylisés conformément au design system.

---

### Edge Cases

- Que se passe-t-il si un livre n'a pas de traducteur (livre en langue originale française) ?
- Comment afficher un livre sans résumé renseigné ? → Section masquée si `summary` null/vide.
- Que se passe-t-il si `volumeNumber` est absent (œuvre hors saga) ? → Numéro de volume et saga masqués du header ; le header affiche titre français et titre original uniquement.
- Comment gérer un livre avec plusieurs auteurs dans le header et le tableau fiche technique ? → Inline séparés par virgule dans les deux cas.
- Que se passe-t-il si `coverImage` est absent ?

## Clarifications

### Session 2026-05-25 (checklist review)

- Q: `tirage` dans US1-SC2 — champ réel ou erreur ? → A: Erreur — supprimé de l'acceptance scenario ; pas de champ `tirage` dans l'entité Book.
- Q: Définition "badge de collection" dans le header ? → A: Hors scope — supprimé de FR-004 ; le header affiche volume + titre uniquement.
- Q: Barre d'action visible aux visiteurs anonymes ? → A: Non — masquée pour les anonymes, visible uniquement pour les utilisateurs authentifiés.
- Q: Comportement header si `volumeNumber` et `saga` tous deux null ? → A: Les deux éléments masqués, header = titre français + titre original uniquement.
- Q: Schémas des entités liées (Author, Illustrator, Translator, Editor) ? → A: Personnes (Author, Illustrator, Translator) = `firstName` + `lastName` + `slug` ; organisation (Editor) = `name` + `slug`.
- Q: Affichage de plusieurs illustrateurs ? → A: Inline séparés par virgule, identique aux auteurs.
- Q: Galerie avec zéro BookImage — comportement ? → A: Onglet "Tome" affiché par défaut avec placeholder SVG de couverture.
- Q: Format ISBN ? → A: ISBN-10 et ISBN-13 acceptés, stocké tel quel, pas de validation de format. Unique en base quand renseigné.
- Q: Types et nullabilité des champs FR-001 ? → A: Confirmés avec corrections : `isbn` nullable, `pages` nullable (tableau FR-001 mis à jour).
- Q: Ordre et labels du tableau Fiche Technique ? → A: Ordre défini (12 lignes) dans FR-005 — auteurs, traducteur, saga/volume, ISBN, parution France, paragraphes, illustrateurs, éditeur, édition, parution originale, pages, langues disponibles.
- Q: Paramètre URL de La Taverne ? → A: Variable d'environnement `TAVERNE_URL`, injectée directement.
- Q: SEO dans le scope ? → A: Oui — balise title, meta description, og:title et og:image (FR-016 ajouté).
- Q: Contrainte unique sur `isbn` ? → A: Unique en base quand renseigné (unique index nullable).
- Q: `languages` tableau vide — afficher ou masquer la ligne Fiche Technique ? → A: Masquée, même règle que les champs null.
- Q: État visuel des boutons d'action pour les utilisateurs authentifiés ? → A: Stylisés normalement (design system, apparence active), sans handler de clic.
- Q: Niveau d'accessibilité pour la navigation onglets galerie ? → A: WCAG 2.1 AA — clavier + lecteurs d'écran, rôles ARIA (tablist/tab/tabpanel).

### Session 2026-05-24

- Q: Affichage champs nullable dans tableau Fiche Technique ? → A: Ligne masquée (row absent du DOM si valeur null)
- Q: Onglet galerie sans image en base ? → A: Onglet masqué si aucun `BookImage` pour ce tab
- Q: Formulaire admin de création/édition livre dans scope ? → A: Non — données créées via fixtures uniquement ; formulaire admin hors scope
- Q: Mécanisme de génération du slug URL ? → A: Gedmo Sluggable (StofDoctrineExtensionsBundle), auto-généré depuis `title`
- Q: Plusieurs `BookImage` par tab possible ? → A: Non — une seule image par tab ; contrainte d'unicité (book + tab)
- Q: Quel est le mode de stockage de `galleryImages` ? → A: Entité liée `BookImage` (OneToMany), champs `tab` + `imagePath`
- Q: Comportement visuel si `coverImage` absent ? → A: Placeholder SVG statique défini dans le design system
- Q: Quel rôle peut accéder aux fiches PENDING/REJECTED ? → A: ROLE_MODERATOR (+ ROLE_ADMIN par hiérarchie)
- Q: Stratégie de déduplication du slug en cas de collision de titre ? → A: Suffix ISBN court (ex: `sorciere-des-neiges-2070368`)
- Q: Transitions de statut (PENDING/PUBLISHED/REJECTED) dans le scope ? → A: Hors scope — couvert par une future spec backoffice de modération
- Q: Mécanisme de stockage des images (`imagePath` sur `BookImage`) ? → A: Filesystem local + VichUploaderBundle configuré dans cette spec ; formulaire d'upload hors scope (fixtures uniquement)
- Q: Définition du champ `editionInfo` ? → A: String libre (ex: "2e édition revue et corrigée")
- Q: Type du champ `languages` ? → A: JSON array de codes ISO (ex: `["fr", "en"]`)
- Q: Affichage si `summary` null/vide ? → A: Section "Résumé du Grimoire" masquée
- Q: Affichage de plusieurs auteurs dans le header ? → A: Inline séparés par virgule (ex: "Steve Jackson, Ian Livingstone")

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Le système DOIT exposer une entité `Book` avec les champs suivants (types et nullabilité définis) :

  | Champ | Type | Nullable | Contraintes |
  |-------|------|----------|-------------|
  | `title` | string | Non | — |
  | `originalTitle` | string | Oui | — |
  | `slug` | string | Non | Auto-généré (Gedmo Sluggable depuis `title`) ; unique en base ; collision résolue par suffix ISBN |
  | `isbn` | string | Oui | Unique en base quand renseigné (ISBN-10 ou ISBN-13, stocké tel quel sans validation de format) |
  | `pages` | int | Oui | — |
  | `paragraphs` | int | Oui | — |
  | `frenchPublicationYear` | int | Oui | Année sur 4 chiffres |
  | `originalPublicationYear` | int | Oui | Année sur 4 chiffres |
  | `editionInfo` | string | Oui | Ex : "2e édition revue et corrigée" |
  | `saga` | string | Oui | — |
  | `volumeNumber` | int | Oui | — |
  | `summary` | text | Oui | — |
  | `coverImage` | string | Oui | Géré par VichUploaderBundle (chemin fichier) |
  | `status` | enum | Non | Valeurs : PENDING, PUBLISHED, REJECTED |
  | `languages` | JSON array | Non | Défaut `[]` ; codes ISO 639-1 (ex : `["fr", "en"]`) |

  Index de base de données requis sur `slug` (lookup URL) et `status` (filtrage accès).
- **FR-002**: L'entité `Book` DOIT comporter des relations vers les entités suivantes :

  | Entité | Relation | Nullable | Champs propres de l'entité liée |
  |--------|----------|----------|---------------------------------|
  | `Author` | ManyToMany | — | `firstName` (string), `lastName` (string), `slug` (string, unique, auto-généré) |
  | `Illustrator` | ManyToMany | — | `firstName` (string), `lastName` (string), `slug` (string, unique, auto-généré) |
  | `Translator` | ManyToOne | Oui | `firstName` (string), `lastName` (string), `slug` (string, unique, auto-généré) |
  | `Editor` | ManyToOne | Non | `name` (string), `slug` (string, unique, auto-généré) |
  | `BookImage` | OneToMany | — | `tab` (enum : Tome, Dos, Tranche, Pages, Carte), `imagePath` (string, géré par VichUploaderBundle) |

  Contrainte d'unicité sur le couple (`book`, `tab`) pour `BookImage` — enforced au niveau base de données (unique index). `cascadeRemove` et `orphanRemoval=true` sur la relation `BookImage` : la suppression d'un `Book` supprime ses `BookImage` associés.
- **FR-003**: Le statut `status` DOIT supporter les valeurs PENDING, PUBLISHED, REJECTED. Règles d'accès (s'appuie sur le système RBAC défini dans la spec 004) :
  - `PUBLISHED` : accessible à tous (anonymes et authentifiés).
  - `PENDING` / `REJECTED` : retourne HTTP 404 pour tout utilisateur sans `ROLE_MODERATOR` — qu'il soit anonyme ou authentifié (`ROLE_USER` inclus). La distinction anonyme/authentifié n'a pas d'effet ici : les deux obtiennent un 404.
  - `ROLE_MODERATOR` (et `ROLE_ADMIN` par hiérarchie de rôles définie dans spec 004) peuvent accéder aux fiches PENDING et REJECTED.
- **FR-004**: La page détail DOIT afficher le header avec : numéro de volume, titre français, titre original, et liste d'auteurs. Si `saga` et `volumeNumber` sont tous deux null (œuvre hors saga), ces deux éléments sont masqués — le header affiche uniquement le titre français et le titre original. Si plusieurs auteurs ou illustrateurs, ils sont affichés inline séparés par des virgules (ex : "Steve Jackson, Ian Livingstone"). Même règle pour les illustrateurs dans le tableau Fiche Technique.
- **FR-005**: La page détail DOIT afficher un tableau "Fiche Technique" avec les lignes suivantes, dans cet ordre (toute ligne dont la valeur est null ou tableau vide est masquée — row absent du DOM) :

  | # | Label affiché | Champ source |
  |---|---------------|--------------|
  | 1 | Auteur(s) | `authors` (inline, virgule) |
  | 2 | Traducteur | `translator` |
  | 3 | Saga / Volume | `saga` + `volumeNumber` (combiné, ex : "Défis Fantastiques — tome 3") |
  | 4 | ISBN | `isbn` |
  | 5 | Parution (France) | `frenchPublicationYear` |
  | 6 | Paragraphes | `paragraphs` |
  | 7 | Illustrateur(s) | `illustrators` (inline, virgule) |
  | 8 | Éditeur | `editor` |
  | 9 | Édition | `editionInfo` |
  | 10 | Parution originale | `originalPublicationYear` |
  | 11 | Pages | `pages` |
  | 12 | Langues disponibles | `languages` (voir `design/pages/livre.html` pour le rendu) |
- **FR-006**: La page détail DOIT afficher le "Résumé du Grimoire" avec lettrine si le design system la prévoit. Si `summary` est null ou vide, la section est masquée.
- **FR-007**: La page détail DOIT intégrer un système d'onglets (Tome, Dos, Tranche, Pages, Carte) pour la galerie d'images. Seuls les onglets ayant au moins un `BookImage` en base sont affichés ; un onglet sans image est masqué. Exception : si aucun `BookImage` n'existe pour le livre (galerie vide), l'onglet "Tome" est affiché par défaut avec le placeholder SVG de couverture. La navigation par onglets DOIT respecter WCAG 2.1 AA : accessible au clavier (touches flèches/Tab), rôles ARIA appropriés (`role="tablist"`, `role="tab"`, `role="tabpanel"`), labels lisibles par les lecteurs d'écran.
- **FR-008**: La page détail DOIT afficher un bloc "En discuter sur la Taverne des Aventuriers" avec lien externe s'ouvrant dans un nouvel onglet (`target="_blank" rel="noopener noreferrer"`). L'URL est lue depuis la variable d'environnement `TAVERNE_URL`, injectée directement dans le contrôleur ou comme variable Twig globale.
- **FR-009**: La barre d'action DOIT afficher les boutons "Ma Collection", "À lire", "À acheter" et "Favori" uniquement pour les utilisateurs authentifiés. Pour les visiteurs anonymes, la barre d'action n'est pas rendue. Pour les utilisateurs authentifiés, les boutons sont stylisés normalement (design system, apparence active) mais sans handler de clic — ancres visuelles pour la spec 006 (gestion de collection membre).
- **FR-010**: Le système DOIT exclure le bloc "La Communauté", les commentaires, les avatars des membres et les moyennes de notes globales.
- **FR-014**: La gestion des transitions de statut (PENDING → PUBLISHED / REJECTED) est hors scope de cette spec ; elle sera couverte par la spec 007 (backoffice de modération). Pour cette spec, le statut est défini via fixtures ou en base directement.
- **FR-015**: Le formulaire d'administration pour la création/édition de livres et l'upload d'images est hors scope ; couvert par la spec 007 (backoffice de modération). Les données de test sont créées exclusivement via fixtures Doctrine (Foundry). VichUploaderBundle est configuré dans cette spec pour le stockage (mapping filesystem local, répertoire de destination à définir en implémentation), mais aucune interface d'upload utilisateur n'est implémentée.
- **FR-016**: La page détail DOIT exposer les métadonnées SEO suivantes : balise `<title>` (ex : "{title} — La Collection"), balise `<meta name="description">`, et balises Open Graph `og:title` et `og:image` (URL absolue de `coverImage` ou du placeholder SVG si absent).
- **FR-011**: Le système DOIT exclure la section "Ma note personnelle" (boucliers/étoiles).
- **FR-012**: La page DOIT être conforme au design system "vieux grimoire" (couleurs, polices, espacements définis dans `design/pages/livre.html`).
- **FR-013**: Les champs nullable DOIVENT être gérés gracieusement sans erreur d'affichage. Règles de masquage :
  - Dans le tableau "Fiche Technique" : toute ligne dont la valeur est null **ou tableau vide** (`languages: []`) est masquée (row absent du DOM). Règle unifiée pour tous les champs nullable et `languages`.
  - `saga` et `volumeNumber` null simultanément : ces éléments sont masqués du header (voir FR-004).
  - `coverImage` absent : un placeholder SVG statique défini dans `design/pages/livre.html` est affiché ; son attribut `alt` DOIT contenir le titre français du livre.
  - Onglet galerie sans `BookImage` : onglet masqué (voir FR-007 pour l'exception galerie vide).

### Key Entities

- **Book**: Représente une édition d'une œuvre de littérature interactive. Pivot central reliant auteurs, éditeur, traducteur, illustrateurs et futures collections membres.
- **Author**: Créateur de l'œuvre (ex : Steve Jackson, Ian Livingstone). Champs : `firstName` (string), `lastName` (string), `slug` (string, unique, auto-généré). Relation ManyToMany avec Book.
- **Illustrator**: Artiste responsable des illustrations intérieures et/ou de couverture. Champs : `firstName` (string), `lastName` (string), `slug` (string, unique, auto-généré). Relation ManyToMany avec Book.
- **Translator**: Traducteur de l'édition française. Champs : `firstName` (string), `lastName` (string), `slug` (string, unique, auto-généré). Relation ManyToOne avec Book (nullable).
- **Editor**: Maison d'édition (ex : Gallimard Jeunesse). Champs : `name` (string), `slug` (string, unique, auto-généré). Relation ManyToOne avec Book.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Toutes les données éditoriales d'un livre publié sont accessibles depuis une URL unique en moins de 2 secondes (p95, cache chaud, conditions normales de charge).
- **SC-002**: 100% des champs définis dans FR-001 et FR-002 sont persistés et restitués sans perte de données.
- **SC-003**: La page respecte visuellement le design system "vieux grimoire" — validé par revue visuelle sur `design/pages/livre.html`.
- **SC-004**: Un livre au statut PENDING ou REJECTED n'est pas accessible aux visiteurs non privilégiés (retour 404).
- **SC-005**: Le lien partenaire "La Taverne" est fonctionnel et s'ouvre dans un nouvel onglet sur 100% des fiches publiées.

## Assumptions

- Le slug URL du livre est auto-généré via Gedmo Sluggable (StofDoctrineExtensionsBundle) depuis le champ `title`. En cas de collision, un suffix basé sur les premiers chiffres de l'ISBN est ajouté (ex : `sorciere-des-neiges-2070368`). L'ISBN étant unique par édition, ce slug est stable et déterministe. **Prérequis** : StofDoctrineExtensionsBundle doit être installé et configuré avant l'implémentation de cette spec (vérifier présence dans `composer.json`).
- Les entités `Author`, `Illustrator`, `Translator`, `Editor` sont créées dans cette spec ; elles n'existent pas encore en base. Leurs migrations Doctrine sont à générer dans cette spec.
- `galleryImages` est stocké via une entité liée `BookImage` (relation OneToMany depuis `Book`), chaque entité ayant les champs `tab` (enum: Tome, Dos, Tranche, Pages, Carte) et `imagePath` géré par VichUploaderBundle (stockage filesystem local).
- L'URL de "La Taverne des Aventuriers" est lue depuis la variable d'environnement `TAVERNE_URL` (pas un champ par livre). À documenter dans `.env.example`.
- La gestion des permissions d'accès aux fiches PENDING/REJECTED s'appuie sur le système RBAC déjà défini dans la spec 004. **Dépendance bloquante** : spec 004 doit être mergée avant d'implémenter FR-003.
- Le support mobile est dans le scope (le design system est responsive ; les breakpoints sont définis dans `design/pages/livre.html`).
- La langue d'affichage par défaut est le français ; le champ `languages` (JSON array de codes ISO) prépare une future internationalisation sans l'implémenter ici.
- Les boutons d'action ("Ma Collection", "À lire", etc.) sont rendus uniquement pour les utilisateurs authentifiés, sans handler fonctionnel — leur logique est réservée à la spec 006 (gestion de collection membre).
