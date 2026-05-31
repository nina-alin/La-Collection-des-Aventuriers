# Feature Specification: Composant Recherche Globale (Omnibox)

**Feature Branch**: `012-omnibox-global-search`

**Created**: 2026-05-31

**Status**: Draft

**Input**: User description: "Composant Recherche Globale (Omnibox) — champ de saisie avec panneau déroulant contextuel, suggestions récentes, entités fréquentes, navigation clavier, badges de type."

---

## Clarifications

### Session 2026-05-31

- Q: Mécanisme de persistance de l'historique des recherches récentes → A: Session uniquement (in-memory) — aucun localStorage ni API backend ; l'historique disparaît au rechargement.
- Q: État du panneau pendant la frappe (sections pré-saisie vs résultats dynamiques) → A: Remplacement total — dès le premier caractère saisi, les sections "Recherches Récentes" et "Souvent Consultés" disparaissent et seuls les résultats filtrés s'affichent.
- Q: Comportement lors d'une indisponibilité de la source de données (API recherche ou "Souvent Consultés") → A: Dégradation silencieuse — la section défaillante est masquée, le reste du panneau fonctionne normalement sans message d'erreur.
- Q: Nombre maximum de résultats affichés dans le panneau de recherche dynamique → A: 5 à 8 résultats maximum (équilibre lisibilité/couverture, cohérent avec le plafond de 4 items "Souvent Consultés").
- Q: Stratégie de déclenchement de la recherche lors de la saisie → A: Debounce 300ms — la recherche se déclenche 300ms après la dernière frappe (réduit le volume d'appels API, compatible avec SC-003 ≤500ms).
- Q: État visuel pendant le chargement des résultats de recherche → A: Skeleton placeholders dans le panneau — simule la structure des résultats (indicateur visuel, titre, métadonnées, badge) sans perturber le layout.
- Q: Pattern URL de navigation vers les fiches d'entités depuis les résultats → A: Slug-based — `/livre/:slug`, `/collection/:slug`, `/auteur/:slug`.

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Recherche par frappe (Priority: P1)

Un utilisateur connecté clique sur le champ de recherche dans la barre de navigation, voit un panneau s'ouvrir avec ses recherches récentes et ses entités souvent consultées, tape quelques lettres et obtient des suggestions filtrées (livres, collections, auteurs). Il sélectionne un résultat et est redirigé vers la fiche correspondante.

**Why this priority**: C'est le flux principal et la valeur centrale du composant. Sans cette fonctionnalité, le composant n'existe pas.

**Independent Test**: Ouvrir l'application, cliquer sur le champ de recherche, taper "Steve", sélectionner un auteur → page auteur s'affiche.

**Acceptance Scenarios**:

1. **Given** la barre de navigation est visible, **When** l'utilisateur clique sur le champ de recherche, **Then** un panneau déroulant s'ouvre avec les sections "Recherches Récentes" et "Souvent Consultés".
2. **Given** le panneau est ouvert, **When** l'utilisateur tape au moins 1 caractère, **Then** des suggestions filtrées s'affichent (livres, collections, auteurs correspondants).
3. **Given** des résultats sont affichés, **When** l'utilisateur clique sur un item, **Then** il est redirigé vers la fiche de l'entité sélectionnée.
4. **Given** l'utilisateur saisit du texte, **When** il appuie sur Entrée sans sélection active, **Then** une recherche globale est lancée.

---

### User Story 2 - Suggestions pré-saisie contextuelles (Priority: P2)

Avant même que l'utilisateur ne tape quoi que ce soit, le panneau propose des raccourcis utiles : ses dernières recherches et les entités qu'il consulte le plus souvent.

**Why this priority**: Accélère la navigation pour les utilisateurs récurrents. Réduit les frappes nécessaires pour retrouver du contenu familier.

**Independent Test**: Ouvrir le panneau sans taper → les deux sections "Recherches Récentes" et "Souvent Consultés" apparaissent avec des données réelles ou un état vide élégant.

**Acceptance Scenarios**:

1. **Given** l'utilisateur a effectué des recherches précédentes, **When** le panneau s'ouvre, **Then** la section "Recherches Récentes" affiche l'historique avec icône horloge et compteur.
2. **Given** l'utilisateur n'a pas d'historique, **When** le panneau s'ouvre, **Then** la section "Recherches Récentes" affiche un état vide (ou est masquée).
3. **Given** le panneau est ouvert, **When** aucun texte n'est saisi, **Then** la section "Souvent Consultés" affiche jusqu'à 4 entités avec leur badge de type et leurs métadonnées.

---

### User Story 3 - Navigation entièrement au clavier (Priority: P3)

Un utilisateur power-user navigue dans le panneau de recherche sans utiliser la souris : flèches pour parcourir les résultats, Entrée pour valider, Échap pour fermer.

**Why this priority**: Exigence d'accessibilité et d'efficacité pour utilisateurs avancés et lecteurs d'écran.

**Independent Test**: Ouvrir le panneau via Tab/focus clavier, naviguer avec ↑↓, valider avec Entrée, fermer avec Échap — sans toucher la souris.

**Acceptance Scenarios**:

1. **Given** le panneau est ouvert, **When** l'utilisateur appuie sur ↓, **Then** le focus se déplace sur le premier item de résultat.
2. **Given** un item est en focus, **When** l'utilisateur appuie sur ↑/↓, **Then** le focus se déplace à l'item précédent/suivant.
3. **Given** un item est en focus, **When** l'utilisateur appuie sur Entrée, **Then** il est redirigé vers la fiche de l'entité.
4. **Given** le panneau est ouvert, **When** l'utilisateur appuie sur Échap, **Then** le panneau se ferme et le focus retourne au champ de saisie.
5. **Given** le panneau est ouvert, **When** l'utilisateur clique en dehors du panneau, **Then** le panneau se ferme.

---

### User Story 4 - Accès à la recherche avancée (Priority: P4)

L'utilisateur, en bas du panneau, trouve un lien fixe vers la vue Catalogue pour affiner sa recherche avec des filtres avancés.

**Why this priority**: Porte de sortie indispensable quand l'omnibox ne suffit pas.

**Independent Test**: Ouvrir le panneau → le lien "Recherche avancée dans le Catalogue →" est visible en pied de panneau et redirige vers `/catalogue`.

**Acceptance Scenarios**:

1. **Given** le panneau est ouvert, **When** l'utilisateur fait défiler vers le bas, **Then** le lien "Recherche avancée dans le Catalogue →" reste ancré en bas.
2. **Given** le lien est cliqué, **When** la navigation s'effectue, **Then** l'utilisateur arrive sur la vue Catalogue.

---

### Edge Cases

- Que se passe-t-il si l'utilisateur efface tout le texte saisi ? → Le panneau revient à l'état pré-saisie (récents + souvent consultés).
- Que se passe-t-il si aucun résultat ne correspond à la recherche ? → Un état "Aucun résultat" est affiché avec un message clair.
- Que se passe-t-il si les métadonnées d'une entité sont trop longues ? → Troncature par ellipsis (...) sur une seule ligne.
- Que se passe-t-il si l'utilisateur n'a ni historique ni entités consultées ? → Les sections affichent un état vide ou sont masquées proprement.
- Que se passe-t-il si l'API de recherche ou la source "Souvent Consultés" est indisponible ? → Dégradation silencieuse : la section défaillante est masquée, aucun message d'erreur, le reste du panneau reste fonctionnel.
- Que se passe-t-il sur mobile (écran < 720px) ? → Le panneau prend toute la largeur disponible sous la barre de navigation.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Le champ de recherche DOIT afficher le placeholder "Rechercher un livre, un auteur, une collection…" à l'état par défaut.
- **FR-002**: Au focus (clic ou Tab clavier) sur le champ, le panneau déroulant DOIT s'ouvrir.
- **FR-003**: Le panneau DOIT afficher une section "Recherches Récentes" avec les requêtes saisies durant la session de navigation en cours, chaque item précédé d'une icône horloge. (Politique FIFO, plafond et déduplication : voir FR-023.)
- **FR-004**: La section "Recherches Récentes" DOIT afficher un compteur du nombre de requêtes mémorisées dans la session courante, au format `(N)` en regard du titre de section.
- **FR-005**: Le panneau DOIT afficher une section "Souvent Consultés" avec les entités les plus populaires de la plateforme (proxy : nombre de critiques pour les Livres, nombre de tomes pour les Collections, nombre de fiches pour les Auteurs — voir research.md §1). Maximum 4 items affichés.
- **FR-006**: La section "Souvent Consultés" DOIT afficher un compteur du nombre total d'entités suggérées, au format `(N)` en regard du titre de section.
- **FR-007**: Le panneau DOIT afficher un en-tête avec l'instruction "COMMENCE À ÉCRIRE_" et un guide de navigation clavier (touches ↑↓ et ESC). Cet en-tête n'est visible qu'en état pré-saisie ; il est masqué dès le premier caractère saisi.
- **FR-008**: Chaque item de résultat DOIT afficher : un indicateur visuel (gauche, 40×40px), un titre en gras, des métadonnées secondaires tronquées sur une seule ligne avec ellipsis, et un badge de type (droite).
- **FR-009**: L'indicateur visuel DOIT être : miniature couverture (40×40px) ou icône livre générique si aucune couverture disponible, pour un Livre ; icône rayonnage pour une Collection ; photo de profil si disponible, sinon avatar avec les 2 premières initiales du nom complet sur fond généré automatiquement, pour un Auteur.
- **FR-010**: Les métadonnées DOIT suivre le schéma par type :
  - Livre : `[Référence] - [Année] - [Auteur/Éditeur]`
  - Collection : `collection - [N] tomes - [Auteur principal]`
  - Auteur : `auteur - [N] fiches`
- **FR-011**: Le badge de type DOIT indiquer visuellement le type avec une couleur distinctive : rouge/orange pour LIVRE, vert pour COLLECTION, bleu pour AUTEUR.
- **FR-012**: La navigation clavier DOIT être entièrement fonctionnelle : ↑/↓ pour déplacer le focus visuel sur tous les items visibles du panneau (sections pré-saisie et résultats dynamiques inclus), Entrée pour valider, Échap pour fermer. Le focus clavier reste dans le champ de saisie ; le déplacement est géré via `aria-activedescendant` (pattern ARIA combobox). Le lien "Recherche avancée dans le Catalogue →" est atteignable via Tab uniquement, pas via ↑↓.
- **FR-013**: Un indicateur "ESC FERMER" DOIT être visible dans le bas du panneau.
- **FR-014**: Un lien "Recherche avancée dans le Catalogue →" DOIT être ancré en bas du panneau et pointer vers la vue Catalogue.
- **FR-015**: Le panneau DOIT se fermer lorsqu'un clic est effectué en dehors de lui.
- **FR-016**: Le design visuel DOIT suivre fidèlement les fichiers CSS du dossier `design/` (notamment `design/assets/search.css` et les classes `.sh-search`, `.search-dropdown`, `.search-result`, etc.).
- **FR-017**: Dès le premier caractère saisi, les sections "Recherches Récentes" et "Souvent Consultés" DOIVENT être masquées et remplacées intégralement par les résultats de recherche dynamiques. Lorsque le champ est vidé, le panneau DOIT revenir à l'état pré-saisie (sections récents + souvent consultés).
- **FR-018**: Le panneau de résultats dynamiques DOIT afficher un maximum de 8 résultats (toutes entités confondues). En présence de résultats mixtes (Livres, Collections, Auteurs), la limite totale reste 8 items.
- **FR-019**: La recherche dynamique DOIT être déclenchée avec un debounce de 300ms après la dernière frappe. Aucun appel n'est émis pendant la saisie continue ; la requête part 300ms après l'arrêt de frappe. Toute requête en cours DOIT être annulée si l'utilisateur effectue une nouvelle frappe avant la réponse.
- **FR-020**: Pendant le chargement des résultats (entre déclenchement de la requête et réponse), le panneau DOIT afficher des skeleton placeholders reproduisant la structure d'un item de résultat (indicateur visuel, titre, métadonnées, badge) — typiquement 3 skeletons animés.
- **FR-021**: Les liens de navigation des résultats DOIVENT utiliser des slugs : `/livre/:slug` pour un Livre, `/collection/:slug` pour une Collection, `/auteur/:slug` pour un Auteur. Chaque entité retournée par l'API DOIT exposer tous les champs définis dans la section Key Entities.
- **FR-022**: Lorsque l'utilisateur appuie sur Entrée sans item sélectionné, le composant navigue vers `/catalogue?q=:query` où `:query` est la valeur courante du champ de saisie.
- **FR-023**: L'historique de session suit une politique FIFO : la 6e requête distincte évince la plus ancienne. Une requête identique à une entrée existante ne crée pas de doublon mais déplace l'entrée en tête de liste. Le panneau affiche au maximum 5 items (identique au plafond de session).
- **FR-024**: Lorsque la section "Souvent Consultés" est indisponible, elle est masquée sans message. Si les deux sections pré-saisie sont vides ou indisponibles simultanément, le panneau s'affiche avec uniquement le lien "Recherche avancée dans le Catalogue →" visible.

### Non-Functional Requirements

- **NFR-001**: Le composant DOIT être conforme WCAG 2.1 niveau AA.
- **NFR-002**: Rôles ARIA obligatoires — `role="combobox"` sur le champ de saisie avec `aria-expanded` et `aria-controls` ; `role="listbox"` sur la liste de résultats ; `role="option"` sur chaque item ; `aria-activedescendant` mis à jour lors de la navigation ↑↓.
- **NFR-003**: Un `aria-live="polite"` DOIT annoncer le nombre de résultats aux lecteurs d'écran lors de chaque mise à jour de la liste.
- **NFR-004**: Le timeout de requête API est fixé à 5 000ms. Au-delà, la dégradation silencieuse s'applique (section masquée, reste fonctionnel).
- **NFR-005**: SC-002 se mesure depuis l'événement `focus` sur le champ jusqu'au premier paint du panneau. SC-003 se mesure depuis la réception de la réponse API jusqu'au rendu des résultats.

### Key Entities

- **Livre** : Entité catalogue principal. Attributs pertinents : slug, titre, référence/code, année de parution, auteur(s), éditeur, miniature de couverture.
- **Collection** : Regroupement de livres liés. Attributs : slug, titre, nombre de tomes, auteur principal.
- **Auteur** : Contributeur de type créateur. Attributs : slug, nom complet, initiales (pour avatar), nombre de fiches liées, photo de profil (optionnelle).
- **Requête récente** : Chaîne de texte saisie lors d'une recherche dans la session de navigation courante. Non persistée — disparaît à la fermeture ou au rechargement de la page.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un utilisateur trouve et navigue vers une entité en moins de 10 secondes depuis le focus sur le champ de recherche.
- **SC-002**: Le panneau s'ouvre et affiche les suggestions pré-saisie en moins de 300ms après le focus.
- **SC-003**: Les résultats de recherche apparaissent en moins de 500ms après déclenchement de la requête (soit ≤800ms après la dernière frappe en comptant le debounce de 300ms).
- **SC-004**: 100% des interactions du composant sont réalisables sans souris (accessibilité clavier complète vérifiable manuellement).
- **SC-005**: Le composant fonctionne correctement sur desktop (≥720px) et mobile (<720px) avec une mise en page adaptée.
- **SC-006**: Les métadonnées longues ne débordent jamais hors de leur zone allouée (troncature ellipsis).

---

## Assumptions

- L'historique des recherches récentes est une mémoire de session uniquement (in-memory, non persistée) : il disparaît à la fermeture ou au rechargement de la page. Aucun stockage localStorage ni API backend n'est requis pour cette fonctionnalité.
- Les "Souvent Consultés" sont calculés depuis les données de navigation disponibles. En l'absence de données utilisateur, les entités globalement populaires de la plateforme sont utilisées.
- Le composant est intégré dans la barre de navigation globale déjà existante (`<form class="sh-search">`), telle que définie dans les fichiers de design.
- La vue "Catalogue" vers laquelle pointe le lien de pied de panneau existe déjà dans l'application.
- La recherche active (lors de la frappe) est branchée sur une API ou un mécanisme de filtrage existant — le composant ne définit pas l'API backend.
- Sur mobile (<720px), le panneau adopte un positionnement fixe pleine largeur sous la barre de navigation, comme défini dans le CSS design.
