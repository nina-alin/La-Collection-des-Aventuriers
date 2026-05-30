# Feature Specification: Système de Notation et Commentaires

**Feature Branch**: `009-book-review-rating`

**Created**: 2026-05-27

**Status**: Draft

**Input**: User description: "Système de Notation et Commentaires — permettre aux utilisateurs de noter un livre (1 à 10) et de laisser un avis, afficher statistiques (histogramme, moyenne) et flux de commentaires filtrable."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Soumission d'un avis (Priority: P1)

Un utilisateur connecté consulte la page d'un livre, sélectionne une note de 1 à 10 via les boucliers cliquables, saisit un commentaire facultatif, puis publie son avis. Si l'utilisateur a déjà noté ce livre, sa note précédente est mise à jour plutôt que dupliquée.

**Why this priority**: C'est le cœur de la fonctionnalité — sans soumission d'avis, rien d'autre n'a de valeur. Représente l'action primaire de l'utilisateur.

**Independent Test**: Peut être testé en isolation en soumettant une note via le formulaire et en vérifiant la persistance en base de données. Livrable seul : les utilisateurs peuvent noter un livre.

**Acceptance Scenarios**:

1. **Given** un utilisateur authentifié sur la page d'un livre sans avis existant, **When** il sélectionne le bouclier 7, saisit un commentaire de 200 caractères et clique "Publier mon avis", **Then** son avis est enregistré, les 4 targets Turbo Stream se mettent à jour (stats header, histogramme, liste d'avis, formulaire pré-rempli), et le bouton du formulaire passe à "Modifier mon avis".
2. **Given** un utilisateur ayant déjà noté ce livre (note 5), **When** il revient sur la page et soumet une nouvelle note (8) avec un nouveau commentaire, **Then** l'entrée existante est mise à jour (pas de doublon), et la nouvelle note remplace l'ancienne.
3. **Given** un utilisateur non authentifié, **When** il tente d'accéder au formulaire de notation, **Then** il est redirigé vers la page de connexion.
4. **Given** un utilisateur authentifié, **When** il tente de soumettre sans sélectionner de bouclier (note), **Then** un message d'erreur lui indique que la note est obligatoire.
5. **Given** un utilisateur authentifié en cours de saisie, **When** sa session expire et il soumet le formulaire, **Then** il est redirigé vers la page de connexion (données non sauvegardées).
6. **Given** un utilisateur authentifié, **When** la requête de soumission échoue (erreur réseau ou erreur serveur), **Then** l'état du formulaire est préservé et un message flash d'erreur est affiché.

---

### User Story 2 - Consultation des statistiques de notation (Priority: P2)

Un visiteur (authentifié ou non) consulte la page d'un livre et voit immédiatement la note moyenne, le nombre total d'évaluations et les avatars des 4 derniers évaluateurs dans l'en-tête. Il peut également visualiser l'histogramme de distribution des notes dans la section communautaire.

**Why this priority**: Les statistiques de notation augmentent la confiance de l'acheteur/lecteur et donnent de la valeur sociale à la page. Prioritaire après la soumission car nécessite des données.

**Independent Test**: Peut être testé avec des données de fixtures. Livrable seul : la page affiche des statistiques calculées dynamiquement.

**Acceptance Scenarios**:

1. **Given** un livre avec 10 avis (scores variés), **When** n'importe quel visiteur consulte la page du livre, **Then** la note moyenne exacte (arrondie à 1 décimale, arrondi mathématique standard), le compteur "X aventuriers ont noté ce livre" et les initiales des 4 derniers évaluateurs (triés par `updatedAt` DESC) sont affichés.
2. **Given** un livre sans aucun avis, **When** un visiteur consulte sa page, **Then** l'en-tête de notation indique "Aucune évaluation pour l'instant" et l'histogramme est vide ou masqué.
3. **Given** un livre avec des avis, **When** un visiteur fait défiler jusqu'à la section communautaire, **Then** l'histogramme affiche 10 barres (1 à 10) dont la hauteur est proportionnelle au nombre d'avis pour chaque note (échelle linéaire : hauteur = count / max_count × 100%).
4. **Given** un livre sans aucun avis et un utilisateur authentifié qui soumet le premier avis, **When** la soumission est confirmée, **Then** le Turbo Stream remplace l'état vide "Aucune évaluation pour l'instant" par les statistiques calculées et l'histogramme, sans rechargement de page.

---

### User Story 3 - Consultation et filtrage de la liste des avis (Priority: P3)

Un visiteur consulte la liste des avis dans la section communautaire. Il peut filtrer par "AVEC COMMENTAIRE" ou "RÉCENTES" sans rechargement de page. Le filtre "RÉCENTES" est actif par défaut au chargement. Chaque avis affiche l'initiale et le nom de l'auteur, la date relative (fuseau horaire du navigateur), le commentaire et la note (bouclier doré).

**Why this priority**: Enrichit l'expérience de lecture des avis mais ne bloque pas les fonctionnalités principales.

**Independent Test**: Peut être testé avec des données de fixtures incluant des avis avec et sans commentaires. Livrable seul : liste des avis avec filtres fonctionnels.

**Acceptance Scenarios**:

1. **Given** une liste d'avis mélangés (avec/sans commentaire), **When** le visiteur clique sur "AVEC COMMENTAIRE", **Then** seuls les avis ayant un texte non vide et non nul s'affichent, triés par `updatedAt` DESC, sans rechargement de la page.
2. **Given** une liste d'avis, **When** le visiteur clique sur "RÉCENTES", **Then** tous les avis sont triés par `updatedAt` DESC (plus récemment modifié en premier), sans rechargement de page.
3. **Given** le filtre "AVEC COMMENTAIRE" actif ne retourne aucun résultat, **When** la liste se charge, **Then** le message "Aucune évaluation pour l'instant" est affiché dans le Turbo Frame.
4. **Given** un avis d'un modérateur ou admin, **When** la liste s'affiche, **Then** un badge distinctif (rôle) est visible à côté du nom de cet utilisateur. Seuls les rôles "modérateur" et "admin" bénéficient d'un badge — ce sont les seuls rôles existants dans l'application.
5. **Given** le filtre "AVEC COMMENTAIRE" actif sur la page 2, **When** le visiteur navigue vers la page 3, **Then** le paramètre de filtre est préservé dans les liens de pagination du Turbo Frame (`?filter=avec_commentaire&page=3`).

---

### Edge Cases

- Que se passe-t-il si un utilisateur soumet simultanément deux avis pour le même livre (double-clic) ? Si la contrainte d'unicité (utilisateur, livre) est violée au niveau de la persistance malgré la protection côté client, le serveur retourne une erreur 409 Conflict avec un message d'erreur explicite.
- Que se passe-t-il si le commentaire dépasse 1000 caractères ? La soumission est bloquée côté client (compteur) et rejetée côté serveur.
- Que se passe-t-il si un livre n'a qu'un seul avis ? La moyenne affiche ce score unique, l'histogramme montre une seule barre.
- Que se passe-t-il si un utilisateur tente de voter avec une valeur hors 1-10 ? La validation serveur rejette la requête avec un message d'erreur explicite.
- Que se passe-t-il si l'utilisateur n'a pas encore de profil complet (pas d'initiales) ? Une image générique fixe (placeholder) est affichée.
- Que se passe-t-il si un utilisateur supprime son avis ? La suppression est physique et immédiate ; 4 targets Turbo Stream sont mis à jour : stats header, histogramme, liste d'avis, et formulaire (réinitialisé vide).
- Que se passe-t-il si la page N de la liste devient vide suite à une suppression ? La page N est rendue avec le message "Aucune évaluation pour l'instant" (pas de redirection automatique).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Le système DOIT permettre à tout utilisateur authentifié de soumettre une note de 1 à 10 pour un livre.
- **FR-002**: Le système DOIT permettre à tout utilisateur authentifié d'associer un commentaire facultatif (max 1000 caractères) à sa note. Un commentaire vide (chaîne vide `""`) est traité comme l'absence de commentaire (équivalent à `NULL`).
- **FR-003**: Le système DOIT garantir qu'un utilisateur ne peut avoir qu'une seule note par livre — une nouvelle soumission met à jour l'existante. En cas de violation de la contrainte d'unicité au niveau de la persistance (race condition), le serveur retourne une réponse 409 Conflict avec un message d'erreur explicite.
- **FR-004**: Le système DOIT afficher la note moyenne (arrondie à 1 décimale, arrondi mathématique standard) calculée sur l'ensemble des avis du livre.
- **FR-005**: Le système DOIT afficher le nombre total d'évaluations pour chaque livre.
- **FR-006**: Le système DOIT afficher les initiales (ou avatars) des 4 derniers utilisateurs ayant évalué le livre, triés par `updatedAt` DESC (soumission initiale ou modification la plus récente).
- **FR-007**: Le système DOIT afficher un histogramme de distribution des notes (barres pour les valeurs 1 à 10), dont la hauteur est proportionnelle au nombre d'avis pour chaque note (échelle linéaire : hauteur = count / max_count × 100%).
- **FR-008**: Le système DOIT permettre de filtrer la liste des avis via deux filtres : "AVEC COMMENTAIRE" (avis avec texte non vide et non nul) et "RÉCENTES" (tous les avis). Les deux filtres trient par `updatedAt` DESC. Le filtre "RÉCENTES" est actif par défaut au chargement de la page. La navigation est assurée par Symfony UX Turbo Frames sans rechargement de page. Les paramètres de filtre actif sont préservés dans les liens de pagination du Turbo Frame. Si un filtre ne retourne aucun résultat, le message "Aucune évaluation pour l'instant" est affiché dans le Turbo Frame.
- **FR-009**: Chaque avis dans la liste DOIT afficher : l'initiale/avatar de l'auteur, son nom, son rôle (badge "modérateur" ou "admin" si applicable — seuls ces deux rôles existent), la date relative calculée dans le fuseau horaire du navigateur de l'utilisateur, le commentaire (le cas échéant) et le score via un bouclier doré.
- **FR-010**: Le système DOIT bloquer la soumission si aucune note n'est sélectionnée et afficher un message d'erreur explicite.
<!-- FR-011–FR-013 : supprimés en session 2026-05-30 (refonte des exigences lors des clarifications). -->
- **FR-014**: Le formulaire de notation DOIT être inaccessible aux utilisateurs non authentifiés (redirection vers connexion). Si la session de l'utilisateur expire pendant la saisie et qu'il soumet le formulaire, il est redirigé vers la page de connexion (données non sauvegardées).
- **FR-015**: Le compteur de caractères du commentaire DOIT se mettre à jour dynamiquement et bloquer la saisie au-delà de 1000 caractères.
- **FR-016**: La liste des avis DOIT être paginée (pagination classique, page 1/2/3…) dans le Turbo Frame — les contrôles de page sont rendus dans le frame et naviguent sans rechargement de page complète. 10 avis par page. Si le nombre total de résultats est inférieur ou égal à 10, les contrôles de pagination sont masqués. Si la page N demandée ne contient aucun résultat, le message "Aucune évaluation pour l'instant" est affiché.
- **FR-017**: Si l'utilisateur authentifié a déjà soumis un avis pour ce livre, le formulaire de notation DOIT être pré-rempli avec sa note (boucliers) et son commentaire existants, et afficher le bouton "Modifier mon avis" ainsi qu'un bouton de suppression. Pour un nouvel avis (aucun avis existant), le formulaire affiche le bouton "Publier mon avis" sans bouton de suppression.
- **FR-018**: Un utilisateur authentifié DOIT pouvoir supprimer définitivement son propre avis ; la suppression déclenche un Turbo Stream mettant à jour les 4 targets (stats header, histogramme, liste d'avis, formulaire réinitialisé vide avec bouton "Publier mon avis") pour permettre une re-notation immédiate. Si la requête de suppression échoue, l'état de la page est préservé et un message flash d'erreur est affiché.
- **FR-019**: Un utilisateur avec le rôle "modérateur" ou "admin" DOIT pouvoir supprimer définitivement l'avis de n'importe quel autre utilisateur.
- **FR-020**: Les initiales de l'utilisateur SONT dérivées de la première lettre de son prénom et de la première lettre de son nom, en majuscules (ex : "Nina Alin" → "NA"). Si le prénom ou le nom est absent, une image générique fixe (placeholder) est affichée à la place des initiales.
- **FR-021**: Le sélecteur de boucliers (note 1 à 10) DOIT être accessible : il DOIT avoir un `role="radiogroup"`, chaque bouclier DOIT avoir un `aria-label` explicite (ex : "Note 7 sur 10"), et la navigation au clavier DOIT être supportée (touches fléchées pour sélectionner, Entrée/Espace pour confirmer).
- **FR-022**: En cas d'échec d'une requête de soumission ou de suppression d'avis (erreur réseau, erreur serveur 5xx), l'état du formulaire DOIT être préservé (données saisies non perdues) et un message flash d'erreur DOIT être affiché à l'utilisateur.

### Key Entities

- **Review**: Représente l'évaluation d'un livre par un utilisateur. Attributs : score (1-10), commentaire (nullable, ≤1000 chars, chaîne vide traitée comme NULL), date de création, date de mise à jour. Reliée à un livre (ManyToOne) et à un utilisateur (ManyToOne nullable). Contrainte d'unicité : (utilisateur, livre). Cascade delete : la suppression du Book parent entraîne la suppression en cascade de toutes ses Reviews. La suppression du User parent entraîne l'anonymisation des Reviews (référence auteur mise à NULL) — pas de cascade delete.
- **Book**: Entité existante. Reliée à zéro ou plusieurs Reviews (OneToMany).
- **User**: Entité existante. Reliée à zéro ou plusieurs Reviews (OneToMany). Fournit les initiales/avatar pour l'affichage.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un utilisateur authentifié peut sélectionner une note et publier son avis en moins de 60 secondes depuis l'ouverture de la page. *(Objectif UX non-bloquant — non mesurable en CI.)*
- **SC-002**: La note moyenne et le compteur d'évaluations se mettent à jour immédiatement après soumission d'un avis via Turbo Stream (sans rechargement de page). La mise à jour est perçue comme instantanée — aucun seuil numérique défini.
- **SC-003**: Le filtrage de la liste des avis s'effectue en moins de 500 ms perçus par l'utilisateur, sans rechargement de page.
- **SC-004**: 100% des soumissions avec une note hors de la plage 1-10 ou un commentaire dépassant 1000 caractères sont rejetées par le système.
- **SC-005**: Un utilisateur ne peut pas créer plus d'un avis par livre — toute tentative de double soumission met à jour l'existant ou retourne une erreur 409.
- **SC-006**: L'histogramme reflète fidèlement la distribution réelle des notes en base de données. La cohérence est garantie par l'architecture Turbo Stream (mise à jour synchrone après chaque écriture) — aucun budget de cohérence différée.
- **SC-007**: La suppression d'un avis déclenche un Turbo Stream mettant à jour les 4 targets (stats header, histogramme, liste d'avis, formulaire) sans rechargement de page complète.

## Out of Scope

- Interface de modération (tableau de bord admin/modérateur pour gérer ou signaler les avis).
- Sidebar "Contributeurs" dynamique — son contenu est statique pour cette itération.
- Limitation du débit (rate limiting) sur la soumission d'avis.

## Assumptions

- Les utilisateurs doivent être authentifiés pour soumettre un avis ; la consultation des avis et des statistiques est ouverte à tous les visiteurs.
- L'entité User et l'entité Book existent déjà dans le projet — cette fonctionnalité s'y greffe.
- Les filtres "AVEC COMMENTAIRE" et "RÉCENTES" sont implémentés via Symfony UX Turbo Frames : chaque clic sur un filtre navigue un `<turbo-frame>` qui charge les avis filtrés depuis le serveur sans rechargement de page.
- Le design de toutes les interactions visuelles (boucliers, compteur de caractères, filtres) doit être fidèle au fichier source `design/pages/livre.html` (finalisé).
- Les notes soumises sont publiées immédiatement.
- La protection CSRF est assurée par les mécanismes intégrés de Symfony (token CSRF automatique) — aucun FR explicite requis.

## Clarifications

### Session 2026-05-27

- Q: Mécanisme d'implémentation des filtres (client-side JS / AJAX / Turbo) → A: Symfony UX Turbo Frames — chaque filtre navigue un turbo-frame vers le serveur.
- Q: Mise à jour post-soumission (redirect / Turbo Stream / AJAX) → A: Turbo Stream — le POST retourne une réponse stream qui met à jour 4 targets : stats header, histogramme, liste d'avis et formulaire (pré-rempli avec l'avis soumis). *(Initialement 3 targets — corrigé en session 2026-05-30.)*
- Q: Sémantique du filtre "Récentes" (tri date vs fenêtre temporelle) → A: Tri DESC par `updatedAt` — toutes les reviews, plus récemment modifiée en premier. *(Initialement `createdAt` — corrigé en session 2026-05-30.)*
- Q: Pagination de la liste des avis (aucune / fixe / pagination classique / infinite scroll) → A: Pagination classique (page 1, 2, 3…) dans le Turbo Frame.
- Q: Pré-remplissage du formulaire si avis existant (vide / boucliers seulement / tout pré-rempli) → A: Boucliers + commentaire pré-remplis avec l'avis existant de l'utilisateur.
- Q: Suppression par l'auteur de son propre avis (non / suppression physique / masquage) → A: Oui, l'auteur peut supprimer définitivement son propre avis.
- Q: Nombre d'avis par page (pagination classique) → A: 10 avis par page.

### Session 2026-05-27 (suite)

- Q: Ordre de tri par défaut du filtre "TOUTES" → A: `updatedAt` DESC. *(Filtre "TOUTES" supprimé en session 2026-05-30.)*
- Q: Comportement cascade suppression User/Book → A: Book = cascade delete. User = anonymisation des Reviews (auteur mis à NULL). *(Initialement cascade delete pour les deux — corrigé en session 2026-05-30.)*
- Q: État du formulaire après suppression de l'avis par l'auteur → A: Formulaire vide réaffiché via Turbo Stream (4e target) — re-notation immédiate possible.

### Session 2026-05-30

- Q: Cibles Turbo Stream après soumission → A: 4 targets — stats header + histogramme + liste d'avis + formulaire (pré-rempli avec l'avis soumis, bouton "Modifier mon avis" visible).
- Q: Ordre de tri pour tous les filtres → A: `updatedAt` DESC pour les deux filtres ("AVEC COMMENTAIRE" et "RÉCENTES").
- Q: Filtre actif par défaut au chargement de la page → A: "RÉCENTES" actif par défaut.
- Q: Filtre "TOUTES" → A: Supprimé — 2 filtres uniquement : "AVEC COMMENTAIRE" et "RÉCENTES".
- Q: Tri des "4 derniers évaluateurs" (FR-006) → A: `updatedAt` DESC — l'évaluateur le plus récemment actif (soumission initiale ou modification) apparaît en premier.
- Q: Traitement chaîne vide dans le commentaire → A: Chaîne vide `""` équivalente à `NULL` — exclue du filtre "AVEC COMMENTAIRE".
- Q: Arrondi de la note moyenne → A: Arrondi mathématique standard (0.05 → supérieur).
- Q: Comportement lors d'un échec de soumission/suppression → A: État du formulaire préservé + message flash d'erreur affiché.
- Q: Suppression du compte utilisateur → A: Anonymisation des Reviews (référence auteur mise à NULL), pas de cascade delete.
- Q: Comportement lors d'une soumission en double (race condition) → A: Serveur retourne 409 Conflict + message d'erreur explicite.
- Q: Comportement si la page N devient vide → A: Afficher "Aucune évaluation pour l'instant" sur la page N (pas de redirection).
- Q: Contrôles de pagination si résultats ≤ 10 → A: Contrôles de pagination masqués.
- Q: Session expirée pendant la saisie → A: Redirection vers la page de connexion à la soumission (données non sauvegardées).
- Q: Rôles bénéficiant d'un badge → A: "modérateur" et "admin" uniquement — ce sont les seuls rôles existants dans l'application.
- Q: Accessibilité du sélecteur de boucliers → A: `role="radiogroup"` + `aria-label` par bouclier + navigation clavier (touches fléchées + Entrée/Espace).
- Q: Droits de suppression admin/modérateur → A: Les utilisateurs avec le rôle "modérateur" ou "admin" peuvent supprimer l'avis de n'importe quel utilisateur.
- Q: Dérivation des initiales → A: prénom[0]+nom[0] en majuscules. Si absent → image placeholder fixe.
- Q: Fuseau horaire pour les dates relatives → A: Fuseau horaire du navigateur de l'utilisateur.
- Q: Gestion CSRF → A: Mécanismes intégrés Symfony — aucun FR explicite requis.
- Q: Rate limiting → A: Hors périmètre pour cette itération.
