# Feature Specification: Page Contributeur — Suggestions

**Feature Branch**: `011-contributor-page`

**Created**: 2026-05-30

**Status**: Draft

---

## Clarifications

### Session 2026-05-30

- Q: Quelle est la politique de rétention des brouillons (durée, purge) ? → A: Pas de système de brouillon.
- Q: Y a-t-il un quota de soumissions (limite d'abus) ? → A: Limite dure sur le nombre de suggestions en attente simultanées (max 20 pending).
- Q: Quel niveau d'accessibilité est requis ? → A: WCAG 2.1 AA complet (labels ARIA, live regions, focus management).
- Q: Sémantique de l'action « Reproposer en collection » sur un refus ? → A: Notion supprimée — cette action n'existe pas.
- Q: Technologie frontend du wizard (4 étapes, live validation, diff) ? → A: Twig UX LiveComponent + Stimulus.
- Q: Source externe pour la vérification croisée des dates (Parution France, Édition originale) ? → A: Pas de vérification externe — validation locale uniquement (format et cohérence).
- Q: Stratégie de calcul du diff en mode Correction (étape 4) ? → A: Calculé à la volée dans le LiveComponent — données originales en `$originalData`, diff recalculé à chaque render.
- Q: Comportement des champs relationnels si le endpoint d'autocomplétion est indisponible ? → A: Fallback saisie texte libre — l'utilisateur peut taper manuellement sans blocage.
- Q: Mises à jour automatiques du panneau « Mon suivi » ? → A: Polling léger toutes les 30 s via Stimulus vers un endpoint JSON.
- Q: Formule de calcul du taux d'acceptation (FR-002) ? → A: validées / (validées + refusées) — les suggestions « En attente » sont exclues du calcul.
- Q: Quels types de fiches comptent pour la progression de rang (ContributorLevel) ? → A: Tous types (Livre, Auteur, Éditeur, etc.) comptent à égalité — 1 fiche validée = 1 point, quel que soit le type.
- Q: Comment les actions contextuelles d'un refus (FR-023) sont-elles déterminées ? → A: Liste prédéfinie — le modérateur sélectionne parmi des actions typées (ex. VOIR_FICHE, MASQUER) ; les actions sont stockées comme tableau d'enum/clés dans SuggestionRefusal.
- Q: Comportement de « Mon suivi » au-delà de 50 suggestions ? → A: Hard cap — affichage des 50 suggestions les plus récentes uniquement, sans pagination ni scroll infini.
- Q: Que se passe-t-il sur l'image uploadée si l'utilisateur change de mode (Correction → Nouvelle fiche) à l'étape 3 ou 4 ? → A: L'image est effacée — l'étape 3 se réinitialise lors du changement de mode.
- Q: La création « à la volée » dans les champs relationnels (FR-009) désigne-t-elle la création d'une nouvelle entrée en base ou d'un nouveau type d'entité ? → A: Création d'une nouvelle entrée en base uniquement (ex. nouvel Auteur avec le nom saisi) — pas de création de type d'entité.
- Q: Quel onglet est affiché par défaut sur mobile (< 1080 px) ? → A: L'onglet Action (formulaire wizard) est affiché en premier.
- Q: Que se passe-t-il si l'utilisateur quitte le wizard en cours de saisie (retour navigateur, fermeture d'onglet, navigation externe) ? → A: Une modale de confirmation custom (Stimulus) s'affiche — l'utilisateur confirme ou annule la navigation. En cas de confirmation, les données sont perdues (aucun brouillon).

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Soumettre une nouvelle fiche (Priority: P1)

Un contributeur connecté accède à la page « Mes Suggestions » et remplit le wizard en 4 étapes pour proposer une nouvelle fiche de type Livre, puis la soumet à la modération.

**Why this priority**: Fonctionnalité principale de la page — sans elle, aucune contribution n'est possible. Constitue le chemin doré du workflow.

**Independent Test**: Peut être testé de bout en bout en : (1) sélectionnant « Nouvelle fiche » + type « Livre », (2) renseignant les champs obligatoires avec des valeurs valides, (3) uploadant une couverture, (4) vérifiant l'aperçu, (5) soumettant — puis en vérifiant qu'une suggestion apparaît dans « Mon suivi » avec le statut « En attente ».

**Acceptance Scenarios**:

1. **Given** l'utilisateur est connecté et sur la page Suggestions, **When** il sélectionne « Nouvelle fiche » + type « Livre », **Then** l'étape 1 est marquée "done" dans le stepper et les champs Livre apparaissent à l'étape 2.
2. **Given** tous les champs obligatoires (Titre, Auteur, Éditeur, ISBN, Parution France, Paragraphes) sont renseignés avec des valeurs valides, **When** l'utilisateur atteint l'étape 4, **Then** le bouton « Soumettre à la modération » est actif et l'aperçu reflète les données saisies.
3. **Given** la fiche est soumise, **When** la soumission réussit, **Then** un toast de confirmation s'affiche et la suggestion apparaît dans « Mon suivi » avec le statut « En attente ».

---

### User Story 2 — Corriger une fiche existante (Priority: P1)

Un contributeur sélectionne « Corriger une fiche », choisit une fiche source, modifie un ou plusieurs champs, puis soumet la correction. Le système affiche un diff comparant la correction à la fiche originale.

**Why this priority**: Second mode principal du workflow — couvre une part significative des contributions réelles (corrections de données bibliographiques).

**Independent Test**: Peut être testé en : (1) sélectionnant « Corriger une fiche », (2) choisissant une fiche source existante (les données se pré-remplissent), (3) modifiant 2 champs, (4) vérifiant que l'étape 4 affiche « 2 champs modifiés » dans la légende diff, (5) soumettant.

**Acceptance Scenarios**:

1. **Given** l'utilisateur sélectionne « Corriger une fiche », **When** le composant de sélection de la fiche source apparaît et l'utilisateur choisit une fiche, **Then** le bandeau de pré-remplissage s'affiche (titre, auteur, année, ISBN de la fiche source) et les champs de l'étape 2 sont pré-remplis avec les données existantes.
2. **Given** l'utilisateur modifie N champs par rapport aux données originales, **When** il accède à l'étape 4, **Then** la légende diff affiche exactement « N champs modifiés » et les valeurs modifiées sont visuellement distinguées dans l'aperçu.
3. **Given** l'utilisateur passe de « Corriger » à « Nouvelle fiche », **When** la sélection change, **Then** le bandeau de pré-remplissage disparaît et les champs se vident.

---

### User Story 3 — Consulter et gérer son suivi de suggestions (Priority: P2)

Un contributeur visualise l'historique de ses suggestions, filtre par statut, et consulte le motif de refus d'une suggestion rejetée avec les actions contextuelles associées.

**Why this priority**: Essentiel pour la rétention du contributeur — il doit comprendre l'état de ses contributions et les actions à entreprendre après un refus.

**Independent Test**: Peut être testé en : (1) ouvrant le volet « Mon suivi », (2) vérifiant que les cartes affichent type, mode, nom, statut, horodatage relatif, (3) cliquant sur le filtre « Refusées », (4) cliquant sur le bouton info d'une carte refusée pour déplier le motif.

**Acceptance Scenarios**:

1. **Given** le contributeur a des suggestions dans plusieurs statuts, **When** il clique sur le filtre « Refusées », **Then** seules les cartes refusées s'affichent et le compteur se met à jour.
2. **Given** une carte a le statut « Refusée », **When** l'utilisateur clique sur le bouton info, **Then** le motif du refus s'affiche (nom du modérateur, texte du motif) avec les actions contextuelles (ex. « Voir la fiche existante », « Masquer ce message »).
3. **Given** le motif est affiché, **When** l'utilisateur clique « Masquer ce message », **Then** le motif se referme sans recharger la page.

---

### User Story 4 — Visualiser son tableau de bord contributeur (Priority: P2)

Un contributeur voit son rang actuel, la progression vers le rang suivant, et ses métriques (fiches validées, en attente, taux d'acceptation) dans le bandeau en haut de page.

**Why this priority**: Composant de valorisation et de gamification — motivationnel mais non bloquant pour la contribution.

**Independent Test**: Peut être testé en visitant la page en tant qu'utilisateur avec des contributions existantes et en vérifiant que le bandeau affiche le bon rang, le bon delta, et les bonnes métriques numériques.

**Acceptance Scenarios**:

1. **Given** l'utilisateur est connecté, **When** la page se charge, **Then** le bandeau affiche son rang actuel (ex. « Rang III · Chroniqueur confirmé »), le nombre de fiches manquantes pour le rang suivant (ex. « à 3 fiches du rang Archiviste »), le nombre de fiches validées, le nombre en attente, et son taux d'acceptation en %.
2. **Given** une de ses suggestions vient d'être validée, **When** il recharge la page, **Then** les compteurs reflètent la mise à jour.

---

### User Story 5 — Valider les champs en temps réel (Priority: P3)

Lors de la saisie dans le formulaire, chaque champ pertinent affiche immédiatement un retour visuel (icône verte/rouge/orange) et un message d'aide contextuel.

**Why this priority**: Améliore l'expérience et réduit les erreurs de soumission, mais la page reste utilisable sans cette couche de validation en direct.

**Independent Test**: Peut être testé champ par champ : (1) saisir un ISBN invalide → icône rouge + message d'erreur ; (2) saisir un ISBN valide → icône verte ; (3) saisir 9999 dans « Paragraphes » → champ bloqué + erreur bloquante ; (4) vérifier le champ Titre pour la déduplication.

**Acceptance Scenarios**:

1. **Given** l'utilisateur saisit un ISBN, **When** le format atteint 10 ou 13 chiffres (hors tirets), **Then** la clé de contrôle est vérifiée et l'état is-valid / is-invalid / is-checking s'applique avec le message approprié.
2. **Given** l'utilisateur saisit une valeur > 800 dans « Paragraphes », **When** il quitte le champ, **Then** le champ passe en état is-invalid avec un message bloquant et le bouton « Soumettre » reste désactivé.
3. **Given** l'utilisateur saisit un Titre, **When** la vérification d'unicité en base est en cours, **Then** l'état is-checking s'affiche ; quand la réponse arrive, is-valid ou is-invalid s'applique avec un message explicatif.

---

### Edge Cases

- Que se passe-t-il si l'utilisateur uploade un fichier > 4 Mo ou dans un format non supporté ? Un message d'erreur explicite doit s'afficher sans perdre les autres données saisies.
- Que se passe-t-il si la fiche source d'une correction est supprimée entre le chargement et la soumission ? La soumission doit échouer avec un message clair.
- Que se passe-t-il si le contributeur atteint 20 suggestions en attente ? Le bouton « Soumettre » est désactivé avec un message explicite ; il redevient actif dès qu'une suggestion est validée ou refusée.
- Que se passe-t-il si l'utilisateur soumet avec le bouton alors que le réseau est lent ? Le bouton doit être désactivé pendant la soumission pour éviter les doublons. [Couvert par FR-040]
- Sur mobile, les deux volets (Action / Suivi) sont commutables via les onglets — l'état du formulaire doit être conservé lors du basculement vers « Mon suivi » et retour.
- Que se passe-t-il si le endpoint de vérification d'unicité est indisponible ? Champ neutre + message d'aide ; soumission non bloquée. [Couvert par FR-055]
- Que se passe-t-il si la session expire pendant la saisie ? Redirection vers la page de connexion ; données du wizard perdues. [Couvert par FR-053]
- Que se passe-t-il si le traitement de l'image échoue côté serveur ? Erreur explicite, slot vidé, données étapes 1–2 préservées. [Couvert par FR-054]
- Que se passe-t-il si l'utilisateur double-clique sur « Soumettre » ? Bouton désactivé après le premier clic ; doublons prévenus côté client et serveur. [Couvert par FR-040]
- Que se passe-t-il si une entité autocomplétée est supprimée entre sélection et soumission ? Erreur serveur avec message explicite, retour à l'étape 2 requis. [Couvert par FR-056]

---

## Requirements *(mandatory)*

### Functional Requirements

**Tableau de bord**
- **FR-001**: La page DOIT afficher le rang actuel du contributeur (ex. « Rang III · Chroniqueur confirmé ») et le delta en nombre de fiches pour atteindre le rang supérieur.
- **FR-002**: La page DOIT afficher en temps réel : nombre de fiches validées, nombre de fiches en attente de modération, taux d'acceptation global (en %) calculé comme `validées / (validées + refusées)` — les suggestions « En attente » sont exclues du dénominateur.

**Wizard — Étape 1 : Type de proposition**
- **FR-003**: Le formulaire DOIT proposer deux modes mutuellement exclusifs : « Nouvelle fiche » et « Corriger une fiche », sélectionnables comme radio-boutons visuels.
- **FR-004**: Lorsque « Corriger une fiche » est sélectionné, un composant de sélection de la fiche source DOIT apparaître ; la sélection d'une fiche DOIT pré-remplir les champs de l'étape 2 avec les données existantes.
- **FR-005**: Le sélecteur de « Type de fiche » DOIT être obligatoire et proposer au minimum : Livre, Auteur, Illustrateur, Traducteur, Éditeur, Collection.
- **FR-006**: Le choix du type de fiche DOIT déterminer dynamiquement les champs affichés à l'étape 2.

**Wizard — Étape 2 : Formulaire de données (Livre)**
- **FR-007**: Chaque champ DOIT disposer d'une validation en temps réel avec retour visuel : icône verte (valide), icône rouge (erreur), icône orange/spinning (vérification en cours).
- **FR-008**: Les champs Titre et Sous-titre DOIVENT effectuer une vérification d'unicité en base et afficher le résultat dans le message d'aide.
- **FR-009**: Les champs relationnels (Auteur, Illustrateur, Éditeur, Collection) DOIVENT permettre la recherche dans la base existante par autocomplétion, avec option d'ajouter un co-auteur ou de créer une nouvelle entité à la volée. Si le endpoint d'autocomplétion est indisponible, le champ DOIT basculer en saisie texte libre sans bloquer la soumission.
- **FR-010**: Le champ ISBN DOIT valider la clé de contrôle (algorithme ISBN-10 ou ISBN-13) côté client et afficher le résultat instantanément.
- **FR-011**: Les champs Date/Année (Parution France, Édition originale) DOIVENT être validés localement : format année valide (ex. 4 chiffres, ≥ 1800, ≤ année courante + 2). Aucune vérification externe n'est requise.
- **FR-012**: Si la valeur du champ « Paragraphes » dépasse le seuil statistique (800), le champ DOIT passer en état d'erreur bloquante et le bouton « Soumettre » DOIT rester désactivé.

**Wizard — Étape 3 : Couverture**
- **FR-013**: La zone d'upload DOIT accepter le dépôt par Drag & Drop ou sélection via explorateur de fichiers.
- **FR-014**: Les formats acceptés DOIVENT être JPG, PNG et WEBP uniquement ; le poids maximum DOIT être de 4 Mo ; un recadrage automatique au format 3×4 DOIT être appliqué.
- **FR-015**: Un fichier non-conforme DOIT déclencher un message d'erreur explicite sans perdre les données saisies aux étapes précédentes.

**Wizard — Étape 4 : Aperçu et soumission**
- **FR-016**: L'étape 4 DOIT afficher une prévisualisation en direct de la fiche, reflétant les données saisies aux étapes 1 à 3.
- **FR-017**: En mode « Correction », l'aperçu DOIT afficher un outil de diff indiquant le nombre de champs modifiés par rapport à la fiche source, avec les valeurs modifiées visuellement distinguées. Le diff est calculé à la volée dans le LiveComponent (`$originalData` vs état courant du formulaire) — aucune donnée de diff n'est persistée en base.
- **FR-018**: Si le contributeur a déjà 20 suggestions en attente (statut « En attente »), le bouton « Soumettre à la modération » DOIT être désactivé et un message d'avertissement DOIT expliquer la limite atteinte.
- **FR-019**: Le bouton « Soumettre à la modération » DOIT être désactivé tant que l'étape 2 comporte des erreurs bloquantes ou des champs obligatoires non renseignés.
- **FR-020**: Après une soumission réussie, un toast de confirmation DOIT s'afficher et la suggestion DOIT apparaître dans le volet « Mon suivi » avec le statut « En attente ».

**Panneau « Mon suivi »**
- **FR-021**: Le panneau latéral DOIT afficher les 50 suggestions les plus récentes du contributeur (hard cap), chaque carte indiquant : type d'entité, mode (Nouvelle fiche / Correction), nom de l'entité, statut (En attente / Validée / Refusée), horodatage relatif. Le panneau DOIT effectuer un polling toutes les 30 secondes via un Stimulus controller appelant un endpoint JSON dédié — sans rechargement de page ni WebSocket.
- **FR-022**: Des filtres par statut (Toutes, En attente, Validées, Refusées) DOIVENT être disponibles ; les compteurs DOIVENT se mettre à jour selon le filtre actif.
- **FR-023**: Un clic sur le bouton info d'une carte « Refusée » DOIT déplier le motif de refus, incluant le nom du modérateur, le texte détaillé du motif, et les actions contextuelles associées. Ces actions sont issues d'une liste prédéfinie sélectionnée par le modérateur lors du refus (ex. `VOIR_FICHE` → « Voir la fiche existante », `MASQUER` → « Masquer ce message ») et stockées comme tableau de clés dans SuggestionRefusal.

**Comportement mobile**
- **FR-024**: Sur les écrans < 1080 px, les deux volets (Action / Suivi) DOIVENT être commutables via des onglets mobiles sans rechargement de page ni perte de l'état du formulaire.

**Accessibilité**
- **FR-025**: La page DOIT être conforme WCAG 2.1 AA : tous les champs de formulaire DOIVENT avoir un label ARIA explicite, les erreurs de validation DOIVENT être annoncées via `aria-live="assertive"`, le focus DOIT être géré explicitement à chaque changement d'étape du wizard, et l'intégralité du parcours DOIT être navigable au clavier (Tab, Shift+Tab, Enter, Espace).

**Design**
- **FR-026**: L'intégration visuelle DOIT suivre fidèlement le design défini dans `design/pages/suggestions.html`, en réutilisant les tokens CSS (`tokens.css`, `components.css`) et les classes CSS définies dans ce fichier de design.
- **FR-027**: Le wizard DOIT être implémenté avec **Twig UX LiveComponent + Stimulus** : LiveComponent gère l'état du wizard côté serveur (étapes, validation, diff), Stimulus gère les comportements client purs (drag-and-drop, focus management, autocomplete). Cette contrainte garantit le fallback sans JS (SC-006) et l'intégration native avec Symfony Security et Doctrine.

**Wizard — Navigation et abandon**
- **FR-028**: La navigation entre étapes DOIT fonctionner comme suit : retour libre vers toute étape précédente sans restriction ; navigation vers l'étape suivante bloquée si l'étape courante comporte des erreurs bloquantes ou des champs obligatoires non renseignés. Le LiveComponent préserve toutes les données saisies lors des allers-retours entre étapes. Un changement de mode (Correction → Nouvelle fiche) à l'étape 3 ou 4 efface l'image uploadée et réinitialise l'étape 3.
- **FR-029**: Le stepper DOIT afficher 4 états visuels distincts pour chaque étape : actif (étape courante), fait (étape validée — coche visible), verrouillé (étape future non accessible), erreur (étape précédente contenant des erreurs, visible lors d'un retour arrière).
- **FR-030**: Si l'utilisateur tente de quitter la page en cours de saisie (retour navigateur, fermeture d'onglet, lien externe), un controller Stimulus DOIT intercepter la navigation et afficher une modale de confirmation : « Vous avez des modifications non sauvegardées. Quitter la page ? » avec les actions « Rester sur la page » et « Quitter ». La confirmation entraîne la perte des données (aucun brouillon).

**Wizard — Champs relationnels (compléments FR-009)**
- **FR-031**: Le dropdown d'autocomplétion DOIT afficher un spinner pendant la requête vers le endpoint. Si aucun résultat n'est trouvé, il DOIT afficher « Aucun résultat » avec une option « Créer "[texte saisi]" » permettant de créer une nouvelle entrée en base (ex. nouvel Auteur) sans quitter le wizard — un appel API dédié insère l'entité avant de la sélectionner dans le champ. Le fallback en saisie texte libre (FR-009) se déclenche automatiquement si le endpoint ne répond pas dans les 3 secondes ou retourne une erreur 5xx ; le champ affiche alors : « Saisie libre — service de recherche indisponible ».
- **FR-032**: La navigation clavier dans le dropdown DOIT suivre le pattern ARIA Listbox : `Flèche Bas/Haut` pour naviguer les options, `Entrée` pour sélectionner, `Échap` pour fermer (focus retourne au champ).
- **FR-033**: Les champs relationnels (Auteur, Illustrateur, Éditeur, Collection) DOIVENT permettre l'ajout de co-contributeurs multiples via un bouton « + Ajouter ». Chaque entrée ajoutée DOIT être supprimable individuellement. Aucun maximum n'est défini ; la liste est scrollable.

**Wizard — Étape 2, états de validation (compléments FR-007, FR-010)**
- **FR-034**: Le retour visuel (FR-007) se déclenche après l'événement `blur` ou la fin d'un debounce de 300 ms. Les états sont mutuellement exclusifs et portés par les classes CSS `is-valid`, `is-invalid`, `is-checking`. Une **erreur bloquante** empêche toute navigation vers l'étape suivante et désactive le bouton « Soumettre » : champs obligatoires vides, dépassement du seuil Paragraphes (FR-012), format invalide (ISBN, Date). Une **erreur non bloquante** affiche un avertissement sans bloquer la soumission : doublon potentiel détecté sur Titre (résultat négatif de FR-008). Avant qu'un champ ISBN atteigne 10 ou 13 chiffres (hors tirets), le champ reste en état neutre — aucune icône n'est affichée.

**Wizard — Étape 3, zone d'upload (compléments FR-013–015)**
- **FR-035**: La zone de dépôt DOIT afficher 5 états visuels distincts : repos (idle — pointillé neutre), survol de glisser (drag-over — bordure accentuée, fond légèrement coloré), fichier invalide (bordure rouge + message d'erreur), chargement en cours (progress indicator ou spinner), téléversement réussi (miniature de prévisualisation). Un bouton « Choisir un fichier » (input file standard) DOIT toujours être présent et accessible au clavier en complément du drag-and-drop.
- **FR-036**: En cas d'erreur de fichier (format ou poids), les données des étapes 1 et 2 DOIVENT être préservées. Le slot d'upload DOIT être vidé (miniature effacée) ; l'utilisateur peut sélectionner un nouveau fichier sans rechargement de page.

**Wizard — Étape 4, prévisualisation et diff (compléments FR-016–017)**
- **FR-037**: Pour le type « Livre », la prévisualisation DOIT afficher les champs suivants s'ils sont renseignés : Titre, Sous-titre, Auteur(s) et co-auteurs, Illustrateur, Traducteur, Éditeur, Collection, ISBN, Parution France, Édition originale, Nombre de paragraphes, miniature de couverture. Les champs optionnels non renseignés SONT omis de la prévisualisation.
- **FR-038**: En mode Correction, chaque champ de formulaire de haut niveau compte pour 1 dans le diff (les champs relationnels comptent pour 1 même s'ils référencent une sous-entité). Passage vide → renseigné (et inversement) = 1 champ modifié. Si aucun champ n'est modifié, la légende affiche « 0 champs modifiés » et le bouton « Soumettre » reste actif. Les champs de la fiche source absents du formulaire de correction ne sont pas comptabilisés.

**Wizard — Étape 4, soumission (compléments FR-018–020)**
- **FR-039**: Le quota de 20 suggestions en attente est vérifié au rendu initial du LiveComponent et mis à jour à chaque cycle de polling (FR-021). Si le quota est atteint entre le chargement et la soumission, la vérification serveur retourne une erreur 400 et le message d'avertissement de FR-018 s'affiche à l'étape 4.
- **FR-040**: Pendant la soumission, le bouton DOIT passer en état désactivé avec un spinner et le libellé « Envoi en cours... » pour prévenir les doubles soumissions. Sans réponse dans les 30 secondes, un toast d'erreur s'affiche : « La soumission a pris trop de temps. Veuillez réessayer. » et le bouton redevient actif.
- **FR-041**: Les toasts DOIVENT avoir les propriétés suivantes : durée de 4 secondes avec auto-disparition, fermables par clic avant la fin du délai, positionnés en haut à droite sur desktop et en haut au centre sur mobile.

**Panneau « Mon suivi » (compléments FR-021–023)**
- **FR-042**: Lorsque le contributeur n'a aucune suggestion, le panneau DOIT afficher : « Vous n'avez pas encore soumis de suggestion. » Sans illustration ni pagination.
- **FR-043**: Si le endpoint de polling retourne une erreur, une tentative silencieuse est effectuée au cycle suivant (30 s). Après 3 échecs consécutifs, un indicateur discret « Mise à jour suspendue » DOIT apparaître sans bloquer l'interface.
- **FR-044**: L'onglet « Suivi » sur mobile DOIT afficher un badge indiquant le nombre de suggestions « En attente », mis à jour à chaque cycle de polling.
- **FR-045**: Les clés d'actions contextuelles non reconnues dans SuggestionRefusal (absentes de la liste prédéfinie d'enum) DOIVENT être silencieusement ignorées — aucun bouton n'est affiché pour ces clés.

**Comportement mobile (compléments FR-024)**
- **FR-046**: Sur mobile (< 1080 px), l'onglet « Action » (formulaire wizard) DOIT être actif par défaut au chargement de la page.
- **FR-047**: Sur mobile, le stepper DOIT s'afficher en version compacte : numéros d'étapes uniquement (sans libellés). Le libellé de l'étape courante PEUT être affiché sous le stepper compact.
- **FR-048**: Sur mobile, le dropdown d'autocomplétion DOIT s'afficher en mode inline (liste sous le champ, max 5 options visibles avec scroll) — pas de mode plein écran.
- **FR-049**: Le polling du panneau « Mon suivi » DOIT continuer quel que soit l'onglet actif (Action ou Suivi) — pas de suspension au changement d'onglet.
- **FR-050**: L'upload de couverture sur mobile DOIT utiliser un `<input type="file" accept="image/jpeg,image/png,image/webp">` standard. La sélection via appareil photo ou galerie est gérée nativement par le navigateur — aucun comportement spécifique à l'appareil n'est requis.
- **FR-051**: Le bandeau de pré-remplissage (FR-004) DOIT être replié par défaut sur mobile, affichant uniquement « Correction de : [Titre de la fiche source] ». Un clic l'expanse pour révéler tous les champs pré-remplis.

**Gestion des erreurs réseau et cas limites**
- **FR-052**: En cas d'erreur réseau pendant la saisie (LiveComponent request failure), un toast DOIT informer l'utilisateur : « Connexion perdue — vos données sont préservées, réessayez. » Le bouton de navigation et « Soumettre » DOIVENT être désactivés tant que la connexion est défaillante.
- **FR-053**: En cas d'expiration de session (réponse 401 sur une requête LiveComponent), Symfony Security DOIT rediriger vers la page de connexion. Les données du wizard sont perdues (aucune reprise prévue).
- **FR-054**: En cas d'échec serveur du traitement de l'image (recadrage 3×4), un message d'erreur DOIT s'afficher : « Une erreur est survenue lors du traitement de l'image. Veuillez réessayer. » Le slot d'upload est vidé ; les données des étapes 1 et 2 sont préservées.
- **FR-055**: Si le endpoint de vérification d'unicité (FR-008) est indisponible (timeout ou 5xx), le champ DOIT revenir à l'état neutre. Le texte d'aide DOIT indiquer : « Vérification impossible — ce champ sera contrôlé à la soumission. » La soumission n'est pas bloquée.
- **FR-056**: Si une entité sélectionnée via autocomplétion est supprimée de la base entre sa sélection et la soumission, le serveur DOIT retourner une erreur avec le message : « L'entité sélectionnée n'existe plus. Veuillez effectuer une nouvelle recherche. » L'utilisateur reste sur l'étape 4 et doit retourner à l'étape 2.
- **FR-057**: En cas d'erreurs de validation serveur sur des champs individuels à la soumission, le wizard DOIT rediriger l'utilisateur vers la première étape contenant une erreur et afficher les champs concernés en état `is-invalid`.

**Accessibilité (compléments FR-025)**
- **FR-058**: Les régions aria-live DOIVENT être configurées comme suit : messages d'erreur de validation → `aria-live="assertive"` ; messages de statut (is-checking, toasts, mises à jour polling) → `aria-live="polite"`.
- **FR-059**: Le focus DOIT se déplacer selon ces règles : changement d'étape (avant/arrière) → focus sur le titre de l'étape entrante ; apparition d'une erreur bloquante → focus sur le premier champ en erreur ; ouverture de la modale d'abandon (FR-030) → focus sur le premier bouton de la modale.
- **FR-060**: Le stepper DOIT être balisé avec `<ol>` ou `<nav aria-label="Étapes de création">`. L'étape courante DOIT avoir `aria-current="step"` ; les étapes verrouillées DOIVENT avoir `aria-disabled="true"`.
- **FR-061**: Le spinner is-checking DOIT avoir `role="status"` et `aria-label="Vérification en cours..."`. Le bouton « Choisir un fichier » de l'upload (FR-035) DOIT être focusable au clavier — c'est l'alternative clavier au drag-and-drop.
- **FR-062**: Le bouton d'expansion des détails de refus (FR-023) DOIT utiliser le pattern ARIA Disclosure : `aria-expanded="false|true"`, `aria-controls="refusal-{id}"`. Le panneau DOIT avoir `role="region"` et `aria-label="Motif de refus"`.
- **FR-063**: Après un échec d'upload, le focus DOIT retourner au bouton « Choisir un fichier » et l'erreur DOIT être annoncée via `aria-live="assertive"`.
- **FR-064**: Le bandeau de tableau de bord (FR-001–002) DOIT être balisé avec `role="region"` et `aria-label="Tableau de bord contributeur"`.

### Key Entities

- **Suggestion**: Représente une proposition de contribution d'un utilisateur. Attributs clés : type d'entité cible (Livre, Auteur, etc.), mode (nouvelle/correction), fiche source (nullable), données du formulaire (JSON ou champs dédiés), statut (pending/validated/refused), horodatage de soumission.
- **SuggestionRefusal**: Représente le refus d'une suggestion par un modérateur. Attributs : référence à la Suggestion, modérateur, motif textuel, actions contextuelles (tableau d'enum/clés prédéfinies sélectionnées par le modérateur, ex. `VOIR_FICHE`, `MASQUER`), date du refus.
- **Rang / ContributorLevel**: Représente les niveaux de progression du contributeur. Attributs : nom du rang, seuil en nombre de fiches validées. Tous les types de fiches (Livre, Auteur, Éditeur, etc.) comptent à égalité — 1 fiche validée = 1 point vers le rang suivant.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un contributeur peut compléter et soumettre une nouvelle fiche (Livre, champs obligatoires seulement) en moins de 5 minutes, du choix du type à la confirmation de soumission. Mesuré dans les conditions suivantes : connexion réseau stable, entités relationnelles existantes en base (autocomplétion disponible), fichier image préparé à l'avance.
- **SC-002**: Le retour de validation en temps réel s'affiche dans les 500 ms après l'événement `blur` ou la fin du debounce de 300 ms (hors vérifications réseau).
- **SC-003**: La validation locale des champs Date/Année (format, plage) s'affiche dans les 100 ms après la saisie — aucune latence réseau.
- **SC-004**: L'affichage du diff en mode Correction est exact — le nombre de champs modifiés affiché correspond exactement aux modifications réelles.
- **SC-005**: Le volet « Mon suivi » affiche les 50 suggestions les plus récentes de l'utilisateur sans pagination. Au-delà de 50 suggestions, seules les 50 plus récentes sont affichées (hard cap — pas de load more ni scroll infini).
- **SC-006**: L'interface est pleinement utilisable sans JavaScript dégradé (formulaire classique, pas de live-validation) — la page ne doit pas être blanche sans JS.
- **SC-007**: La page passe un audit axe-core (règles WCAG 2.1 AA, niveau de violation `critical` et `serious`) sans erreur ; le parcours complet du wizard (étapes 1 à 4, soumission, consultation « Mon suivi ») est navigable au clavier sans souris selon le script de test défini lors de la phase plan.

---

## Assumptions

- L'authentification est gérée par le système existant (Symfony Security) — la page est accessible uniquement aux utilisateurs connectés.
- Le système de rangs et les seuils de progression sont déjà définis dans la base de données ou la configuration (à préciser lors du planning).
- Les entités Livre, Auteur, Illustrateur, Traducteur, Éditeur, Collection existent déjà dans la base de données avec leurs endpoints d'autocomplétion (ou sont à créer — à confirmer lors du planning).
- Les champs Date/Année sont validés localement uniquement (format, plage) — aucune API externe n'est utilisée.
- Le recadrage automatique de la couverture (format 3×4) est appliqué côté serveur lors de l'upload.
- Le design à intégrer est `design/pages/suggestions.html` (fichier `.html`, pas `.html.twig` — le nom dans la description utilisateur était incorrect). Ce fichier est la référence visuelle exacte.
- Les actions contextuelles sur les refus (ex. « Voir la fiche existante ») sont générées dynamiquement selon le motif fourni par le modérateur — la logique de génération sera définie lors du planning.
- Le seuil bloquant pour « Paragraphes » est 800 (valeur max observée dans le catalogue) — à valider avec les données réelles.
- Support mobile cible : écrans ≥ 320 px, commutation Action/Suivi via onglets sous 1080 px. L'orientation paysage sur mobile n'est pas un cas d'usage optimisé — seul le mode portrait est ciblé.
- Si le ContributorLevel d'un utilisateur est absent ou non encore calculé (aucune contribution), le bandeau affiche le rang le plus bas avec le delta vers le premier seuil ; le taux d'acceptation affiche « — ».
