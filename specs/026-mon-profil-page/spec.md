# Feature Specification: Page "Mon Profil" — Tableau de Bord Utilisateur

**Feature Branch**: `026-mon-profil-page`

**Created**: 2026-06-11

**Status**: Draft

**Input**: Intégration de la vue "Mon Profil" avec gestion de la vie privée des listes et suppression de compte. Les maquettes design/pages/profil.html font foi pour le rendu visuel.

---

## Clarifications

### Session 2026-06-11

- Q: Stratégie de suppression de compte — hard delete, soft delete avec anonymisation, ou soft delete simple ? → A: Soft delete avec anonymisation — ligne `User` conservée, `deleted_at` posé, champs PII (email, avatar, pseudonyme) nullés, pas de cascade.
- Q: Mécanisme de provisionnement du GhostUser ? → A: Migration Doctrine dédiée — GhostUser inséré via SQL dans une migration, identifié par `email: ghost@deleted.local`.
- Q: Modification d'email — immédiate ou double opt-in ? → A: Double opt-in — lien de confirmation envoyé au nouvel email avant mise à jour ; ancien email reste actif jusqu'à confirmation.
- Q: Streak de connexion déjà trackée en base (feature 019) ? → A: Non — nouveaux champs `loginStreak` (int) et `lastLoginDate` (date) à ajouter sur `User`, logique de mise à jour au login à implémenter dans cette feature.
- Q: Toggle visibilité des listes — endpoint API REST ou controller Symfony direct ? → A: Controller Symfony direct — mise à jour de l'entité `UserList`, pas d'endpoint REST dédié.
- Q: Logique de calcul du streak — granularité et règle de reset ? → A: ~~Incrément 1×/jour UTC~~ → **révisé** : utilise `User.timezone` (fallback UTC) — voir Session 2026-06-11 (checklist review) CHK016.
- Q: Déliaison OAuth si aucun mot de passe défini — bloquer ou autoriser avec avertissement ? → A: Bloquer — bouton "Délier" désactivé ou message "Définissez un mot de passe avant de délier votre compte Google" ; l'action est refusée tant qu'aucun mot de passe n'est défini.
- Q: Upload d'avatar — contraintes fichier (taille, formats, recadrage) ? → A: 2 Mo max, formats JPG/PNG/WebP, recadrage carré obligatoire côté client avant envoi.
- Q: Pagination des livres dans les onglets de listes ? → A: Pagination — 20 livres/page, navigation prev/next.
- Q: Stockage des avatars uploadés — local filesystem, cloud storage, ou base64 en BDD ? → A: Fichiers locaux — `public/uploads/avatars/`, servi par le webserver.
- Q: Stockage du token de double opt-in pour changement d'email ? → A: 3 champs sur `User` — `pendingEmail`, `emailChangeToken`, `emailTokenExpiresAt`.
- Q: La Taverne (forum) est-elle un module interne ou un service externe ? → A: Service externe — lien simple vers URL tierce, pas de liaison gérée en BDD ; le bouton "Délier" dans Paramètres concerne uniquement OAuth Google.
- Q: Le champ `deleted_at` existe-t-il déjà sur `User` ou est-il à créer ? → A: À créer dans cette feature (026).

### Session 2026-06-11 (checklist review)

- Q: CHK020 — Suppression de compte : null auteur (004 FR-011) ou réattribution GhostUser (026 FR-013) ? → A: GhostUser. Couvre à la fois les `Suggestion` (VALIDATED) et les `CorrectionProposal` (PUBLISHED). FR-013 prévaut sur 004 FR-011 pour les suppressions auto-initiées.
- Q: CHK021 — Stratégie d'anonymisation : null ou `[deleted]` ? Que faire du `googleId` ? → A: Utiliser `[deleted]` pour `email` et `pseudo`/`displayName` (cohérent avec 004 FR-011). `avatarUrl` et `googleId` sont nullés — libérer `googleId` permet au compte Google d'être réutilisé pour un nouveau compte.
- Q: CHK008/CHK018 — KPI "Validées par la guilde" inclut-il les `CorrectionProposal` ? → A: Non — uniquement les entités `Suggestion` (VALIDATED). Les `CorrectionProposal` comptent pour le rang (feature 019) mais pas pour ce KPI.
- Q: CHK019 — Dénominateur du taux d'acceptation ? → A: Suggestions finalisées uniquement — dénominateur = VALIDATED + REJECTED. Les suggestions en attente (PENDING) sont exclues du calcul.
- Q: CHK016 — Calcul du streak : UTC ou fuseau utilisateur ? → A: Fuseau utilisateur (`User.timezone`, fallback UTC si null) — cohérent avec feature 017 FR-004.
- Q: CHK013 — Affichage du panneau Rôle & Permissions pour ROLE_ADMIN (absent du design) ? → A: Panneau identique au ROLE_MODERATOR avec "Niveau 3 · sur 3", titre "Administrateur de la guilde", et toutes les 9 permissions accordées (aucune refusée). Voir FR-011 pour l'énumération complète.

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Consulter son tableau de bord profil (Priority: P1)

Un utilisateur connecté accède à sa page "Mon Profil". Il voit en un coup d'œil son identité (avatar, pseudonyme, badge de rôle), sa date d'inscription, sa guilde régionale, ainsi que quatre cartes de statistiques synthétisant son activité : livres en collection, notes déposées, contributions validées et constance de connexion.

**Why this priority**: C'est le point d'entrée central de toute l'expérience profil. Sans ce bandeau fonctionnel, aucune autre section n'a de sens.

**Independent Test**: Se connecter et naviguer vers `/profil`. Vérifier que le bandeau affiche l'avatar, le pseudonyme, la date d'inscription (absolue + relative), la guilde, et que les 4 KPIs sont présents avec des valeurs cohérentes avec les données du compte.

**Acceptance Scenarios**:

1. **Given** un utilisateur connecté, **When** il accède à `/profil`, **Then** le bandeau affiche son avatar, son pseudonyme, et son badge de rôle si applicable (Modérateur ou Administrateur)
2. **Given** un utilisateur standard (Aventurier), **When** il consulte le bandeau, **Then** aucun badge de rôle ne s'affiche
3. **Given** un utilisateur inscrit depuis plus d'un an, **When** il consulte le bandeau, **Then** la date d'inscription s'affiche en format absolu (ex : "14 mars 2025") et en format relatif (ex : "il y a 15 mois")
4. **Given** un utilisateur avec des livres dans sa collection, **When** il consulte les KPIs, **Then** la carte "Livres en collection" affiche le total avec la répartition en cours / terminés et la tendance mensuelle d'ajout
5. **Given** un utilisateur ayant déposé des avis, **When** il consulte les KPIs, **Then** la carte "Notes déposées" affiche le nombre d'avis et sa note moyenne globale donnée
6. **Given** un utilisateur ayant soumis des suggestions, **When** il consulte les KPIs, **Then** la carte "Validées par la guilde" affiche le nombre de fiches validées et le taux d'acceptation en pourcentage
7. **Given** un utilisateur ayant une série de connexions consécutives, **When** il consulte les KPIs, **Then** la carte "Constance" affiche le nombre de jours consécutifs de connexion (streak)

---

### User Story 2 — Gérer la visibilité publique de ses listes (Priority: P1)

Un utilisateur connecté navigue entre ses onglets de listes (Ma Collection, À Lire, À Acheter, Mes Favoris). Pour chaque onglet, un bouton bascule lui permet de rendre sa liste publique ou privée. L'état est sauvegardé immédiatement et impacte la visibilité de cette liste sur son profil public.

**Why this priority**: Nouvelle fonctionnalité backend requise. La confidentialité des listes est une attente fondamentale des utilisateurs et conditionne la confiance dans la plateforme.

**Independent Test**: Sur l'onglet "Ma Collection", basculer le toggle de "Privée" à "Publique". Se déconnecter, accéder au profil public de l'utilisateur et vérifier que la collection est bien visible. Revenir à "Privée" et vérifier l'absence de la liste sur le profil public.

**Acceptance Scenarios**:

1. **Given** un utilisateur sur l'onglet "Ma Collection", **When** il bascule le toggle sur "Publique", **Then** l'état est sauvegardé sans rechargement et un toast de confirmation s'affiche
2. **Given** un utilisateur sur l'onglet "Ma Collection" avec liste publique, **When** il bascule le toggle sur "Privée", **Then** l'état est sauvegardé et la liste disparaît du profil public
3. **Given** un utilisateur naviguant entre les onglets, **When** il change d'onglet, **Then** le toggle reflète l'état de visibilité propre à cet onglet (chaque liste a sa propre visibilité)
4. **Given** un utilisateur non connecté consultant le profil public d'un autre membre, **When** une liste est définie en "Privée", **Then** cette liste n'apparaît pas du tout sur le profil public

---

### User Story 3 — Changer l'affichage et le tri de ses listes (Priority: P2)

L'utilisateur peut alterner entre une vue en grille (cartes, par défaut) et une vue en liste (tableau condensé) pour consulter ses livres. Il peut également trier les résultats (ex : "Récemment ajoutés").

**Why this priority**: Améliore l'ergonomie pour les utilisateurs avec de grandes collections. Ne requiert pas de nouveau modèle de données.

**Independent Test**: Depuis l'onglet "Ma Collection" avec au moins 5 livres, cliquer sur le bouton "vue liste" → les livres s'affichent en format tableau. Cliquer "vue grille" → retour aux cartes. Changer le tri → l'ordre des résultats se met à jour.

**Acceptance Scenarios**:

1. **Given** un utilisateur sur un onglet avec des livres, **When** il clique sur le bouton "vue liste", **Then** les livres s'affichent en format tableau condensé sans rechargement
2. **Given** un utilisateur en vue liste, **When** il clique sur le bouton "vue grille", **Then** les livres s'affichent en format carte (vue par défaut)
3. **Given** un utilisateur sur n'importe quel onglet, **When** il change le tri, **Then** les résultats sont triés selon le critère sélectionné

---

### User Story 4 — Se désabonner d'un auteur ou d'une collection (Priority: P2)

Dans la section "Mes Auteurs & Collections Suivis", chaque carte dispose d'un bouton "♥ SUIVI". En cliquant dessus, l'utilisateur se désabonne de cette entité. La carte disparaît dynamiquement de la grille avec une courte animation, et un toast de confirmation s'affiche.

**Why this priority**: Action de gestion directe depuis le profil. Complète la fonctionnalité de suivi existante en offrant une interface centralisée pour s'en désabonner.

**Independent Test**: Depuis la section "Mes Auteurs Suivis", cliquer sur "♥ SUIVI" d'un auteur. Vérifier : animation de fondu, disparition de la carte, toast de confirmation. Recharger la page et vérifier que l'auteur n'apparaît plus dans la liste.

**Acceptance Scenarios**:

1. **Given** un utilisateur avec des auteurs suivis, **When** il clique sur "♥ SUIVI" d'un auteur, **Then** la carte disparaît de la grille avec une animation de fondu et un toast "Désabonnement confirmé" s'affiche
2. **Given** un utilisateur avec des collections suivies, **When** il clique sur "♥ SUIVI" d'une collection, **Then** la carte disparaît sans rechargement de page
3. **Given** un utilisateur qui se désabonne de tous ses auteurs, **When** la dernière carte disparaît, **Then** un message "Vous ne suivez aucun auteur pour le moment" s'affiche

---

### User Story 5 — Modifier ses informations de compte (Priority: P2)

Dans la section "Paramètres & Sécurité", l'utilisateur peut modifier son pseudonyme, son adresse email, sa région et sa photo de profil. Il peut également délier son profil de forum (Taverne) ou ses services tiers (Google OAuth). Il peut changer son mot de passe si son compte utilise des identifiants classiques.

**Why this priority**: Fonctionnalité de gestion de compte standard. Requise pour maintenir des informations à jour.

**Independent Test**: Modifier le pseudonyme depuis l'interface → vérifier la mise à jour dans le bandeau profil. Modifier l'email → vérifier que le changement est pris en compte. Tester le bouton "Changer" du mot de passe pour un compte classique et vérifier l'ouverture du formulaire approprié.

**Acceptance Scenarios**:

1. **Given** un utilisateur en section Paramètres, **When** il clique "Modifier" en face de son pseudonyme, **Then** un champ éditable ou une modale s'ouvre avec le pseudonyme actuel pré-rempli
2. **Given** un utilisateur ayant modifié son pseudonyme, **When** il valide, **Then** le nouveau pseudonyme s'affiche dans le bandeau profil sans rechargement complet
3. **Given** un utilisateur dont le compte est lié à Google OAuth, **When** il clique "Délier" en face du service Google, **Then** une modale de confirmation s'affiche avant tout changement
4. **Given** un utilisateur avec des identifiants classiques (email + mot de passe), **When** il clique "Changer" en face du mot de passe, **Then** le formulaire de changement de mot de passe s'ouvre

---

### User Story 6 — Consulter son rôle et sa progression (Priority: P2)

La section "Rôle & Permissions" s'adapte au type d'utilisateur. Un modérateur ou administrateur voit un encart bleu avec l'intitulé de son rôle et la liste exhaustive de ses permissions (coche verte = accordée, tiret gris = refusée). Un utilisateur standard (Aventurier) voit son rang de gamification actuel et la distance exacte qui le sépare du rang supérieur.

**Why this priority**: Transparence sur les droits et motivation par la progression. Réutilise la logique de gamification existante (feature 019).

**Independent Test**: Se connecter avec un compte ROLE_MODERATOR et vérifier l'encart bleu avec les permissions. Se connecter avec un compte ROLE_USER et vérifier l'affichage du rang actuel et du message de progression vers le rang suivant.

**Acceptance Scenarios**:

1. **Given** un utilisateur avec le rôle ROLE_MODERATOR, **When** il consulte la section Rôle & Permissions, **Then** un encart bleu s'affiche avec le titre "MODÉRATEUR DE LA GUILDE" et la liste de ses permissions
2. **Given** un utilisateur avec le rôle ROLE_ADMIN, **When** il consulte la section Rôle & Permissions, **Then** l'encart bleu affiche le titre "ADMINISTRATEUR DE LA GUILDE" avec l'ensemble des permissions accordées
3. **Given** un modérateur ou admin dont une permission est refusée, **When** il consulte la liste, **Then** la permission refusée est affichée avec un tiret gris
4. **Given** un utilisateur standard (ROLE_USER) à 7 contributions validées sur 10 requises pour le rang suivant, **When** il consulte la section, **Then** son rang actuel s'affiche et le message indique "Plus que 3 fiches validées pour atteindre le rang [Nom du rang]"
5. **Given** un utilisateur standard au rang maximum, **When** il consulte la section, **Then** un message de félicitations s'affiche sans indicateur de progression

---

### User Story 7 — Supprimer son compte (Priority: P1)

Un utilisateur souhaitant quitter la plateforme peut supprimer son compte depuis la "Zone de Danger". Cette action irréversible s'accompagne d'une modale de confirmation stricte exigeant de saisir le mot "SUPPRIMER". Les données personnelles sont effacées, mais les contributions validées historiques restent dans le catalogue, réattribuées à un profil anonyme.

**Why this priority**: Obligation légale (RGPD - droit à l'effacement). Nouvelle fonctionnalité backend critique avec des règles métier précises pour préserver l'intégrité du catalogue.

**Independent Test**: Cliquer "Supprimer mon compte" → vérifier l'ouverture de la modale avec le champ de saisie. Saisir "SUPPRIMER" → vérifier l'activation du bouton de confirmation. Confirmer → vérifier la déconnexion, la redirection, et en base de données : avatar/listes/notes effacés, suggestions validées réattribuées au profil fantôme.

**Acceptance Scenarios**:

1. **Given** un utilisateur clique "Supprimer mon compte", **When** la modale s'ouvre, **Then** le bouton de confirmation finale est désactivé jusqu'à ce que "SUPPRIMER" soit exactement saisi dans le champ
2. **Given** un utilisateur saisit "supprimer" (minuscules) dans la modale, **When** il tente de confirmer, **Then** le bouton reste désactivé (casse exacte requise)
3. **Given** un utilisateur confirme correctement la suppression, **When** l'action est exécutée, **Then** sa session est terminée, il est redirigé vers la page d'accueil, et son avatar/listes/notes sont supprimés
4. **Given** l'utilisateur supprimé avait des suggestions validées dans le catalogue, **When** la suppression est traitée, **Then** ces suggestions restent présentes dans le catalogue avec la mention "un ancien aventurier" comme auteur (pas de rupture de l'encyclopédie)
5. **Given** un utilisateur clique "Se déconnecter" (et non "Supprimer"), **When** l'action est exécutée, **Then** seule la session est fermée sans aucune suppression de données

---

### Edge Cases

- Que se passe-t-il si l'utilisateur ferme la modale de suppression sans confirmer ? → La modale se ferme, aucune action n'est effectuée
- Que se passe-t-il si un utilisateur (compte OAuth uniquement, sans mot de passe) tente de délier son OAuth ? → Le bouton "Délier" est désactivé ; un message indique "Définissez un mot de passe avant de délier votre compte Google" — aucune déliaison possible tant qu'un mot de passe n'est pas défini
- Que se passe-t-il si un utilisateur n'a suivi aucun auteur ni collection ? → La section affiche un état vide avec un message d'invite
- Que se passe-t-il si un onglet de liste est vide (ex : "À Lire" avec 0 livre) ? → L'onglet s'affiche avec un état vide et le toggle de visibilité reste fonctionnel
- Que se passe-t-il si la modification d'email échoue (email déjà utilisé) ? → Un message d'erreur s'affiche dans la modale/champ, la modification n'est pas appliquée
- Que se passe-t-il si l'utilisateur ne clique pas le lien de confirmation d'email ? → L'ancien email reste actif ; le lien expire après 24h et l'utilisateur peut relancer la demande
- Que se passe-t-il si l'utilisateur tente d'accéder à `/profil` sans être connecté ? → Redirection vers la page de connexion
- Que se passe-t-il pour l'avatar si l'utilisateur n'en a pas défini ? → Un avatar généré (initiales ou image par défaut) est affiché

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Le système DOIT afficher la page `/profil` uniquement aux utilisateurs connectés ; les visiteurs non connectés sont redirigés vers la page de connexion
- **FR-002**: Le bandeau DOIT afficher l'avatar, le pseudonyme, le badge de rôle (pour ROLE_MODERATOR et ROLE_ADMIN uniquement), la date d'inscription (absolue + relative), un lien externe vers le profil de forum Taverne (URL stockée sur `User`, affichée tel quel), et la guilde régionale
- **FR-003**: Le bandeau DOIT afficher quatre KPIs : (1) total livres + répartition en cours/terminés + tendance mensuelle, (2) nombre d'avis + note moyenne, (3) nombre d'entités `Suggestion` au statut `VALIDATED` uniquement (les `CorrectionProposal` ne comptent **pas** dans ce KPI) + taux d'acceptation calculé sur les suggestions finalisées uniquement — dénominateur = `VALIDATED + REJECTED`, suggestions en attente (`PENDING`) exclues du calcul, (4) streak de connexion en jours — calculé depuis les champs `loginStreak` et `lastLoginDate` à ajouter sur l'entité `User`
- **FR-004**: La section "Mes Listes" DOIT comporter 4 onglets : Ma Collection, À Lire, À Acheter, Mes Favoris
- **FR-005**: Chaque onglet DOIT disposer d'un toggle "Publique / Privée" dont l'état est persisté via un **controller Symfony** (pas d'endpoint API REST) mettant à jour directement l'entité `UserList`, et est propre à chaque liste. En cas de succès : toast "Visibilité mise à jour". En cas d'erreur serveur : toast "Erreur de mise à jour — veuillez réessayer" ; l'état du toggle est rétabli à sa valeur précédente (rollback visuel).
- **FR-006**: La base de données DOIT stocker un flag booléen `isPublic` sur le modèle des listes utilisateur, avec une valeur par défaut à `false` (privé)
- **FR-007**: La section "Mes Listes" DOIT permettre le basculement entre vue grille et vue liste (tableau condensé). Les livres sont paginés à **20 par page** avec navigation prev/next ; la pagination s'applique à chaque onglet indépendamment
- **FR-008**: La section "Mes Auteurs & Collections Suivis" DOIT afficher les entités suivies et permettre le désabonnement depuis chaque carte, avec disparition dynamique (animation + toast) sans rechargement. Toast de succès : "Désabonnement confirmé". Toast d'erreur : "Erreur lors du désabonnement — veuillez réessayer" ; la carte reste visible si l'opération échoue.
- **FR-009**: La section "Paramètres & Sécurité" DOIT permettre la modification du pseudonyme (immédiat), de l'email (double opt-in par lien de confirmation envoyé au nouvel email), de la région et de la photo de profil via modale ou champ inline. L'upload d'avatar est limité à **2 Mo max**, formats **JPG/PNG/WebP** uniquement, avec **recadrage carré obligatoire côté client** avant envoi. Les fichiers sont stockés dans **`public/uploads/avatars/`** sur le filesystem local et servis directement par le webserver. Upload d'avatar réussi : toast "Avatar mis à jour". Erreur d'upload (format, taille, serveur) : message d'erreur inline dans le composant d'upload (pas de toast — l'erreur est contextuelle).
- **FR-010**: La section "Paramètres & Sécurité" DOIT permettre la gestion des méthodes d'authentification (délier OAuth Google, changer le mot de passe pour les comptes classiques). Le lien Taverne dans le bandeau est un lien externe simple (pas de déliaison gérée ici). La déliaison OAuth Google est **bloquée** si l'utilisateur n'a aucun mot de passe défini — le bouton "Délier" est désactivé avec message explicatif "Définissez un mot de passe avant de délier votre compte Google"
- **FR-011**: La section "Rôle & Permissions" DOIT afficher un encart bleu avec les permissions listées pour ROLE_MODERATOR et ROLE_ADMIN, ou le rang de gamification avec l'indicateur de progression pour ROLE_USER. L'encart ROLE_MODERATOR affiche "Niveau 2 · sur 3" avec les 8 permissions définies dans `design/pages/profil.html` (6 accordées, 2 refusées). L'encart ROLE_ADMIN affiche "Niveau 3 · sur 3", titre "Administrateur de la guilde", description "Tu veilles sur l'intégrité de la guilde dans son ensemble — gestion des aventuriers, configuration de la plateforme et validation des contributions. Tes actes sont consignés dans le grand registre.", et 9 permissions toutes accordées (aucune refusée) : Lire le catalogue intégral · Déposer notes & commentaires · Suggérer de nouvelles fiches · Valider les suggestions des autres · Corriger les fiches existantes · Signaler du contenu · Bannir des aventuriers · Modifier les rôles d'autrui · Accéder aux paramètres d'administration. **État zéro (gamification)** : si `ContributorLevelService` retourne null ou si la table `ContributorLevel` n'est pas peuplée, la section affiche "Aucune donnée de rang disponible" sans indicateur de progression — aucun rang fictif ne doit être affiché.
- **FR-012**: Le bouton "Supprimer mon compte" DOIT ouvrir une modale exigeant la saisie exacte du mot "SUPPRIMER" (casse stricte) avant d'activer le bouton de confirmation
- **FR-013**: La suppression de compte DOIT anonymiser le `User` (soft delete : `deleted_at` posé ; `email` et `pseudo`/`displayName` remplacés par la chaîne littérale `[deleted]` — cohérent avec 004 FR-011 ; `avatarUrl` et `googleId` nullés ; `pendingEmail`, `emailChangeToken`, `emailTokenExpiresAt`, `password` effacés) ; réattribuer au profil fantôme toutes les `Suggestion` (statut `VALIDATED`) **et** toutes les `CorrectionProposal` (statut `PUBLISHED`) dont l'utilisateur est auteur — les deux types pointent vers le GhostUser (cette approche remplace la mise à null de l'auteur définie en 004 FR-011 : pour les suppressions auto-initiées, le GhostUser est utilisé à la place) ; les listes et notes de l'utilisateur sont supprimées. La libération de `googleId` (null) permet à ce compte Google d'être associé à un nouveau compte ultérieurement.
- **FR-015**: Le profil fantôme (`email: ghost@deleted.local`) DOIT être protégé contre toute modification ou suppression, y compris les demandes de suppression RGPD — aucune opération utilisateur ne peut le cibler comme destinataire d'une action destructive
- **FR-014**: La suppression de compte DOIT terminer la session de l'utilisateur et le rediriger vers la page d'accueil
- **FR-016**: Toute suppression de compte auto-initiée DOIT générer un enregistrement dans la table `moderation_log` avec `target_entity_type = 'User'`, `target_entity_id` = UUID du compte supprimé, `action = 'ACCOUNT_DELETED'`, `moderator_id = null` (action auto-initiée, pas de modérateur), et l'horodatage. Cet enregistrement est conservé après la purge définitive de la ligne `User` correspondante (pas de FK hard sur `moderator_id`).

### Key Entities *(include if feature involves data)*

- **UserList** (modèle existant étendu): Représente une liste de livres de l'utilisateur (Ma Collection, À Lire, etc.). Nouvel attribut : `isPublic` (booléen, défaut : faux). Lié à un utilisateur et contenant des références à des livres.
- **User** (modèle existant étendu): Profil utilisateur. Attributs consultés : avatar, pseudonyme, email, région, date d'inscription, guilde, rôle, méthodes d'authentification liées. Nouveaux attributs à ajouter : `loginStreak` (entier), `lastLoginDate` (date), `deletedAt` (datetime nullable, soft delete), `pendingEmail` (string nullable), `emailChangeToken` (string nullable), `emailTokenExpiresAt` (datetime nullable).
- **GhostUser** (enregistrement `User` spécial, créé par migration Doctrine): Compte anonyme identifié par `email: ghost@deleted.local`, pseudonyme "un ancien aventurier". Toutes les suggestions validées d'un compte supprimé lui sont réattribuées. Jamais supprimé ni modifié après création.
- **UserSuggestion** (modèle existant): Suggestion soumise par un utilisateur. Lors d'une suppression, les suggestions validées conservent leur entrée dans le catalogue mais voient leur auteur pointer vers le GhostUser.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un utilisateur connecté peut accéder à l'ensemble des sections de sa page profil en moins de 2 secondes après navigation vers `/profil`
- **SC-002**: Le basculement du toggle de visibilité d'une liste (Publique/Privée) est reflété sur le profil public en moins de 3 secondes
- **SC-003**: L'action de désabonnement d'un auteur ou d'une collection produit une mise à jour visuelle (disparition + toast) en moins de 1 seconde perçue par l'utilisateur
- **SC-004**: La suppression de compte via la procédure complète (modale + saisie + confirmation) s'exécute en moins de 5 secondes et aucune donnée personnelle ne subsiste en base de données après l'opération
- **SC-005**: 100% des suggestions validées d'un compte supprimé restent présentes dans le catalogue, réattribuées au profil fantôme — aucune rupture de l'encyclopédie
- **SC-006**: La page profil est entièrement fonctionnelle et conforme aux maquettes de référence (`design/pages/profil.html`) pour tous les cas d'affichage conditionnel (ROLE_USER, ROLE_MODERATOR, ROLE_ADMIN)
- **SC-007**: Aucune donnée de liste privée d'un utilisateur n'est accessible sur le profil public d'un autre membre

---

## Assumptions

- Le profil public des utilisateurs (`/profil/{pseudonyme}` ou similaire) existe ou sera créé séparément ; la feature 026 couvre uniquement la vue privée `/profil` (Mon Profil)
- Le suivi des **collections** est déjà implémenté (`UserCollectionSubscription` + repo existants) — cette spec couvre uniquement l'ajout du désabonnement. Le suivi des **auteurs** n'existe pas encore : `UserContributorSubscription` et son repo sont à créer dans cette feature (026), de même que le câblage du filtre `onlyFollowed` dans `ContributorRepository::applyFilters()` (scaffoldé mais non implémenté)
- Le système de rangs de gamification (feature 019) est déjà implémenté et ses données sont consommables par cette page
- Le streak de connexion n'est **pas** encore stocké en base ; cette feature doit ajouter `loginStreak` (int) et `lastLoginDate` (date) sur `User`, et implémenter la logique de mise à jour du streak à chaque authentification réussie. **Règle fuseau utilisateur** (cohérent avec feature 017 FR-004) : le calcul du "jour courant" utilise `User.timezone` — fallback UTC si null. Règle : si `lastLoginDate` = hier (dans le TZ utilisateur) → `loginStreak++` ; si `lastLoginDate` < hier → reset à 1 ; si `lastLoginDate` = aujourd'hui → aucun changement (connexions multiples ignorées). **Règle de premier démarrage** : si `lastLoginDate` est null (première connexion), `loginStreak` est initialisé à 1 et `lastLoginDate` posé à la date locale de l'utilisateur. **Migration des utilisateurs existants** : après l'ajout des colonnes via migration Doctrine, les utilisateurs existants reçoivent `loginStreak = 0` et `lastLoginDate = null` — streak initialisé à 1 à la prochaine connexion.
- Le système de notifications (feature 017) est disponible pour les toasts et éventuels messages de confirmation
- Le profil fantôme ("un ancien aventurier") est inséré via une **migration Doctrine dédiée** exécutée une seule fois ; toutes les suppressions de compte pointent vers ce même enregistrement identifié par un identifiant stable (ex : `email: ghost@deleted.local`)
- La suppression de compte utilise un **soft delete avec anonymisation** : la ligne `User` est conservée en base (intégrité référentielle), `deleted_at` est posé, et les champs PII (email, avatar, pseudonyme) sont nullés ou remplacés par des valeurs anonymes. Aucune suppression en cascade.
- La modification d'email requiert un **double opt-in** : un lien de confirmation est envoyé au nouvel email ; l'ancien email reste actif jusqu'à confirmation. Le token et le nouvel email en attente sont stockés dans trois champs sur `User` : `pendingEmail`, `emailChangeToken`, `emailTokenExpiresAt` (expiration 24h). La modification du pseudonyme est immédiate (pas de confirmation requise).
- Le design fidèle à `design/pages/profil.html` fait foi pour tous les détails visuels (couleurs, espacements, typographie, composants) non mentionnés explicitement dans cette spec
- La conservation de la ligne `User` après suppression (soft delete) repose sur l'**intérêt légitime** de maintien de la traçabilité des actions de modération et de l'intégrité du catalogue (base légale RGPD Art. 6(1)(f)) — la ligne anonymisée ne contient plus aucune donnée permettant d'identifier la personne
- Les lignes `User` anonymisées (soft-supprimées) sont conservées **30 jours** puis effacées définitivement par une tâche planifiée ; la mise en place de cette tâche est **hors périmètre** de la feature 026 et fera l'objet d'une spec dédiée
- Le droit à la portabilité des données (RGPD Art. 20 — export avant suppression) est **explicitement hors périmètre** de cette feature
