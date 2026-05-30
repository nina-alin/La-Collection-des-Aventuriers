# Feature Specification: Auth Pages — Nouveau Design

**Feature Branch**: `010-auth-pages`

**Created**: 2026-05-30

**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Connexion avec le nouveau design (Priority: P1)

Un utilisateur déjà inscrit accède à `/connexion` et voit une page au nouveau design split-screen : volet gauche illustré (statistiques, liste de bénéfices, branding), volet droit avec le formulaire de connexion. Il peut se connecter via Google ou via e-mail + mot de passe. Il peut afficher/masquer son mot de passe d'un clic. Il peut cocher « Rester connecté ». Si ses identifiants sont incorrects ou que l'IP est bloquée par la protection anti-brute-force, il voit un message d'erreur clair. En cas de succès, il est redirigé vers sa collection.

**Why this priority**: Point d'entrée principal ; toute la communauté l'utilise. Le design actuel est un formulaire simple centré, sans cohérence visuelle avec le reste du site.

**Independent Test**: Ouvrir `/connexion` dans un navigateur, vérifier la mise en page split-screen, saisir des identifiants valides → redirection ; saisir des identifiants incorrects → message d'erreur visible.

**Acceptance Scenarios**:

1. **Given** un utilisateur non connecté, **When** il accède à `/connexion`, **Then** il voit la page au format split-screen avec volet couverture à gauche et formulaire à droite.
2. **Given** le formulaire de connexion, **When** l'utilisateur clique sur l'icône œil dans le champ mot de passe, **Then** le texte du mot de passe bascule entre masqué et visible.
3. **Given** le formulaire de connexion, **When** l'utilisateur saisit un e-mail et mot de passe valides et soumet, **Then** il est redirigé vers sa collection.
4. **Given** le formulaire de connexion, **When** l'utilisateur soumet des identifiants incorrects, **Then** un message d'erreur s'affiche dans la zone prévue à cet effet.
5. **Given** une IP bloquée par brute-force, **When** l'utilisateur accède à `/connexion`, **Then** un bandeau d'avertissement avec le temps restant est visible et le bouton de soumission est désactivé.
6. **Given** le formulaire de connexion, **When** l'utilisateur clique sur « Continuer avec Google », **Then** il est redirigé vers le flux OAuth Google.
7. **Given** un utilisateur inscrit dont l'e-mail n'est pas encore vérifié, **When** il soumet ses identifiants valides, **Then** la connexion est refusée et la zone d'erreur affiche « Vérifie ta boîte de réception » avec un lien de renvoi du courriel.

---

### User Story 2 — Inscription avec jauge de robustesse et confirmation e-mail (Priority: P1)

Un nouvel utilisateur accède à `/inscription` et voit le nouveau design split-screen. Il peut s'inscrire via Google OAuth (même bouton que sur `/connexion`) ou via le formulaire e-mail + pseudo + mot de passe. Le formulaire comprend trois champs : pseudo, e-mail, mot de passe. Pendant la saisie du mot de passe, une jauge visuelle et une checklist live indiquent en temps réel quels critères sont remplis (8 caractères min., majuscule + minuscule, chiffre, symbole). L'utilisateur doit cocher la case CGU avant de soumettre. Après soumission réussie via formulaire, la vue bascule vers un état « Vérifie ta boîte de réception » sans rechargement de page. Après soumission via Google OAuth, l'utilisateur est redirigé vers sa collection.

**Why this priority**: Première impression de la plateforme ; la jauge de robustesse réduit les mots de passe faibles et le flow de confirmation e-mail est attendu par les nouvelles inscriptions.

**Independent Test**: Ouvrir `/inscription`, saisir un pseudo + e-mail + mot de passe fort, cocher CGU, soumettre → la vue formulaire disparaît et un état de confirmation e-mail s'affiche sans rechargement de page complet.

**Acceptance Scenarios**:

1. **Given** le formulaire d'inscription, **When** l'utilisateur tape dans le champ mot de passe, **Then** la jauge de robustesse et la checklist se mettent à jour en temps réel.
2. **Given** la checklist de robustesse, **When** un critère est rempli, **Then** son item passe à l'état validé visuellement (icône cochée).
3. **Given** un formulaire d'inscription valide (pseudo unique, e-mail non utilisé, mot de passe fort, CGU cochées), **When** l'utilisateur soumet, **Then** la vue bascule vers l'état « Vérifie ta boîte de réception » sans rechargement complet de la page.
4. **Given** l'état « Vérifie ta boîte de réception », **When** l'utilisateur clique sur « Renvoyer le lien », **Then** un nouveau courriel de confirmation est envoyé.
5. **Given** le formulaire d'inscription, **When** la case CGU n'est pas cochée et l'utilisateur soumet, **Then** le formulaire refuse la soumission avec un message d'erreur.
6. **Given** un e-mail ou un pseudo déjà utilisé (qu'il soit vérifié ou non), **When** l'utilisateur soumet, **Then** un message d'erreur générique « Ces informations sont déjà utilisées » s'affiche dans le champ concerné, sans révéler si un compte existe ni son statut de vérification.

---

### User Story 3 — Mot de passe oublié : demande de lien (Priority: P2)

Un utilisateur qui a oublié son mot de passe accède à `/mot-de-passe-oublie` (lien présent sur la page de connexion). Il saisit son adresse e-mail et soumet. La vue bascule vers un état « Lien envoyé » (le message ne révèle pas si l'adresse existe ou non). Un lien est envoyé par e-mail s'il correspond à un compte existant. L'utilisateur peut demander un renvoi depuis l'état de confirmation.

**Why this priority**: Flux de récupération essentiel ; actuellement absent du backend. Bloque les utilisateurs qui ont perdu l'accès.

**Independent Test**: Ouvrir `/mot-de-passe-oublie`, saisir n'importe quelle adresse e-mail, soumettre → l'état « Lien envoyé » s'affiche ; un e-mail est reçu si l'adresse correspond à un compte existant.

**Acceptance Scenarios**:

1. **Given** la page « Mot de passe oublié », **When** l'utilisateur saisit une adresse e-mail et soumet, **Then** la vue bascule vers l'état « Lien envoyé » quel que soit le statut de l'adresse.
2. **Given** une adresse e-mail correspondant à un compte existant, **When** la demande est soumise, **Then** un e-mail contenant un lien de réinitialisation à usage unique valable 30 minutes est envoyé.
3. **Given** une adresse e-mail inconnue, **When** la demande est soumise, **Then** l'état « Lien envoyé » s'affiche sans révéler que l'adresse n'existe pas.
4. **Given** l'état « Lien envoyé », **When** l'utilisateur clique sur « Renvoyer le lien », **Then** un nouveau courriel est envoyé (si l'adresse existe) et l'action est rate-limitée.

---

### User Story 4 — Réinitialisation du mot de passe (Priority: P2)

Un utilisateur clique sur le lien de réinitialisation reçu par e-mail. Il arrive sur `/reinitialiser-mot-de-passe?token=...`. Il saisit un nouveau mot de passe et sa confirmation. La même jauge de robustesse et checklist live sont présentes. Après soumission réussie, toutes les sessions actives sont invalidées, et l'état « Mot de passe mis à jour » s'affiche avec un lien vers la connexion. Si le token est expiré ou invalide, un message d'erreur est affiché.

**Why this priority**: Complète le flux de récupération. Sans cette page fonctionnelle, le lien envoyé en US3 est inutile.

**Independent Test**: Cliquer sur un lien de réinitialisation valide → page avec formulaire de nouveau mot de passe ; saisir un mot de passe fort + confirmation identique, soumettre → état succès affiché, reconnexion avec l'ancien mot de passe échoue.

**Acceptance Scenarios**:

1. **Given** un token de réinitialisation valide (non expiré, non utilisé), **When** l'utilisateur accède à l'URL, **Then** le formulaire de nouveau mot de passe est affiché.
2. **Given** le formulaire de réinitialisation, **When** l'utilisateur saisit un mot de passe, **Then** la jauge de robustesse et la checklist live fonctionnent de la même façon que sur la page d'inscription.
3. **Given** un mot de passe et sa confirmation identiques valides, **When** l'utilisateur soumet, **Then** le mot de passe est mis à jour, toutes les sessions actives sont invalidées, et l'état « Mot de passe mis à jour » s'affiche.
4. **Given** un token expiré ou invalide, **When** l'utilisateur accède à l'URL, **Then** un message d'erreur clair est affiché avec un lien vers « Mot de passe oublié ».
5. **Given** le formulaire de réinitialisation, **When** la confirmation ne correspond pas au mot de passe, **Then** le formulaire refuse la soumission avec un message d'erreur.

---

### Edge Cases

- Que se passe-t-il si l'utilisateur soumet plusieurs fois le formulaire « Mot de passe oublié » pour la même adresse en peu de temps ? (rate-limiting par IP : 5 req/heure, FR-012 et FR-025)
- Que se passe-t-il si l'utilisateur ouvre le lien de réinitialisation dans deux onglets simultanément ? (usage unique : le deuxième affiche un message d'erreur explicite avec un lien vers « Mot de passe oublié », identique au cas token expiré défini dans FR-007)
- Que se passe-t-il si l'utilisateur désactive JavaScript ? (la jauge ne fonctionnera pas ; les formulaires doivent rester soumissibles et validés côté serveur, conforme FR-010)
- Que se passe-t-il si le thème sombre est activé ? (le toggle de thème doit rester fonctionnel sur toutes les pages auth, conforme FR-009)
- Que se passe-t-il si l'utilisateur quitte la page depuis l'état « Vérifie ta boîte de réception » ou « Lien envoyé » et revient ? (l'état n'est pas préservé ; le formulaire s'affiche à nouveau — l'état de confirmation est lié à la navigation JS courante uniquement)
- Que se passe-t-il si l'utilisateur clique sur le bouton précédent/suivant du navigateur sur une page à états multiples ? (comportement navigateur par défaut ; l'état formulaire n'est pas préservé entre navigations)

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Les pages `/connexion`, `/inscription`, `/mot-de-passe-oublie` et `/reinitialiser-mot-de-passe` DOIVENT utiliser la mise en page split-screen définie dans les fichiers de design (volet couverture gauche, formulaire droite). Chaque page possède un contenu de volet gauche spécifique (eyebrow, headline, liste de bénéfices et stats propres à la page) tel que défini dans les maquettes HTML correspondantes.
- **FR-002**: Chaque champ mot de passe DOIT disposer d'un bouton afficher/masquer fonctionnel.
- **FR-003**: La page d'inscription DOIT afficher une jauge de robustesse et une checklist live (4 critères : longueur ≥ 8, majuscule+minuscule, chiffre, symbole) qui se met à jour à chaque frappe.
- **FR-004**: Le formulaire d'inscription DOIT basculer vers l'état « Vérifie ta boîte de réception » après soumission réussie, sans rechargement complet de la page.
- **FR-005**: Le formulaire « Mot de passe oublié » DOIT basculer vers l'état « Lien envoyé » après soumission, sans révéler si l'adresse existe.
- **FR-006**: Le formulaire de réinitialisation DOIT basculer vers l'état « Mot de passe mis à jour » après succès.
- **FR-007**: La page de réinitialisation DOIT valider le token (expiration 30 min, usage unique) et afficher une erreur explicite si invalide.
- **FR-008**: La réinitialisation du mot de passe DOIT invalider toutes les sessions actives sur tous les appareils ET tous les tokens `remember_me` de l'utilisateur concerné (full logout everywhere, tous appareils).
- **FR-009**: Les pages d'auth DOIVENT conserver le toggle de thème (clair/sombre) fonctionnel.
- **FR-010**: Toutes les pages d'auth DOIVENT rester utilisables sans JavaScript (formulaires soumissibles, validations côté serveur).
- **FR-011**: Les composants backend existants suivants NE DOIVENT PAS être modifiés : `BruteForceProtectionService` (seuil : 10 échecs → blocage 15 min), le mécanisme de rate-limiting existant, `UserRegistrationService`. Exception partielle pour `GoogleAuthenticator` : la méthode `onAuthenticationSuccess()` DOIT être modifiée pour (1) persister `googleId`, `displayName` et `avatarUrl` sur un utilisateur existant lors d'une liaison de compte (FR-028), et (2) corriger la redirection de secours de `app_home` vers `app_collection` ; toutes les autres méthodes de `GoogleAuthenticator` NE DOIVENT PAS être modifiées. FR-022 (vérification e-mail à la connexion) constitue un ajout de comportement nouveau, non une modification de l'existant, et nécessite un nouveau champ `isEmailVerified` sur l'entité `User`.
- **FR-014**: La page `/inscription` DOIT proposer le bouton « Continuer avec Google » identique à `/connexion` ; le flux OAuth redirige vers la collection sans état de confirmation e-mail.
- **FR-015**: En dessous de 920 px de largeur de viewport, le volet gauche (couverture) DOIT être masqué ; le formulaire occupe 100 % de la largeur. Au-delà de 920 px, le split-screen est affiché (conforme au breakpoint défini dans `design/assets/auth.css`).
- **FR-016**: À la création d'un nouveau `ResetPasswordToken`, tous les tokens précédents non expirés et non utilisés pour le même utilisateur DOIVENT être invalidés (supprimés ou marqués `used = true`). Cette opération DOIT être atomique avec la création du nouveau token : si la création échoue, l'invalidation est annulée et l'utilisateur doit soumettre une nouvelle demande.
- **FR-017**: Toutes les pages d'auth DOIVENT respecter WCAG 2.1 niveau AA : labels de formulaire explicites, ratio de contraste ≥ 4.5:1, navigation clavier complète, messages d'erreur liés par `aria-describedby`.
- **FR-018**: Le pseudo DOIT contenir entre 3 et 30 caractères ; seuls les caractères alphanumériques, le tiret (`-`) et l'underscore (`_`) sont autorisés. Validation côté serveur ET côté client.
- **FR-012**: La demande de réinitialisation DOIT être rate-limitée à 5 requêtes / heure / IP.
- **FR-013**: L'e-mail de réinitialisation DOIT inclure le pseudo de l'utilisateur et préciser la durée de validité (30 minutes).
- **FR-019**: La case « Rester connecté » sur `/connexion` DOIT utiliser le mécanisme natif Symfony `remember_me` avec une durée de 30 jours. Si décochée, la session expire à la fermeture du navigateur.
- **FR-020**: En cas d'échec Google OAuth (erreur réseau, annulation, compte refusé), un message d'erreur DOIT s'afficher inline dans la zone d'erreur existante du formulaire, sans redirection ni rechargement complet.
- **FR-021**: Un utilisateur déjà authentifié accédant à `/connexion`, `/inscription`, `/mot-de-passe-oublie` ou `/reinitialiser-mot-de-passe` DOIT être redirigé immédiatement vers sa collection, sans affichage du formulaire.
- **FR-022**: Si un utilisateur tente de se connecter avec un e-mail non encore vérifié, la connexion DOIT être refusée et la zone d'erreur DOIT afficher un message « Vérifie ta boîte de réception » avec un lien permettant de renvoyer le courriel de confirmation.
- **FR-023**: Pendant la soumission d'un formulaire d'auth, le bouton de soumission DOIT être désactivé et afficher un spinner inline en remplacement du libellé ; toute tentative de double soumission DOIT être ignorée.
- **FR-024**: L'e-mail de confirmation d'inscription DOIT inclure le pseudo de l'utilisateur, un lien de confirmation à usage unique, et préciser la durée de validité du lien (identique au modèle de FR-013).
- **FR-025**: La fonctionnalité « Renvoyer le lien » sur les états « Vérifie ta boîte de réception » et « Lien envoyé » DOIT être rate-limitée à 5 requêtes / heure / IP (identique à FR-012).
- **FR-026**: Tous les formulaires d'auth DOIVENT être protégés par un token CSRF (mécanisme natif Symfony `csrf_token`). La soumission sans token valide DOIT être rejetée avec une erreur.
- **FR-027**: Si l'envoi d'un e-mail transactionnel échoue (confirmation d'inscription, réinitialisation de mot de passe, renvoi de lien), la zone d'erreur du formulaire DOIT afficher un message d'erreur explicite ; l'opération ne DOIT PAS basculer vers l'état de confirmation.
- **FR-028**: Si un utilisateur tente une inscription via Google OAuth avec un e-mail déjà associé à un compte e-mail/mot de passe existant, les comptes DOIVENT être automatiquement liés (même `User`) et l'utilisateur est redirigé vers sa collection.

### Key Entities

- **ResetPasswordToken**: token unique lié à un utilisateur, avec date d'expiration (30 min) et flag « utilisé ».
- **User.isEmailVerified** (nouveau champ) : booléen indiquant si l'adresse e-mail de l'utilisateur a été confirmée. Requis par FR-022. Faux par défaut à la création ; passé à vrai lors du clic sur le lien de confirmation.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-007**: Toutes les pages d'auth passent un audit axe-core v4+ sans violation WCAG 2.1 AA.

- **SC-001**: Un utilisateur peut compléter la connexion (e-mail + mot de passe) en moins de 30 secondes depuis l'arrivée sur `/connexion`.
- **SC-002**: Un utilisateur peut compléter l'inscription complète (formulaire → état confirmation) en moins de 2 minutes.
- **SC-003**: Un utilisateur peut réinitialiser son mot de passe (demande → e-mail → nouveau mdp → état succès) en moins de 5 minutes.
- **SC-004**: La jauge de robustesse répond à chaque frappe en moins de 100 ms perçus.
- **SC-005**: 100 % des transitions de vue (formulaire → état confirmation) s'effectuent sans rechargement complet de la page lorsque JavaScript est actif.
- **SC-006**: Les 4 pages d'auth sont visuellement conformes aux maquettes de design validées `design/pages/connexion.html`, `design/pages/inscription.html` et les deux pages mot-de-passe (vérifié par revue visuelle côte-à-côte).
- **SC-008**: La validation du pseudo (FR-018) rejette 100 % des valeurs hors format lors des tests de validation côté serveur et côté client.

## Clarifications

### Session 2026-05-30 (clarify-2)

- Q: Portée de l'invalidation lors de la réinitialisation du mot de passe — sessions uniquement ou aussi les tokens `remember_me` ? → A: Les deux invalidés (full logout everywhere) ; suppression des tokens `remember_me` en base en plus de l'invalidation de session.
- Q: Contenu du volet gauche (couverture) — identique sur les 4 pages ou spécifique par page ? → A: Spécifique par page, tel que défini dans les maquettes HTML de design (eyebrow, headline, bénéfices et stats distincts pour chaque page).
- Q: Inscription avec e-mail déjà enregistré mais non vérifié — message spécifique ou générique ? → A: Message générique « Ces informations sont déjà utilisées » sans révéler le statut de vérification ni l'existence du compte (révisé — voir session gap-resolution).

### Session 2026-05-30 (suite)

- Q: Comportement de la case « Rester connecté » sur `/connexion` → A: Cookie `remember_me` 30 jours, mécanisme natif Symfony ; sans coche, session expire à la fermeture du navigateur.
- Q: Comportement en cas d'échec Google OAuth sur `/connexion` ou `/inscription` → A: Message d'erreur inline dans la zone d'erreur du formulaire, pas de redirection ni rechargement.
- Q: Valeur numérique du rate-limiting sur `/mot-de-passe-oublie` → A: 5 requêtes / heure / IP.
- Q: Comportement si un utilisateur déjà connecté accède à `/connexion` ou `/inscription` → A: Redirection immédiate vers sa collection, formulaire non affiché.
- Q: Connexion possible si e-mail non encore vérifié ? → A: Bloquée ; message « Vérifie ta boîte de réception » affiché avec lien pour renvoyer le courriel.
- Q: Retour visuel pendant la soumission du formulaire → A: Bouton désactivé + spinner inline sur le bouton ; double soumission impossible.

### Session 2026-05-30

- Q: La page `/inscription` propose-t-elle aussi Google OAuth, ou uniquement e-mail + pseudo + mot de passe ? → A: Google OAuth disponible sur `/connexion` ET `/inscription` ; le flux OAuth sur inscription redirige vers la collection (pas d'état « Vérifie ta boîte de réception »).
- Q: Sur mobile, le volet gauche (couverture) du split-screen est-il masqué ou affiché réduit ? → A: Masqué en dessous de 920 px (corrigé depuis 768 px — voir session gap-resolution) ; formulaire occupe 100 % de la largeur.
- Q: Quand un nouveau lien de réinitialisation est demandé pour le même e-mail, les tokens précédents non utilisés sont-ils invalidés ? → A: Oui — tous les tokens actifs existants pour cet utilisateur sont invalidés avant création du nouveau.
- Q: Quel niveau d'accessibilité minimum est requis pour les pages d'auth ? → A: WCAG 2.1 niveau AA.
- Q: Format autorisé pour le pseudo à l'inscription (longueur, caractères) ? → A: 3–30 caractères, alphanumériques + tiret + underscore.

### Session 2026-05-30 (gap-resolution)

- Q: Portée du "full logout everywhere" (FR-008) — session courante ou tous les appareils ? → A: Tous les appareils — toutes les sessions actives et tous les tokens `remember_me` de l'utilisateur sont invalidés.
- Q: Breakpoint mobile du split-screen — 768 px ou valeur réelle du design ? → A: 920 px, conforme à `design/assets/auth.css` (`min-width: 920px`). FR-015 corrigé en conséquence.
- Q: Message d'erreur sur inscription avec e-mail/pseudo déjà utilisé — spécifique ou générique ? → A: Générique « Ces informations sont déjà utilisées », sans révéler l'existence du compte ni le statut de vérification. US2 scénario 6 mis à jour.
- Q: FR-011 (ne pas modifier l'existant) vs FR-022 (bloquer login non vérifié) — conflit ? → A: Résolu. FR-011 protège explicitement les composants existants listés. FR-022 est un ajout de comportement nouveau nécessitant un nouveau champ `User.isEmailVerified` ; ce n'est pas une modification de l'existant.
- Q: Google OAuth avec un e-mail déjà enregistré via mot de passe — comportement ? → A: Liaison automatique des comptes (même `User`), redirection vers la collection. Couvert par FR-028.
- Q: Comportement en cas d'échec d'envoi d'e-mail transactionnel ? → A: Message d'erreur affiché dans la zone d'erreur du formulaire ; pas de bascule vers l'état de confirmation. Couvert par FR-027.
- Q: Valeurs réelles des seuils brute-force login (FR-011) ? → A: 10 échecs consécutifs → blocage IP 15 minutes (900 s), implémenté dans `BruteForceProtectionService`.
- Q: Contenu de l'e-mail de confirmation d'inscription ? → A: Même modèle que l'e-mail de réinitialisation (FR-013) : pseudo, lien à usage unique, durée de validité. Couvert par FR-024.
- Q: Rate-limiting sur « Renvoyer le lien » ? → A: 5 req/heure/IP, identique à FR-012. Couvert par FR-025.

## Assumptions

- L'infrastructure d'envoi d'e-mails transactionnels est déjà configurée (Mailer Symfony).
- Le token de réinitialisation sera stocké en base de données (nouvelle entité `ResetPasswordToken`) ; aucun service tiers de gestion de tokens n'est nécessaire.
- La validation JavaScript (jauge, afficher/masquer) est progressive : les formulaires doivent fonctionner sans JS, avec validation serveur en fallback.
- La page de réinitialisation affiche l'adresse e-mail de l'utilisateur dans le sous-titre (extraite du token, pas d'un champ de saisie).
- L'invalidation de toutes les sessions actives ET des tokens `remember_me` se fait par : (1) régénération du sel de mot de passe (invalide les tokens de session Symfony) + (2) suppression explicite des entrées `RememberMeToken` en base liées à l'utilisateur.
- Le design CSS de la mise en page auth sera livré comme nouveau fichier d'asset intégré au pipeline de build existant.
- La fonctionnalité « Renvoyer le lien » sur les états de confirmation est rate-limitée de façon identique à la demande initiale (formalisé dans FR-025).
- L'entité `User` nécessite un nouveau champ `isEmailVerified` (booléen, défaut `false`) pour supporter FR-022. Ce champ est passé à `true` lors de la confirmation e-mail.
- En cas d'échec d'envoi d'e-mail, aucune entrée en base n'est créée (token ou confirmation) pour éviter des états incohérents.
- La page d'inscription actuelle redirige directement vers l'accueil après inscription ; ce comportement change : elle affichera désormais l'état de confirmation e-mail (changement de comportement UX attendu, pas de changement de logique métier).
- La variable `MAILER_DSN` est injectée en production via les variables de projet Platform.sh (console ou CLI `platform variable:create`) ; elle n'est pas déclarée dans `.platform.app.yaml` car elle contient des identifiants SMTP sensibles (non-managed service — constitution Principle II non concerné).
