# Feature Specification: Salle de Modération — Intégration du Design

**Feature Branch**: `023-moderation-room-design`

**Created**: 2026-06-08

**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Comparaison diff et validation d'une suggestion (Priority: P1)

Un modérateur consulte la suggestion en tête de file et visualise côte à côte les données actuelles de la fiche et les données proposées. Il identifie rapidement ce qui a changé grâce aux surbrillances diff. Il valide la suggestion d'un clic, ce qui fusionne les données en base et charge automatiquement la suggestion suivante.

**Why this priority**: C'est l'action quotidienne centrale du modérateur. Tout le reste tourne autour de ce flux principal.

**Independent Test**: On peut ouvrir la page avec une suggestion PENDING en base, vérifier que le comparateur s'affiche et que le bouton "Valider" déclenche l'approbation et charge la suivante.

**Acceptance Scenarios**:

1. **Given** il existe au moins une suggestion PENDING, **When** le modérateur ouvre `/moderation`, **Then** le comparateur affiche les données actuelles à gauche et les données proposées à droite, avec les champs modifiés mis en évidence.
2. **Given** le comparateur affiche une suggestion, **When** un champ textuel long (ex: résumé) est modifié, **Then** les mots ajoutés apparaissent soulignés en vert et les mots supprimés barrés en rouge (diff mot à mot, pas de remplacement du champ entier).
3. **Given** le comparateur affiche une suggestion, **When** le modérateur clique "Valider", **Then** la suggestion est approuvée, les données sont fusionnées en base, et la suggestion suivante de la file est chargée automatiquement sans rechargement de page.
4. **Given** plusieurs suggestions en file, **When** le modérateur clique sur une entrée du panneau latéral "La Suite", **Then** le comparateur se met à jour pour afficher la suggestion cliquée.
5. **Given** le modérateur fait défiler la page vers le bas, **When** la page scrolle au-delà du comparateur, **Then** le panneau latéral de la file d'attente reste fixé à l'écran (position sticky).

---

### User Story 2 — Refus d'une suggestion avec motif (Priority: P2)

Le modérateur refuse une suggestion en choisissant un motif prédéfini (ou en saisissant un motif libre) dans une modale de confirmation. Le motif est enregistré et consultable ultérieurement.

**Why this priority**: Le refus est aussi fréquent que la validation, et le motif est essentiel pour la communication avec le contributeur.

**Independent Test**: Ouvrir la modale de refus, choisir un motif ou saisir "Autre", soumettre, vérifier que la suggestion passe au statut REFUSED avec le motif enregistré.

**Acceptance Scenarios**:

1. **Given** le comparateur affiche une suggestion, **When** le modérateur clique "Refuser", **Then** une modale s'ouvre avec le texte "Êtes-vous sûr de vouloir refuser cette suggestion ?", un menu déroulant de raisons prédéfinies, et les boutons "Annuler" / "Confirmer".
2. **Given** la modale de refus est ouverte, **When** le modérateur sélectionne "Autre" dans le menu déroulant, **Then** une zone de texte obligatoire apparaît pour saisir un motif libre.
3. **Given** la modale de refus est ouverte avec "Autre" sélectionné et le textarea vide, **When** le modérateur soumettre, **Then** la soumission est bloquée et un message d'erreur de validation est affiché.
4. **Given** un motif valide est saisi, **When** le modérateur confirme le refus, **Then** la suggestion passe au statut REFUSED, le motif est persisté, et la suggestion suivante de la file est chargée.

---

### User Story 3 — Vue Tableau (mode condensé) (Priority: P3)

Le modérateur peut basculer la section "Flux de traitement" en mode tableau compact pour traiter les suggestions en série (notamment les suggestions de type "Express" — typos, dates, ISBN).

**Why this priority**: Accélère le traitement des lots de corrections mineures sans avoir besoin du comparateur détaillé.

**Independent Test**: Cliquer le bouton "Vue Tableau", vérifier que les suggestions s'affichent en liste tabulaire avec filtres et actions par ligne. Cliquer l'œil sur une ligne doit repasser en Vue Flux et charger cette suggestion.

**Acceptance Scenarios**:

1. **Given** le modérateur est en Vue Flux, **When** il clique le bouton "Vue Tableau" (en haut à droite de la section), **Then** le comparateur détaillé est remplacé par un tableau compact listant les suggestions PENDING avec colonnes : nom, type, priorité, délai, actions.
2. **Given** le tableau est affiché, **When** le modérateur clique le filtre "Express", **Then** seules les suggestions de priorité EXPRESS sont affichées.
3. **Given** le tableau est affiché, **When** le modérateur clique le bouton "coche" d'une ligne, **Then** la suggestion est validée immédiatement (même comportement que le bouton Valider de la Vue Flux).
4. **Given** le tableau est affiché, **When** le modérateur clique le bouton "œil" d'une ligne, **Then** la vue repasse en mode Flux et le comparateur charge cette suggestion spécifique.

---

### User Story 4 — En-tête de page et indicateurs de contexte (Priority: P4)

Le modérateur voit en permanence la date du jour, le nombre de suggestions en attente et le badge "VUE MODÉRATEUR" pour contextualiser son travail.

**Why this priority**: Contexte visuel important mais purement informatif, sans impact sur la logique métier.

**Independent Test**: Vérifier que le nombre affiché correspond au `COUNT` en base des suggestions PENDING, que la date est correcte, et que le badge s'affiche.

**Acceptance Scenarios**:

1. **Given** le modérateur charge la page, **When** il consulte l'en-tête, **Then** il voit la date du jour, le nombre exact de suggestions PENDING, et le badge "VUE MODÉRATEUR".
2. **Given** une suggestion vient d'être traitée, **When** la page recharge, **Then** le compteur en-tête décrémente d'une unité.

---

### User Story 5 — Gestion globale des fiches (Priority: P5)

Le modérateur peut parcourir, rechercher, modifier et supprimer/dépublier toutes les fiches de la base (toutes entités confondues) depuis la section "Gestion globale".

**Why this priority**: Outil de maintenance moins fréquent mais nécessaire pour intervenir hors du flux de suggestions.

**Independent Test**: Filtrer par type "Livres", vérifier que seuls des livres apparaissent. Cliquer le bouton poubelle d'un brouillon, vérifier que la modale affiche 3 choix. Cliquer l'œil sur une fiche REFUSED, vérifier que le motif de refus s'affiche.

**Acceptance Scenarios**:

1. **Given** la section Gestion globale est visible, **When** le modérateur saisit un terme dans la barre de recherche, **Then** le tableau se filtre dynamiquement en temps réel.
2. **Given** le tableau est affiché, **When** le modérateur clique le filtre "Auteurs", **Then** seules les fiches d'entités de type AUTHOR sont affichées.
3. **Given** une fiche existe dans le tableau, **When** le modérateur clique le bouton crayon, **Then** il est redirigé vers la page de modification de cette fiche (lien `#` pour l'instant).
4. **Given** une fiche est affichée, **When** le modérateur clique le bouton poubelle, **Then** une modale s'ouvre avec le texte "Êtes-vous sûr de vouloir supprimer définitivement cette fiche ?" et trois boutons : "Supprimer" (destructeur), "Dépublier" (passe en brouillon), "Annuler".
5. **Given** une fiche a le statut REFUSED, **When** le modérateur clique le bouton œil sur cette ligne, **Then** une modale en lecture seule affiche le motif de refus enregistré.

---

### Edge Cases

- Que se passe-t-il si la file de suggestions est vide quand le modérateur valide la dernière ? Le comparateur doit afficher un état vide/célébration sans crash.
- Que se passe-t-il si la suggestion n'a pas d'entité source (nouvelle fiche) ? Le comparateur doit afficher la colonne gauche vide (toutes valeurs "AJOUTÉ").
- Que se passe-t-il si le champ `formData` de la suggestion contient des champs inconnus ? Ils doivent s'afficher sans erreur (affichage générique).
- Un modérateur ne peut pas valider deux fois la même suggestion (idempotence côté serveur).
- Si le fetch d'approbation ou de refus échoue (réseau, 4xx, 5xx), un toast d'erreur s'affiche, les boutons se réactivent, et la suggestion reste chargée dans le comparateur sans perte d'état.

## Requirements *(mandatory)*

### Functional Requirements

**En-tête et indicateurs**

- **FR-001**: La page DOIT afficher la date du jour, le nombre de suggestions PENDING en base, et un badge "VUE MODÉRATEUR" dans l'en-tête de section.
- **FR-002**: Le bouton de bascule "Vue Tableau" / "Vue Flux" DOIT être situé en haut à droite de la section "Flux de traitement". Lorsque la file de suggestions PENDING est vide, le bouton "Vue Tableau" DOIT être désactivé (disabled) ; l'utilisateur reste en Vue Flux avec l'état vide/célébration.

**Moteur de comparaison (diff)**

- **FR-003**: Le système DOIT comparer chaque champ de `formData` de la suggestion avec les données actuelles de l'entité source pour produire un diff structuré avec les statuts : AJOUTÉ, SUPPRIMÉ, REMPLACÉ, INCHANGÉ.
- **FR-004**: Pour les champs texte longs (résumé, description), le diff DOIT opérer au niveau des mots (word-level diff) côté serveur via un service PHP (ex: `jfcherng/php-diff`) et non un simple remplacement du champ. Le résultat annoté est transmis à Twig ; aucun recalcul côté client.
- **FR-005**: Le diff DOIT calculer et retourner le total d'ajouts, remplacements et suppressions pour alimenter le compteur de la barre d'actions.
- **FR-006**: Pour une suggestion de type NEW_ENTRY (sans entité source), tous les champs proposés DOIVENT être marqués AJOUTÉ.

**Rendu visuel du comparateur**

- **FR-007**: Le comparateur DOIT afficher deux colonnes côte à côte : "DONNÉES ACTUELLES" (gauche) et "NOUVELLES DONNÉES" (droite), conformément au design `design/pages/moderation.html` — classes `.split`, `.split-col.now`, `.split-col.next`.
- **FR-008**: Les champs INCHANGÉS DOIVENT être grisés ou masquables pour focaliser l'attention sur les modifications.
- **FR-009**: Les champs de type AJOUTÉ DOIVENT afficher un badge vert "AJOUTÉ" et la valeur soulignée en vert (classe `.ins`).
- **FR-010**: Les champs REMPLACÉS DOIVENT afficher l'ancienne valeur barrée en rouge (classe `.del`) suivie de la nouvelle valeur soulignée en vert (classe `.ins`).
- **FR-011**: Les champs SUPPRIMÉS DOIVENT afficher la valeur barrée en rouge avec un badge rouge "SUPPRIMÉ".
- **FR-012**: Les champs de type tag/relation DOIVENT afficher les pills avec état visuel : tags retirés estompés et barrés, nouveaux tags avec bordure/fond vert.
- **FR-013**: Sur mobile (< 880 px), le comparateur DOIT afficher des onglets "Actuelles" / "Proposées" à la place des deux colonnes.

**Barre d'actions**

- **FR-014**: La barre d'actions DOIT afficher le compteur de modifications (ex: "+5 ajouts · 3 remplacements · 0 suppression").
- **FR-015**: Le bouton "Modifier" DOIT rediriger vers la page d'édition de la suggestion (lien `#` provisoire).
- **FR-016**: Le bouton "Refuser" DOIT ouvrir une modale avec : texte de confirmation, menu déroulant de raisons prédéfinies, et zone de texte textarea apparaissant uniquement si "Autre" est sélectionné (textarea requis dans ce cas).
- **FR-017**: Le bouton "Valider" DOIT approuver la suggestion immédiatement (sans confirmation supplémentaire), fusionner les données en base via l'action existante, et charger la suggestion suivante de la file (triée par `submittedAt` ASC — FIFO). Pendant le fetch, le bouton est désactivé avec un spinner ; en cas d'échec, un toast d'erreur s'affiche et l'état est restauré.

**Sécurité**

- **FR-030**: Les appels fetch vers `moderation_approve_suggestion` et `moderation_refuse_suggestion` DOIVENT inclure un token CSRF valide généré par `csrf_token()` dans Twig et exposé via `data-csrf-token` sur les boutons d'action.

**Panneau latéral "La Suite"**

- **FR-018**: Le panneau latéral DOIT rester fixé à l'écran lors du scroll (position sticky, top: 80px) sur les écrans ≥ 1100 px conformément à `.flux { grid-template-columns: minmax(0,1fr) 320px }`. Sur les écrans 880px–1099px, le panneau est affiché empilé sous le comparateur (pas de colonne sticky). Sur les écrans < 880px, le panneau est affiché empilé sous le panneau à onglets.
- **FR-019**: Cliquer sur une entrée de la file DOIT mettre à jour le comparateur central pour afficher cette suggestion spécifique.

**Vue Tableau**

- **FR-020**: La Vue Tableau DOIT lister les suggestions PENDING sous forme tabulaire. Le filtre "Toutes" est fonctionnel. Les filtres "Express", "Régulière", "Délicate" sont rendus comme placeholders visuels (hors scope — dérivation de priorité non implémentée dans cette feature).
- **FR-021**: Le bouton "coche" par ligne DOIT valider la suggestion immédiatement (même comportement que FR-017).
- **FR-022**: Le bouton "croix" par ligne DOIT ouvrir la même modale de refus (FR-016).
- **FR-023**: Le bouton "œil" par ligne DOIT repasser en Vue Flux et charger cette suggestion dans le comparateur. Le toggle "Vue Flux" direct (sans "œil") charge la première suggestion PENDING (`submittedAt` ASC).

**Gestion globale**

- **FR-024**: La section "Gestion globale" DOIT afficher un tableau de toutes les fiches (livres, auteurs, illustrateurs, etc.) avec colonnes : nom, type, statut, dernière maj, actions.
- **FR-025**: Le tableau DOIT supporter une recherche textuelle dynamique et des filtres par type d'entité via une requête serveur (route Symfony dédiée ou paramètre de la route existante) ; JS envoie les paramètres de recherche/filtre et remplace le contenu du tableau avec la réponse.
- **FR-026**: Le bouton "Nouvelle fiche" DOIT rediriger vers la page de création (lien `#` provisoire).
- **FR-027**: Le bouton crayon par ligne DOIT rediriger vers la page de modification (lien `#` provisoire).
- **FR-028**: Le bouton poubelle par ligne DOIT ouvrir une modale de confirmation avec 3 options : "Supprimer" (suppression définitive), "Dépublier" (passe en brouillon), "Annuler". Les actions "Supprimer" et "Dépublier" DOIVENT être entièrement fonctionnelles : "Supprimer" supprime définitivement l'entité en base via une route Symfony dédiée (`DELETE /moderation/entities/{type}/{id}`) protégée par CSRF + `ROLE_MODERATOR` ; "Dépublier" passe l'entité au statut brouillon/dépublié via une route dédiée (`PATCH /moderation/entities/{type}/{id}/depublish`) avec les mêmes protections. Les deux actions doivent être couvertes par des tests PHPUnit (CSRF, access control, mutation en base).
- **FR-029**: Un bouton "Voir le motif" (icône info/exclamation) par ligne DOIT n'être visible que sur les fiches au statut REFUSED et ouvrir une modale en lecture seule affichant le motif de refus. *(Note: la maquette utilise une icône info-cercle, pas une icône œil.)*

### Key Entities

- **Suggestion**: Entité existante (`App\Entity\Suggestion`) — champs clés : `id`, `user`, `entityType` (SuggestionEntityType enum), `mode` (SuggestionMode enum), `sourceEntityId`, `formData` (JSON), `status` (SuggestionStatus enum : PENDING/VALIDATED/REFUSED), `submittedAt`, `refusal` (relation vers SuggestionRefusal).
- **SuggestionRefusal**: Entité existante (`App\Entity\SuggestionRefusal`) — stocke le motif de refus.
- **DiffResult** (nouveau DTO PHP): Structure produite par le `DiffService` Symfony à partir de deux `array<string, mixed>` normalisés — liste de champs avec statut (ADDED/REMOVED/REPLACED/UNCHANGED), valeur avant, valeur après, token-level diff annoté pour les champs textuels, compteurs totaux, **label FR du champ** (fourni par le normalizer via sa static map). Passé directement à Twig via le contrôleur. Agnostique au type d'entité source.
- **EntitéSource** (Livre, Auteur, Illustrateur, etc.): Les entités existantes du catalogue dont les données actuelles alimentent la colonne gauche du comparateur.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un modérateur peut traiter une suggestion complète (ouverture + lecture diff + décision) en moins de 60 secondes.
- **SC-002**: Le moteur de diff identifie correctement 100 % des champs modifiés sur l'ensemble des types d'entités (livre, auteur, illustrateur, etc.).
- **SC-003**: Le diff mot à mot sur les champs textuels longs ne marque aucun texte identique comme "ajouté" ou "supprimé".
- **SC-004**: Le passage Vue Flux ↔ Vue Tableau se fait instantanément (aucun rechargement de page).
- **SC-005**: Le panneau latéral reste visible et fixé à tout moment lors du scroll, sur les écrans ≥ 1100 px.
- **SC-006**: La modale de refus bloque la soumission si le motif "Autre" est sélectionné et le textarea vide.
- **SC-007**: La page affiche un état vide explicite (pas de crash) lorsque la file de suggestions est vide.
- **SC-008**: Le design rendu est visuellement conforme à la maquette de référence `design/pages/moderation.html` (classes CSS du design system utilisées).

## Assumptions

- La librairie `diff-match-patch` (ou équivalente) sera installée **côté serveur PHP** (ex: `jfcherng/php-diff` ou package Composer équivalent) pour le diff mot à mot des champs textuels. Le contrôleur calcule le `DiffResult` complet avant de rendre le template Twig ; le JS ne reçoit pas de données brutes d'entité et ne recalcule pas le diff.
- Les données de l'entité source (colonne gauche) sont récupérables à partir de `suggestion.sourceEntityId` et `suggestion.sourceEntityType` via les repositories existants. Chaque type d'entité implémente `EntityNormalizerInterface` (service Symfony taggué) exposant trois méthodes : `normalize(): array<string, mixed>`, `getFieldLabels(): array<string, string>` (clé → label FR), et `getFieldTypes(): array<string, string>` (clé → `'scalar'|'text'|'tags'`). `DiffService::compute()` appelle `getFieldTypes()` pour construire la type map avant d'itérer les champs — les champs `'text'` déclenchent le diff mot-à-mot via `jfcherng/php-diff`. Le `DiffService` résout le bon normalizer via un service locator par clé `entityType` enum, sans switch/match dans le contrôleur. **Ces normalizers n'existent pas encore et doivent être créés dans cette feature** pour les 6 types : BOOK, AUTHOR, ILLUSTRATOR, TRADUCTOR, EDITOR, COLLECTION.
- Les raisons de refus prédéfinies dans le menu déroulant sont : "Données incorrectes", "Source non citée", "Doublon", "Hors périmètre", "Autre".
- La Vue Tableau affiche uniquement les suggestions PENDING (pas l'historique des validées/refusées — c'est la Gestion globale qui liste tout).
- La "priorité" des suggestions (Express, Régulière, Délicate) est **hors scope de cette feature** — les boutons de filtre correspondants dans la Vue Tableau sont des placeholders visuels uniquement. Seul le filtre "Toutes" est fonctionnel.
- La validation et le refus utilisent les routes et actions Symfony existantes (`moderation_approve_suggestion`, `moderation_refuse_suggestion`). Les appels se feront en JavaScript (fetch) pour éviter les rechargements de page. Le token CSRF est généré via `csrf_token()` dans Twig, exposé en `data-csrf-token` sur les boutons d'action, et inclus dans le corps ou l'en-tête du fetch.
- La file de suggestions est triée par `submittedAt` ASC (FIFO) : la suggestion la plus ancienne est toujours traitée en premier. La "suggestion suivante" après validation ou refus est la prochaine dans cet ordre.
- Les pages de création et de modification de fiches n'existant pas encore, les boutons concernés pointent vers `#` provisoirement.
- La section "Vue d'ensemble" (Section I existante dans la maquette : KPIs, historique récent) est hors scope de cette feature — seules les Sections II et III sont à intégrer, plus l'en-tête de page.
- L'accès à `/moderation` est protégé par `#[IsGranted('ROLE_MODERATOR')]` sur le contrôleur (ou `access_control` dans `security.yaml`). Aucun nouveau voter n'est nécessaire.
- Les raccourcis clavier (V/R/S/M) visibles dans la maquette sont **hors scope** — explorations de design uniquement, non à implémenter.
- Les boutons de navigation Précédente/Suivante (flèches ← →) et la fonction "passer sans décision" visibles dans la maquette sont **hors scope** — explorations de design uniquement, non à implémenter.

## Clarifications

### Session 2026-06-08

- Q: Where is the diff computed — client-side JS or server-side Symfony service? → A: Server-side Symfony service — the controller calls a PHP `DiffService`, passes a pre-computed `DiffResult[]` to the Twig template; JS only handles DOM interactions (view toggle, fetch for approve/refuse, sticky panel).
- Q: How is access to `/moderation` protected? → A: Existing role check — `#[IsGranted('ROLE_MODERATOR')]` on the controller (or `access_control` in `security.yaml`); no new voter needed.
- Q: Visual feedback during approve/refuse fetch + on error? → A: Buttons disabled + spinner while request in flight; on failure, toast/alert with error message; suggestion stays loaded (no page reload).
- Q: How is Express/Régulière/Délicate priority derived from SuggestionMode for Vue Tableau filters? → A: Out of scope for this feature — priority filter buttons rendered as UI-only placeholders; only "Toutes" filter is functional.
- Q: How does the controller fetch source entity data across heterogeneous entity types for the diff? → A: Each entity type implements a normalizer (Symfony Serializer or `NormalizableInterface`) returning `array<string, mixed>`; the `DiffService` receives two plain arrays and is entity-type-agnostic.

### Session 2026-06-09

- Q: What is the ordering rule for the suggestion queue (which suggestion loads next after approve/refuse)? → A: `submittedAt` ASC — FIFO, oldest PENDING suggestion first.
- Q: Are entity normalizers (Livre, Auteur, Illustrateur…) already implemented or need to be created? → A: Don't exist yet — must be built in this feature. 6 types: BOOK, AUTHOR, ILLUSTRATOR, TRADUCTOR, EDITOR, COLLECTION.
- Q: How are field labels displayed in the diff comparator UI? → A: Static map per entity type — each normalizer defines `'fieldKey' => 'Label FR'` alongside field extraction; no Twig translation keys needed.
- Q: How is CSRF protection handled for JS fetch calls to approve/refuse routes? → A: Twig `csrf_token()` embedded in HTML via `data-csrf-token` attribute on action buttons; JS reads and sends it in the fetch request body/header.
- Q: How does DiffService determine which fields require word-level diff? → A: `EntityNormalizerInterface` gains a `getFieldTypes(): array<string, string>` method returning `fieldKey → 'scalar'|'text'|'tags'`; `DiffService::compute()` calls it to build the type map before iterating fields.
- Q: FR-028 — Supprimer et Dépublier sont-ils fonctionnels dans cette feature ou juste des placeholders visuels ? → A: Entièrement fonctionnels — les deux actions doivent déclencher des mutations en base (suppression définitive / passage en brouillon), avec routes Symfony dédiées, protection CSRF + ROLE_MODERATOR, et tests PHPUnit.
- Q: When toggling directly back to Vue Flux (not via "œil"), which suggestion loads in the comparator? → A: First PENDING suggestion (`submittedAt` ASC) — same FIFO rule as post-approve/refuse.
- Q: Is Gestion globale search/filter client-side JS or server-side? → A: Server-side — new Symfony route (or existing route with query params) processes search/filter; JS sends fetch/form request with query term and entity type filter.
- Q: Vue Tableau empty state when no PENDING suggestions exist? → A: Button disabled/hidden — when queue is empty, "Vue Tableau" toggle is disabled; user stays in (or is redirected to) Vue Flux empty/célébration state.
- Q: How does DiffService dispatch to the correct entity normalizer by entityType? → A: Tagged Symfony services behind a shared `EntityNormalizerInterface`; a service locator resolves the correct normalizer by `entityType` enum key — no manual switch/match in the controller.
- Q: Design shows Précédente/Suivante navigation arrows and S-key skip — in scope? → A: Design-only explorations — cut. No skip/navigate-without-decision feature to implement.
- Q: Design shows keyboard shortcuts V/R/S/M — in scope? → A: Out of scope.
- Q: FR-028 (3-option delete modal) conflicts with design (status-aware direct row buttons) — which is authoritative? → A: Spec — FR-028 applies as written regardless of row status; the 3-option modal (Supprimer / Dépublier / Annuler) is the correct implementation.
- Q: Empty state visual when suggestion queue is empty (SC-007)? → A: Skeleton display — greyed-out skeleton of the comparator layout.
- Q: Sidebar "La Suite" behavior on 880px–1099px? → A: Visible, stacked below the comparator (not sticky, not hidden).
- Q: Sidebar "La Suite" behavior on < 880px (tab mode)? → A: Visible, stacked below the tab panel.
