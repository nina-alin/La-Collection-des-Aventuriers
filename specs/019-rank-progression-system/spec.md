# Feature Specification: Système de Rangs et Progression

**Feature Branch**: `019-rank-progression-system`

**Created**: 2026-06-04

**Status**: Draft

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Affichage du rang dans le profil et les menus (Priority: P1)

Un utilisateur standard connecté voit son titre de rang (ex : "CHRONIQUEUR") affiché sous son nom dans le menu de profil de la barre de navigation, ainsi que dans les sections où son identité est visible (commentaires, suggestions, etc.). Un utilisateur modérateur ou administrateur ne voit pas ce titre — seul son badge de fonction est affiché.

**Why this priority**: C'est la manifestation principale du système de gamification. Sans affichage, le rang n'existe pas aux yeux de l'utilisateur.

**Independent Test**: Peut être testé en se connectant avec un compte ROLE_USER ayant des suggestions validées, et en vérifiant l'affichage du titre dans le menu de profil. Tester séparément avec un compte ROLE_MODERATOR pour vérifier l'absence du titre.

**Acceptance Scenarios**:

1. **Given** un utilisateur avec le rôle ROLE_USER et 12 suggestions validées, **When** il consulte le menu de profil, **Then** son titre de rang actuel (ex : "CHRONIQUEUR") est affiché sous son nom
2. **Given** un utilisateur avec le rôle ROLE_MODERATOR, **When** il consulte le menu de profil, **Then** aucun titre de rang n'est affiché — seul le badge "Modérateur" est visible
3. **Given** un utilisateur avec le rôle ROLE_ADMIN, **When** il consulte le menu de profil, **Then** aucun titre de rang n'est affiché — seul le badge "Administrateur" est visible
4. **Given** un utilisateur avec 0 suggestion validée, **When** il consulte le menu de profil, **Then** le titre du rang initial (ex : "NOVICE") est affiché

---

### User Story 2 - Bandeau dynamique sur le tableau de bord des suggestions (Priority: P1)

Un utilisateur consulte la page "Mes suggestions". Le bandeau d'encouragement en haut du tableau affiche dynamiquement la distance qui le sépare du rang suivant (ex : "Tu es à 3 fiches du rang Archiviste."). Si l'utilisateur est au rang maximal, le message s'adapte pour le féliciter.

**Why this priority**: C'est le principal mécanisme d'engagement et de motivation visible quotidiennement par les contributeurs actifs.

**Independent Test**: Peut être testé en vérifiant le message affiché pour un utilisateur à différents stades de progression (ex : 7 validées sur 10 requises pour le prochain rang → message "Tu es à 3 fiches du rang Chroniqueur.").

**Acceptance Scenarios**:

1. **Given** un utilisateur a 7 suggestions validées et que le prochain rang (seuil 10) s'appelle "Chroniqueur", **When** il accède à son tableau de bord suggestions, **Then** le bandeau affiche "Tu es à 3 fiches du rang Chroniqueur."
2. **Given** un utilisateur a atteint le rang maximal, **When** il accède au tableau de bord, **Then** le bandeau affiche un message de félicitations pour le rang suprême atteint (ex : "Tu as atteint le rang suprême d'Érudit. Merci pour tes contributions inestimables !")
3. **Given** un utilisateur a 0 suggestion validée, **When** il accède au tableau de bord, **Then** le bandeau affiche le delta vers le premier rang non-initial

---

### User Story 3 - Notification de passage de rang (Priority: P2)

Lorsqu'un modérateur valide une suggestion et que cette validation fait franchir un palier de rang à l'auteur, celui-ci reçoit une notification dans le centre de notifications existant. La notification indique le nom du nouveau rang atteint et contient un lien vers son profil ou tableau de bord.

**Why this priority**: Renforce l'engagement en temps réel. Dépend du système de notifications existant (feature 017).

**Independent Test**: Peut être testé en validant la N-ième suggestion d'un utilisateur dont le compteur atteint exactement le seuil d'un nouveau rang, puis en vérifiant la présence d'une notification de type "progression de rang" dans son centre de notifications.

**Acceptance Scenarios**:

1. **Given** un utilisateur a 9 suggestions validées et que le seuil du rang "Chroniqueur" est 10, **When** un modérateur valide sa 10e suggestion, **Then** une notification de type progression apparaît dans son centre de notifications avec le message "Félicitations — tu viens d'atteindre le rang Chroniqueur."
2. **Given** un modérateur valide une suggestion sans franchissement de palier, **When** la validation est effectuée, **Then** aucune notification de progression n'est générée
3. **Given** la notification de rang est générée, **When** l'utilisateur clique dessus, **Then** il est redirigé vers son tableau de bord suggestions

---

### User Story 4 - Configuration de la matrice des rangs (Priority: P2)

L'application dispose d'une grille de progression pré-configurée définissant les paliers et noms de rangs. La grille peut être mise à jour sans déploiement de code (ex : via données de configuration ou fixtures dédiées).

**Why this priority**: Sans données de configuration, le système de rangs ne peut pas fonctionner. La configurabilité évite de recoder pour ajuster les seuils.

**Independent Test**: Peut être testé en vérifiant que les rangs existent en base avec les bons seuils, et qu'un utilisateur avec N suggestions validées se voit attribuer le bon rang selon la grille.

**Acceptance Scenarios**:

1. **Given** la grille de progression est définie (ex : Novice 0, Chroniqueur 10, Archiviste 50, Aventurier 100, Érudit 250), **When** un utilisateur atteint exactement le seuil d'un rang, **Then** ce rang lui est attribué
2. **Given** un utilisateur est entre deux seuils (ex : 30 validations), **When** le système calcule son rang, **Then** il reçoit le rang le plus élevé dont le seuil est inférieur ou égal à son compteur

---

### Edge Cases

- Que se passe-t-il si deux validations simultanées font franchir le même palier ? Une seule notification de rang est générée.
- Que se passe-t-il si la grille de rangs est vide ou non configurée ? Aucun rang ne s'affiche ; le bandeau suggestions n'affiche pas de delta.
- Que se passe-t-il si un modérateur est aussi rédacteur actif (a des suggestions validées) ? Son rang est calculé normalement mais non affiché (règle RBAC).
- Que se passe-t-il si une suggestion validée est annulée ultérieurement ? Le compteur descend mais aucune notification de "régression" n'est envoyée — l'affichage du rang se met simplement à jour.

## Clarifications

### Session 2026-06-05 (checklist review)

- Q: CorrectionProposal status when approved — 'VALIDATED' or 'PUBLISHED'? → A: `'PUBLISHED'` (raw string in `ModerationService::approve()`). `ContributorLevelService` MUST query CorrectionProposalRepository with `status = 'PUBLISHED'`, not `SuggestionStatus::VALIDATED`. `ModerationService::approve()` must dispatch `ContributionValidatedEvent` for CorrectionProposal (currently only dispatched for WorkEntry).
- Q: 5 colors defined in FR-004 for 6 ranks — intentional or gap? → A: Gap — 6th color missing. FR-004 updated with `[COULEUR_RANG_6_TBD]` placeholder. ~~Must be defined before UI implementation.~~ **Résolu** — FR-004 définit les 6 couleurs dont Grand Sage=`--cuir`. Aucune action requise.
- Q: Behavior when validation skips multiple rank thresholds? → A: Only the final rank achieved triggers notification — intermediate ranks ignored. Assumption updated accordingly.
- Q: SC-004 vs FR-011 conflict (preference suppression)? → A: FR-011 removed — rank-up notification is always generated, no preference condition. SC-004 correct as written.

### Session 2026-06-05

- Q: Backend scope — ContributorLevelService already includes CorrectionProposal or needs updating? → A: Service update NOT yet done. Adding CorrectionProposal counting to ContributorLevelService is in-scope for this feature (not pre-existing).
- Q: N+1 query strategy for badge rendering in user lists (suggestion rows, contributors list, comments)? → A: Single aggregate query per list render — fetch all visible user counts in one JOIN/subquery, not one query per user.
- Q: Badge visual design — distinct color per rank or uniform style? → A: Distinct color per rank tier (e.g., grey → green → blue → purple → gold as rank increases).

### Session 2026-06-05 (requirements check)

- Q: `moderateSuggestion()` dispatches no `ContributionValidatedEvent` — is this a gap? → A: Yes, gap. `moderateSuggestion()` MUST dispatch `ContributionValidatedEvent('une suggestion', $suggestion->getUser())` when `newStatus === SuggestionStatus::VALIDATED`. Without this, rank-up notification never fires (rank counts `Suggestion.VALIDATED` but `approve()` handles `WorkEntry/CorrectionProposal` — WorkEntry approval does not change Suggestion count). Added task T008b.
- Q: `ContributionValidatedEvent` `sourceId` after T003 refactor (WorkEntry → string)? → A: Set `sourceId: null`. No deduplication needed.
- Q: T020 banner logic — reinventing delta in controller vs calling service? → A: Extend `getMetrics()` to return `nextLevel` (`?ContributorLevel`). T020 calls `getMetrics()` only — no controller logic. C1 constitution violation resolved.
- Q: `Suggestion` has no `title` field — what passes as `$title` in `ContributionValidatedEvent` from `moderateSuggestion()`? → A: Generic string `'une suggestion'`.

### Session 2026-06-04

- Q: Is the user's current rank stored as a field on User entity or computed on-the-fly? → A: Fully computed on-the-fly via `ContributorLevelService::computeRank()` — raw DB count of validated suggestions, no cached field on User.
- Q: Where is ContributorLevel data stored? → A: DB entity (`ContributorLevel` table), seeded via Doctrine fixtures (`ContributorLevelFixture`).
- Q: Does `ContributorLevel` entity and rank service need to be created or do they already exist? → A: Full backend implementation exists (entity, repository, service, fixture, event listeners, notification wiring). Feature scope is UI integration + tests.
- Q: Does CorrectionProposal count toward rank progression alongside Suggestion? → A: Yes — both validated Suggestion and validated CorrectionProposal count. `ContributorLevelService` must be updated to sum both.
- Q: Where should rank title appear beyond the profile menu? → A: All identity zones — profile menu, suggestion list rows, public profile page, contributors list. Comments deferred only if not yet implemented.
- Q: User.validatedSuggestionsCount cached field vs on-the-fly computation — which applies, especially now CorrectionProposals also count? → A: Pure on-the-fly — `ContributorLevelService` queries DB live, summing validated Suggestion + CorrectionProposal counts per request. No cached field on User entity; no migration required.
- Q: Notification click target — public profile or suggestions dashboard? → A: Suggestions dashboard (`/mes-suggestions`). More actionable: user lands where they can see progress and continue contributing.
- Q: Rank display format in identity zones (suggestion rows, contributors list, public profile) — text only or badge? → A: Small colored/styled badge + text title in all identity zones.
- Q: Comments identity zone — is comment display UI already implemented? → A: Yes — comments UI exists. Rank badge IS in scope for comment zones in this feature.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Le système DOIT calculer le rang d'un utilisateur en comptant la somme de ses `Suggestion` avec statut `VALIDATED` ET de ses `CorrectionProposal` avec statut `'PUBLISHED'`. `ContributorLevelService` doit intégrer `CorrectionProposalRepository` pour agréger les deux compteurs via deux requêtes distinctes ou un UNION. Note : les deux entités utilisent des valeurs de statut différentes (`SuggestionStatus::VALIDATED` pour Suggestion, chaîne brute `'PUBLISHED'` pour CorrectionProposal).
- **FR-002**: Les suggestions en statut "en attente" ou "refusée" NE DOIVENT PAS contribuer au compteur de progression.
- **FR-003**: La grille des rangs DOIT être stockée en base de données via une entité `ContributorLevel` (nom, numéro d'ordre, seuil), initialisée et maintenue par des fixtures Doctrine. La mise à jour des seuils ne nécessite pas de modification de code applicatif.
- **FR-004**: Le système DOIT afficher le rang de l'utilisateur dans toutes les zones d'identité existantes : menu de profil (déjà câblé), lignes de la liste de suggestions, page de profil public, liste des contributeurs, et zones de commentaires (UI existante). Le rendu DOIT utiliser un badge coloré/stylisé accompagné du titre textuel du rang, sauf dans le menu de profil où le titre seul suffit. Chaque palier de rang DOIT avoir une couleur de badge distincte — 6 couleurs pour 6 rangs, une par palier. Correspondance rang/token de design : Novice=`--parchemin`, Apprenti=`--mousse`, Chroniqueur confirmé=`--encre`, Archiviste=`--ambre`, Érudit=`--or`, Grand Sage=`--cuir` (couleur primaire de la marque — apex). Le rendu des badges dans les listes DOIT utiliser une requête agrégée unique (JOIN ou sous-requête) pour récupérer les compteurs de tous les utilisateurs visibles en un seul appel DB — pas de requête par utilisateur (N+1 interdit).
- **FR-005**: Le titre du rang DOIT être masqué pour les utilisateurs possédant le rôle Modérateur ou Administrateur — leur badge de fonction prime.
- **FR-006**: Le bandeau du tableau de bord des suggestions DOIT afficher dynamiquement le nombre de validations manquantes pour atteindre le prochain rang (delta calculé).
- **FR-007**: Si l'utilisateur a atteint le rang maximal, le bandeau DOIT afficher un message de félicitations adapté à la place du delta.
- **FR-008**: Lors de la validation d'une `Suggestion` par un modérateur (via `ModerationService::moderateSuggestion()` quand `newStatus === SuggestionStatus::VALIDATED`) ou d'une `CorrectionProposal` (via `ModerationService::approve()`), le système DOIT dispatcher un `ContributionValidatedEvent` pour l'auteur. Pour `Suggestion`, le titre passé dans l'événement est la chaîne générique `'une suggestion'` (l'entité ne dispose pas de champ titre). Le listener DOIT détecter si le nouveau compteur de l'auteur franchit le seuil d'un rang supérieur. En cas de saut de plusieurs paliers (compteur passant au-delà de plusieurs seuils en une validation), seule la notification du rang final atteint est générée — les rangs intermédiaires ne donnent pas lieu à notification.
- **FR-009**: En cas de franchissement de palier, le système DOIT générer une notification de type "progression de rang" via le système de notifications existant, avec un message dynamique incluant le nom du nouveau rang.
- **FR-010**: La notification de rang-up DOIT contenir un lien vers le tableau de bord suggestions de l'utilisateur (`/mes-suggestions`).
- **FR-011**: ~~Supprimé~~ — La notification de rang-up est toujours générée, sans condition de préférence utilisateur. SC-004 s'applique sans exception.

### Key Entities

- **ContributorLevel** : Représente un palier de rang. Attributs : nom du rang, numéro d'ordre, seuil de validations requis.
- **User** : Le rang courant est calculé dynamiquement à partir d'un comptage live en base (pas de champ dénormalisé). Possède un rôle (USER / MODERATOR / ADMIN) qui conditionne l'affichage du rang.
- **Suggestion** : Contribue au compteur uniquement lorsqu'elle passe au statut "validée".
- **Notification** : Générée lors d'un franchissement de palier, de type "progression de rang", avec message et lien cible.
- **NotificationPreference** : Champ `rankUp` présent en base mais NON évalué par `RankUpListener` — la notification de rang-up se génère de façon inconditionnelle (voir FR-011 supprimé, Assumptions §154). Le champ peut être ignoré ou retiré du scope.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% des utilisateurs avec le rôle ROLE_USER voient leur titre de rang affiché dans le menu de profil.
- **SC-002**: 0% des utilisateurs avec le rôle ROLE_MODERATOR ou ROLE_ADMIN voient un titre de rang affiché.
- **SC-003**: Le bandeau dynamique affiche le bon delta pour 100% des cas testés (utilisateur en cours, au rang max, à 0 validation).
- **SC-004**: Une notification de progression est générée dans 100% des cas où une validation franchit exactement un palier de rang.
- **SC-005**: Aucune notification de progression n'est générée lorsque la validation n'atteint pas un seuil.
- **SC-006**: La grille de rangs peut être mise à jour sans modification de code applicatif.

## Assumptions

- Le système de notifications (feature 017) est déployé et opérationnel — cette feature l'utilise comme dépendance. Confirmé présent en codebase (commit 3732de1).
- L'implémentation backend est partiellement complète : entité `ContributorLevel`, `ContributorLevelRepository`, `ContributorLevelService` (à mettre à jour pour intégrer `CorrectionProposalRepository` — voir FR-001), `ContributorLevelFixture`, `ContributionValidatedListener` (détecte le rang-up), `RankUpListener` (crée la notification), `NotificationType::RANK_UP`. Le scope de cette feature est : mise à jour de `ContributorLevelService` (FR-001), mise à jour de `ModerationService::approve()` pour dispatcher `ContributionValidatedEvent` sur CorrectionProposal (FR-008), intégration UI (Twig/templates), tests, et vérification du câblage.
- La matrice des rangs est déjà définie en base via `ContributorLevelFixture` : Novice (0), Apprenti (5), Chroniqueur confirmé (15), Archiviste (30), Érudit (60), Grand Sage (100). Ces valeurs et noms sont autoritaires — les exemples dans les user stories (Chroniqueur, Archiviste, Aventurier, Érudit, noms et seuils différents) sont purement illustratifs et ne reflètent pas la grille réelle.
- Les `CorrectionProposal` approuvées (statut `'PUBLISHED'`) comptent au même titre que les `Suggestion` validées (statut `SuggestionStatus::VALIDATED`) pour la progression (FR-001). Les deux entités utilisent des valeurs de statut distinctes — voir FR-001 pour la précision d'implémentation.
- L'affichage du rang couvre toutes les zones d'identité existantes : menu profil, liste suggestions, profil public, contributeurs, et zones de commentaires (UI déjà implémentée — inclus dans le scope).
- En cas de saut de plusieurs paliers en une seule validation (mathématiquement possible avec la grille actuelle), seule la notification du rang final atteint est générée — les rangs intermédiaires sont ignorés. L'affichage du rang reflète toujours le rang réel calculé, quel que soit le saut.
- La notification de rang-up est toujours générée lors d'un franchissement de palier — la préférence `NotificationPreference.rankUp` existe en base mais ne conditionne PAS la création de la notification pour ce type. Le champ peut être ignoré ou retiré du scope. SC-004 s'applique sans exception.
