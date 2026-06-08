# Feature Specification: Page "Nous Contacter" fonctionnelle

**Feature Branch**: `022-contact-page`

**Created**: 2026-06-08

**Status**: Draft

**Input**: User description: "Rendre la page de contact fonctionnelle et mettre à jour le maillage interne du site. Le design d'intégration (HTML/CSS) se trouve dans design/contact.html et doit être respecté à la lettre."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Visiteur non connecté soumet un formulaire de contact (Priority: P1)

Un visiteur non connecté accède à la page "Nous contacter", remplit le formulaire (en fournissant soit prénom+nom, soit un pseudonyme, plus son email, la raison et son message), puis soumet. Le formulaire valide les données et affiche un message de confirmation.

**Why this priority**: C'est le cas d'usage principal et le plus critique — tout utilisateur, qu'il soit connecté ou non, doit pouvoir contacter l'équipe. C'est le cœur de la fonctionnalité.

**Independent Test**: Accéder à `/contact` sans être connecté, remplir tous les champs obligatoires, soumettre → le message de succès s'affiche et les champs sont vidés.

**Acceptance Scenarios**:

1. **Given** un visiteur non connecté sur `/contact`, **When** il remplit "Prénom" + "Nom" + "Email" + "Raison" + "Détail" et clique "Envoyer", **Then** le formulaire est validé et un message de confirmation s'affiche ("Message envoyé !").
2. **Given** un visiteur non connecté, **When** il remplit uniquement "Pseudonyme" + "Email" + "Raison" + "Détail" (sans prénom ni nom), **Then** le formulaire est accepté et la confirmation s'affiche.
3. **Given** un visiteur non connecté, **When** il soumet le formulaire avec tous les champs vides, **Then** des erreurs visuelles s'affichent sur chaque champ obligatoire manquant.
4. **Given** un visiteur non connecté, **When** il saisit un email invalide (ex. "notanemail"), **Then** un message d'erreur s'affiche sur le champ Email.
5. **Given** un visiteur non connecté, **When** il ne fournit ni prénom/nom, ni pseudonyme, **Then** une erreur s'affiche indiquant qu'une identification est requise.

---

### User Story 2 - Utilisateur connecté bénéficie du pré-remplissage (Priority: P2)

Un utilisateur connecté accède à la page de contact et retrouve son pseudonyme et son email automatiquement pré-remplis dans les champs correspondants, ce qui lui évite de les ressaisir.

**Why this priority**: Améliore significativement l'expérience utilisateur pour les membres connectés, mais la page reste fonctionnelle sans cette feature.

**Independent Test**: Se connecter avec un compte, accéder à `/contact` → les champs "Pseudonyme" et "Email" sont pré-remplis avec les données du profil.

**Acceptance Scenarios**:

1. **Given** un utilisateur connecté avec un pseudonyme et un email dans son profil, **When** il accède à `/contact`, **Then** les champs "Pseudonyme" et "Email" sont automatiquement pré-remplis avec ses données de profil.
2. **Given** un utilisateur connecté, **When** il clique "Réinitialiser", **Then** les champs retrouvent les valeurs pré-remplies par défaut (pseudonyme et email du profil, pas des champs vides).
3. **Given** un utilisateur connecté, **When** il modifie l'email pré-rempli par un email invalide et soumet, **Then** une erreur de validation s'affiche sur le champ Email.

---

### User Story 3 - Mise à jour du maillage interne (Priority: P3)

En tant qu'utilisateur naviguant sur le site, je peux accéder à la page de contact depuis le footer et depuis la page des mentions légales, grâce à des liens correctement mis à jour.

**Why this priority**: Garantit que la page de contact est découvrable depuis l'ensemble du site, mais n'affecte pas la fonctionnalité du formulaire lui-même.

**Independent Test**: Sur n'importe quelle page du site, le footer affiche "Nous contacter" (et non "Devenir modérateur") avec un lien fonctionnel vers `/contact`. Sur la page mentions légales, les liens textuels "page de contact" (sections 2 et 5) pointent vers `/contact`.

**Acceptance Scenarios**:

1. **Given** n'importe quelle page du site affichant le footer, **When** l'utilisateur consulte la section "Communauté" du footer, **Then** le lien "Nous contacter" est présent et pointe vers la page de contact (plus de lien "Devenir modérateur" à cet emplacement).
2. **Given** la page Mentions Légales, **When** l'utilisateur clique sur "page de contact" dans la section 2, **Then** il est redirigé vers `/contact`.
3. **Given** la page Mentions Légales, **When** l'utilisateur clique sur "page de contact" dans la section 5, **Then** il est redirigé vers `/contact`.

---

### User Story 4 - Panneau latéral "Avant d'écrire" avec liens fonctionnels (Priority: P4)

En tant que visiteur sur la page de contact, je vois un panneau latéral avec des liens vers d'autres ressources du site (Suggérer un livre, Salle de modération, Conditions d'utilisation), afin d'éviter une demande inutile si ma question a déjà une réponse ailleurs.

**Why this priority**: Améliore la qualité du service en orientant les utilisateurs vers les bons canaux, mais c'est un élément secondaire de la page.

**Independent Test**: Accéder à `/contact` et vérifier que les trois liens du panneau "Avant d'écrire" sont cliquables et redirigent vers les bonnes destinations.

**Acceptance Scenarios**:

1. **Given** un visiteur sur `/contact`, **When** il clique sur "Suggérer un livre" dans le panneau latéral, **Then** il est redirigé vers le formulaire d'ajout de suggestion.
2. **Given** un visiteur sur `/contact`, **When** il clique sur "Salle de modération", **Then** il est redirigé vers le dashboard de modération.
3. **Given** un visiteur sur `/contact`, **When** il clique sur "Conditions d'utilisation", **Then** le lien ne navigue pas (comportement `href="#"` temporaire — la route `app_cgu` est hors scope de ce ticket).
4. **Given** un visiteur sur `/contact`, **When** il consulte le panneau latéral d'information, **Then** la mention "Réponse sous 2 à 3 jours ouvrés" est affichée de façon statique.

---

### Edge Cases

- Que se passe-t-il si l'utilisateur remplit à la fois "Prénom/Nom" ET "Pseudonyme" ? → Les deux sont acceptés ; la règle est "au moins l'un des deux blocs est complet".
- Que se passe-t-il si le pseudo ne contient que des espaces ? → Considéré comme vide ; le prénom+nom redevient obligatoire.
- Que se passe-t-il si l'utilisateur connecté n'a pas de pseudo défini dans son profil ? → Le champ Pseudonyme est laissé vide (le cas de pré-remplissage ne s'applique qu'aux données disponibles).
- Que se passe-t-il si le bouton "Réinitialiser" est actionné sans être connecté ? → Tous les champs sont vidés (aucune valeur par défaut à restaurer).
- Que se passe-t-il si la raison sélectionnée est "Choisissez une raison…" (valeur par défaut) et que l'utilisateur soumet ? → Une erreur de validation s'affiche sur le champ "Raison".

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Le système DOIT exposer une page publique "Nous contacter" accessible à l'URL `/contact`, sans authentification requise.
- **FR-002**: Le formulaire DOIT implémenter une validation conditionnelle d'identité : soit (Prénom ET Nom remplis), soit (Pseudonyme rempli) — au moins l'un des deux blocs est obligatoire.
- **FR-003**: Le champ "Pseudonyme" DOIT, lorsqu'il est rempli, rendre les champs "Prénom" et "Nom" facultatifs (les astérisques "requis" doivent disparaître visuellement selon le design).
- **FR-004**: Le champ "Email" DOIT être obligatoire et valider le format d'une adresse email valide.
- **FR-005**: Le champ "Quelle est la raison de votre demande ?" DOIT être un menu déroulant obligatoire avec l'option par défaut "Choisissez une raison…" (désactivée) et les options exactes suivantes :
  - J'ai une question sur le site
  - Je souhaite remonter un problème
  - Je souhaite signaler une erreur dans une fiche
  - Je souhaite suggérer un livre ou une œuvre
  - Je souhaite devenir modérateur
  - Je souhaite contester une décision de modération
  - Question sur mes données personnelles
  - Partenariat, presse ou association
  - Autre
- **FR-006**: Le champ "Détail de votre demande" DOIT être une zone de texte libre obligatoire. Longueurs maximales : prénom, nom, pseudonyme → 100 caractères ; email → 254 caractères (RFC 5321) ; message → 5 000 caractères.
- **FR-006b**: JavaScript est requis pour le fonctionnement du formulaire. Le template DOIT inclure un bloc `<noscript>` informant l'utilisateur que JavaScript est nécessaire.
- **FR-007**: En cas d'erreur de validation au moment de la soumission, le système DOIT afficher des messages d'erreur visuels sur les champs concernés, conformément au design de `design/contact.html`. Le contrôleur DOIT valider le token CSRF (`isCsrfTokenValid('contact', $token)`) et rejeter toute requête invalide avec HTTP 403 et `{success: false, message: "Requête invalide."}` en JSON. Le contrôleur DOIT également re-valider toutes les règles métier côté serveur (identité, email, raison, message) et retourner HTTP 422 avec `{success: false, errors: [...]}` en JSON si invalide (protection contre les requêtes directes sans JS).
- **FR-007b**: Le template Twig DOIT inclure un champ caché `<input type="hidden" name="_token" value="{{ csrf_token('contact') }}">` dans le formulaire.
- **FR-008**: En cas de soumission réussie, le système DOIT envoyer un email via Symfony Mailer depuis `CONTACT_EMAIL_FROM` vers `CONTACT_EMAIL_TO` (toutes deux configurées dans `.env`), avec le sujet `[Contact] {raison} — {pseudo si renseigné, sinon prénom nom}`, retourner HTTP 200 avec `{success: true}`, afficher un message de confirmation (zone `.form-success`) et vider les champs du formulaire. Le formulaire DOIT soumettre les données via fetch (POST JSON) vers le contrôleur Symfony ; le bouton "Envoyer" DOIT être désactivé (`disabled`) pendant la requête afin d'empêcher les doubles soumissions.
- **FR-009**: Le bouton "Réinitialiser" DOIT vider tous les champs saisis ; si l'utilisateur est connecté, les valeurs pré-remplies (pseudo et email) DOIVENT être restaurées.
- **FR-010**: Si l'utilisateur est connecté, les champs "Pseudonyme" et "Email" DOIVENT être automatiquement pré-remplis avec les données de son profil lors du chargement de la page.
- **FR-011**: Le footer du site DOIT remplacer le lien "Devenir modérateur" par "Nous contacter" pointant vers `/contact`.
- **FR-012**: La page "Mentions Légales" DOIT mettre à jour les liens textuels "page de contact" des sections 2 et 5 pour pointer vers `/contact` (et non plus vers une ancre interne `#contact`).
- **FR-013**: Le panneau latéral DOIT afficher des liens vers : la page Suggestions (`suggestions_index` → `/suggestions`), le dashboard de modération (`moderation_dashboard` → `/moderation`), et "Conditions d'utilisation" (lien `#` temporaire, route CGU hors scope).
- **FR-014**: Le panneau latéral DOIT afficher de manière statique la mention "Réponse sous 2 à 3 jours ouvrés".
- **FR-015**: Le rendu visuel de la page DOIT respecter le design défini dans `design/contact.html` à l'échelle des composants (layout, espacements, couleurs, typographie) en mode clair et en mode sombre ; des différences mineures de rendu inter-navigateurs sont acceptables.
- **FR-016**: En cas d'échec d'envoi Symfony Mailer, le contrôleur DOIT retourner HTTP 500 avec `{success: false, message: "Une erreur est survenue, veuillez réessayer."}` sans exposer les détails techniques. (structure HTML, classes CSS, comportements JS déjà codés dans ce fichier).

### Key Entities *(include if feature involves data)*

- **ContactMessage** : représente un message envoyé via le formulaire. Attributs : identité (prénom, nom ou pseudonyme), email, raison, message. Aucune persistance en base n'est spécifiée — le traitement est géré côté serveur (envoi d'email ou log).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: La page `/contact` est accessible sans authentification et retourne HTTP 200 pour toute requête GET.
- **SC-002**: Un utilisateur connecté retrouve son pseudonyme et son email pré-remplis dès l'ouverture de la page, sans aucune action supplémentaire.
- **SC-003**: 100 % des erreurs de validation (champ manquant, email invalide, identité absente) sont signalées visuellement à l'utilisateur avant tout envoi.
- **SC-004**: La confirmation de soumission est immédiatement visible après un envoi réussi (pas de rechargement de page requis).
- **SC-005**: Les liens "Nous contacter" dans le footer et "page de contact" dans les mentions légales redirigent correctement vers la page de contact sur l'ensemble du site.
- **SC-006**: Le rendu de la page de contact correspond au fichier `design/contact.html` au niveau composant (layout, couleurs, typographie, espacements) en mode clair et en mode sombre.

## Clarifications

### Session 2026-06-08

- Q: Le formulaire doit-il réellement POSTer vers un contrôleur Symfony, ou rester purement JS-only ? → A: POST réel — le formulaire envoie les données à un endpoint Symfony qui les traite côté serveur.
- Q: Que doit faire le contrôleur avec les données reçues ? → A: Symfony Mailer — envoi d'un email réel à une adresse configurée via `.env`.
- Q: Faut-il ajouter une protection CSRF au formulaire POST ? → A: Oui — hidden input `_token` via `csrf_token('contact')`, validé côté contrôleur avec `isCsrfTokenValid()`.
- Q: Le lien "Conditions d'utilisation" du panneau latéral doit pointer vers quoi (route `app_cgu` inexistante) ? → A: Lien `#` temporaire — à implémenter dans un ticket CGU dédié.
- Q: Le contrôleur doit-il re-valider les données côté serveur ? → A: Oui — validation complète côté serveur (identité, email, raison, message) ; retourne 422 + JSON `{success: false}` si invalide.
- Q: Quelle adresse email expéditeur (`From:`) pour Symfony Mailer ? → A: Variable `.env` `CONTACT_EMAIL_FROM` (symétrique à `CONTACT_EMAIL_TO`).
- Q: Quel format pour le sujet (`Subject:`) de l'email de contact ? → A: `[Contact] {raison} — {prénom nom / pseudo}`.
- Q: En cas d'échec Symfony Mailer, que retourne le contrôleur ? → A: HTTP 500 + `{success: false, message: "Une erreur est survenue, veuillez réessayer."}`.
- Q: Le menu déroulant "Raison" doit-il inclure "Je souhaite signaler une erreur dans une fiche" et "Je souhaite suggérer un livre ou une œuvre" (présents dans le design mais absents de FR-005 initial) ? → A: Oui — ces deux options sont intentionnelles ; FR-005 a été mis à jour.
- Q: Quel code HTTP retourner en cas de token CSRF invalide ? → A: HTTP 403 avec `{success: false, message: "Requête invalide."}` en JSON.
- Q: Quel code HTTP retourner pour une soumission valide ? → A: HTTP 200 avec `{success: true}`.
- Q: Si le visiteur renseigne à la fois prénom+nom ET pseudonyme, quel identifiant figure dans le sujet de l'email ? → A: Le pseudonyme (prioritaire sur prénom+nom).
- Q: "Visuellement identique" dans FR-015/SC-006 signifie-t-il une correspondance pixel-perfect ? → A: Non — correspondance au niveau composant (layout, espacements, couleurs, typographie) ; différences mineures de rendu inter-navigateurs acceptables.

## Assumptions

- La page de contact ne persiste pas les messages en base de données pour ce ticket. Le contrôleur envoie un email réel via Symfony Mailer depuis l'adresse `CONTACT_EMAIL_FROM` vers `CONTACT_EMAIL_TO`, toutes deux configurées dans `.env`. En cas d'échec d'envoi (SMTP indisponible, erreur réseau), le contrôleur retourne HTTP 500 + `{success: false, message: "Une erreur est survenue, veuillez réessayer."}` sans exposer les détails techniques.
- L'utilisateur "connecté" est identifié via le système d'authentification Symfony existant (`app.user` dans Twig, `$this->getUser()` dans le contrôleur).
- Routes du panneau latéral : `suggestions_index` (`/suggestions`) et `moderation_dashboard` (`/moderation`) sont confirmées et utilisées. Le lien "Conditions d'utilisation" utilise un lien `#` temporaire (route `app_cgu` hors scope, à créer dans un ticket dédié).
- La route Symfony de la page de contact est nommée `app_contact` (convention du projet : `app_{nom}`, cf. `app_mentions_legales`).
- JavaScript est une dépendance requise. Le formulaire soumet via `fetch()` ; sans JS le formulaire ne fonctionnerait pas (noscript notice prévue dans le template).
- La validation des entrées côté serveur (sanitisation XSS, injection de headers email) est sous la responsabilité de l'implémenteur — Twig échappe automatiquement les sorties, et Symfony Mailer (symfony/mime) sanitise les valeurs d'en-tête. Pas d'exigence explicite dans les FR.
- Le rate limiting et la journalisation (logging) des soumissions sont hors scope pour ce ticket et seront traités dans un ticket dédié.
- Le fichier `design/contact.html` contient déjà le JavaScript de validation côté client complet — ce code DOIT être repris tel quel dans le template Twig (ou dans un fichier JS séparé).
- Le bouton "Réinitialiser" dans `design/contact.html` est de type `reset` (comportement natif HTML) ; côté Twig, la restauration des valeurs pré-remplies pour les utilisateurs connectés est gérée via JavaScript (comme défini dans le fichier de design).
- Cette page n'a pas d'exigences de performance particulières au-delà des standards habituels du site.
- Le design mobile (responsive) est déjà défini dans `design/contact.html` et DOIT être respecté.
