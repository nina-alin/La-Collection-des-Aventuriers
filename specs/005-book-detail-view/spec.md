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
2. **Given** un livre publié en base, **When** l'utilisateur consulte le tableau fiche technique, **Then** il voit ISBN, pages, paragraphes, année de parution française, année originale, tirage, auteurs, illustrateurs, traducteur et éditeur.
3. **Given** un livre non publié (PENDING ou REJECTED), **When** un visiteur non admin accède à l'URL, **Then** la page retourne une erreur 404.

---

### User Story 2 - Parcourir la galerie d'images (Priority: P2)

L'utilisateur peut naviguer entre les vues de l'objet livre (Tome, Dos, Tranche, Pages, Carte) via un système d'onglets sur la fiche.

**Why this priority**: Enrichit l'expérience de consultation sans bloquer le MVP de la fiche.

**Independent Test**: Cliquer chaque onglet de la galerie → l'image correspondante s'affiche, l'onglet actif est visuellement distingué.

**Acceptance Scenarios**:

1. **Given** un livre avec images de galerie, **When** l'utilisateur clique sur l'onglet "Dos", **Then** l'image du dos du livre s'affiche à la place de la couverture.
2. **Given** un livre sans image pour un onglet donné, **When** l'utilisateur clique sur cet onglet, **Then** un placeholder visuel cohérent avec le design system est affiché.

---

### User Story 3 - Accéder au lien partenaire "La Taverne" (Priority: P3)

L'utilisateur voit un bouton "En discuter sur la Taverne des Aventuriers" pointant vers le forum partenaire, affirmant la complémentarité des deux sites.

**Why this priority**: Engagement partenaire contractuel ; visible sans nécessiter d'authentification.

**Independent Test**: Cliquer le bouton Taverne → ouvre le lien externe dans un nouvel onglet.

**Acceptance Scenarios**:

1. **Given** n'importe quelle fiche livre publiée, **When** l'utilisateur clique "En discuter sur la Taverne", **Then** le lien partenaire s'ouvre dans un nouvel onglet navigateur.

---

### User Story 4 - Préparer les actions de collection (Priority: P4)

La barre d'action interactive affiche les boutons "Ma Collection", "À lire", "À acheter" et "Favori" comme ancres visuelles prêtes pour les futures specs de gestion de collection.

**Why this priority**: Prépare l'intégration collection sans l'implémenter — doit être présent visuellement dès le MVP de la fiche.

**Independent Test**: La barre d'action est visible sur toute fiche publiée avec les 4 boutons rendus dans le design system ; les boutons ne déclenchent pas d'action fonctionnelle pour l'instant.

**Acceptance Scenarios**:

1. **Given** un livre publié, **When** l'utilisateur consulte la fiche, **Then** les 4 boutons d'action sont visibles et stylisés conformément au design system.

---

### Edge Cases

- Que se passe-t-il si un livre n'a pas de traducteur (livre en langue originale française) ?
- Comment afficher un livre sans résumé renseigné ?
- Que se passe-t-il si `volumeNumber` est absent (œuvre hors saga) ?
- Comment gérer un livre avec plusieurs auteurs dans le header et le tableau fiche technique ?
- Que se passe-t-il si `coverImage` est absent ?

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Le système DOIT exposer une entité `Book` avec les champs : `title`, `originalTitle`, `isbn`, `pages`, `paragraphs`, `frenchPublicationYear`, `originalPublicationYear`, `editionInfo`, `saga`, `volumeNumber`, `summary`, `coverImage`, `galleryImages`, `status`, `languages`.
- **FR-002**: L'entité `Book` DOIT comporter des relations vers les entités `Author` (ManyToMany), `Illustrator` (ManyToMany), `Translator` (ManyToOne, nullable), et `Editor` (ManyToOne).
- **FR-003**: Le statut `status` DOIT supporter les valeurs PENDING, PUBLISHED, REJECTED ; seules les fiches PUBLISHED sont visibles aux visiteurs non privilégiés.
- **FR-004**: La page détail DOIT afficher le header avec badge de collection, numéro de volume, titre français et titre original.
- **FR-005**: La page détail DOIT afficher un tableau "Fiche Technique" listant l'ensemble des métadonnées éditoriales.
- **FR-006**: La page détail DOIT afficher le "Résumé du Grimoire" avec lettrine si le design system la prévoit.
- **FR-007**: La page détail DOIT intégrer un système d'onglets (Tome, Dos, Tranche, Pages, Carte) pour la galerie d'images.
- **FR-008**: La page détail DOIT afficher un bloc "En discuter sur la Taverne des Aventuriers" avec lien externe s'ouvrant dans un nouvel onglet.
- **FR-009**: La barre d'action DOIT afficher les boutons "Ma Collection", "À lire", "À acheter" et "Favori" sans comportement fonctionnel actif (ancres pour futures specs).
- **FR-010**: Le système DOIT exclure le bloc "La Communauté", les commentaires, les avatars des membres et les moyennes de notes globales.
- **FR-011**: Le système DOIT exclure la section "Ma note personnelle" (boucliers/étoiles).
- **FR-012**: La page DOIT être conforme au design system "vieux grimoire" (couleurs, polices, espacements définis dans `design/pages/livre.html`).
- **FR-013**: Les champs nullable (`translator`, `volumeNumber`, `saga`, `galleryImages` individuels) DOIVENT être gérés gracieusement sans erreur d'affichage.

### Key Entities

- **Book**: Représente une édition d'une œuvre de littérature interactive. Pivot central reliant auteurs, éditeur, traducteur, illustrateurs et futures collections membres.
- **Author**: Créateur de l'œuvre (ex : Steve Jackson, Ian Livingstone). Relation ManyToMany avec Book.
- **Illustrator**: Artiste responsable des illustrations intérieures et/ou de couverture. Relation ManyToMany avec Book.
- **Translator**: Traducteur de l'édition française. Relation ManyToOne avec Book (nullable).
- **Editor**: Maison d'édition (ex : Gallimard Jeunesse). Relation ManyToOne avec Book.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Toutes les données éditoriales d'un livre publié sont accessibles depuis une URL unique en moins de 2 secondes.
- **SC-002**: 100% des champs définis dans FR-001 et FR-002 sont persistés et restitués sans perte de données.
- **SC-003**: La page respecte visuellement le design system "vieux grimoire" — validé par revue visuelle sur `design/pages/livre.html`.
- **SC-004**: Un livre au statut PENDING ou REJECTED n'est pas accessible aux visiteurs non privilégiés (retour 404).
- **SC-005**: Le lien partenaire "La Taverne" est fonctionnel et s'ouvre dans un nouvel onglet sur 100% des fiches publiées.

## Assumptions

- Le slug URL du livre est dérivé du titre français (slugification standard).
- Les entités `Author`, `Illustrator`, `Translator`, `Editor` sont créées dans cette spec ; elles n'existent pas encore en base.
- `galleryImages` est stocké comme une collection ordonnée (tableau JSON ou entité liée), chaque entrée associée à un onglet nommé.
- Le lien vers "La Taverne des Aventuriers" est une valeur de configuration (paramètre d'application), pas un champ par livre.
- La gestion des permissions d'accès aux fiches PENDING/REJECTED s'appuie sur le système RBAC déjà défini dans la spec 004.
- Le support mobile est dans le scope (le design system est responsive).
- La langue d'affichage par défaut est le français ; le champ `languages` prépare une future internationalisation sans l'implémenter ici.
- Les boutons d'action ("Ma Collection", "À lire", etc.) sont rendus mais non fonctionnels — leur logique est réservée à une spec future sur l'espace membre.
