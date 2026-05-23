# Feature Specification: Inscription et Authentification (Classique + Google OAuth2)

**Feature Branch**: `002-user-auth-oauth2`

**Created**: 2026-05-23

**Status**: Draft

## Clarifications

### Session 2026-05-23

- Q: Que se passe-t-il si un visiteur tente de s'inscrire classiquement avec un email déjà associé à un compte Google (sans mot de passe) ? → A: Le système autorise l'inscription classique et ajoute silencieusement le mot de passe au compte Google existant (fusion transparente).
- Q: Quel est le mécanisme de protection contre la force brute (après le seuil de SC-005) ? → A: Blocage temporaire de 15 minutes basé sur l'IP après 10 tentatives échouées consécutives.
- Q: Que se passe-t-il si Google retourne un email non vérifié (`email_verified: false`) ? → A: Rejeter le flux OAuth2, afficher un message d'erreur, rediriger vers la connexion classique.
- Q: Quelle est la durée de la session persistante "Se souvenir de moi" ? → A: 30 jours.
- Q: Que se passe-t-il si Google est indisponible lors du flux OAuth2 ? → A: Message d'erreur explicite affiché, redirection vers la connexion classique (pas d'erreur 500).
- Q: Quelle stratégie pour résoudre un conflit de pseudo lors d'un flux Google (pseudo déjà pris) ? → A: Ajouter un suffixe numérique incrémental (ex. jean_paul_2, jean_paul_3…).
- Q: Conformité RGPD — case de consentement explicite requise à l'inscription ? → A: Oui, case à cocher obligatoire (inscription classique et Google) — le formulaire ne peut pas être soumis sans consentement explicite.
- Q: Portée de la déconnexion — session courante ou toutes les sessions actives ? → A: Session courante uniquement.
- Q: Quels champs de l'Identité Google sont persistés en base ? → A: `google_id` + `email` + nom d'affichage + URL avatar.
- Q: Quels événements d'authentification doivent être journalisés ? → A: Tous les événements clés : connexion réussie, connexion échouée, déconnexion, création de compte, flux OAuth2 (début/fin/erreur).
- Q: Le rate limiting (FR-008) s'applique-t-il aussi à la page d'inscription ? → A: Oui, avec un seuil distinct : 5 tentatives d'inscription par IP par heure.
- Q: Quelle est la page d'atterrissage par défaut après connexion si aucune URL précédente n'est capturée ? → A: Page d'accueil (`/`).
- Q: Comment le consentement RGPD est-il capturé dans le flux Google OAuth2 (pas de formulaire traditionnel) ? → A: Page de consentement intermédiaire après retour de Google, avant création du compte — données Google temporairement stockées en session.

### Session 2026-05-23 (clarify pass 2)

- Q: Le champ pseudo est-il soumis à une contrainte UNIQUE en base de données ? → A: Oui, UNIQUE globalement — l'inscription classique vérifie aussi la disponibilité du pseudo avant création du compte.
- Q: Quel message est affiché à un utilisateur dont l'IP est bloquée par le rate limiting (connexion ou inscription) ? → A: Message avec temps restant : "Trop de tentatives. Réessayez dans X minutes." (X = minutes restantes avant déblocage).
- Q: Quel type de clé primaire pour l'entité Utilisateur ? → A: UUID v4.

### Session 2026-05-23 (checklist gap resolution)

- Q: Quel algorithme de hachage pour les mots de passe ? → A: bcrypt avec un facteur de coût minimum de 13.
- Q: Durée de la session standard (sans "Se souvenir de moi") ? → A: Expire à la fermeture du navigateur (session cookie, pas de TTL fixe).
- Q: La connexion automatique après inscription utilise-t-elle une session persistante ? → A: Non — session standard. L'utilisateur doit cocher "Se souvenir de moi" pour obtenir la persistance 30 jours.
- Q: La déconnexion supprime-t-elle explicitement le cookie "Se souvenir de moi" ? → A: Oui — cookie supprimé du navigateur lors de la déconnexion.
- Q: Le paramètre state OAuth2 est-il requis pour la protection CSRF du callback ? → A: Oui — inclus dans la protection CSRF (FR-009).
- Q: Durée du timeout pour les appels au service Google ? → A: 10 secondes.
- Q: Comportement si l'utilisateur n'accorde pas les scopes email et/ou profile Google ? → A: Rejet total — traité comme une annulation, redirection vers la connexion classique.
- Q: Mécanisme de redirection post-connexion vers l'URL précédente ? → A: Variable de session Symfony Security (`_security.target_path`).
- Q: Périmètre des routes protégées ? → A: Toutes les routes sauf liste publique explicite : `/`, `/connexion`, `/inscription`, `/auth/google`, `/auth/google/callback`, `/auth/google/consent`, `/politique-de-confidentialite`.
- Q: Le consentement RGPD doit-il être enregistré en base ou dans les logs ? → A: Non — la création de compte journalisée (FR-020) suffit.
- Q: La page `/politique-de-confidentialite` est-elle dans le scope ? → A: Hors scope — son existence est supposée.
- Q: La fusion de compte (FR-015) nécessite-t-elle un nouveau consentement RGPD ? → A: Non — le consentement initial du flux Google reste valide.
- Q: Format du pseudo ? → A: Alphanumérique + underscore, 3 à 30 caractères (`^[a-zA-Z0-9_]{3,30}$`).
- Q: Validation de l'adresse email ? → A: Validation HTML5 standard (équivalent `type=email`).
- Q: Le minuteur de blocage brute force est-il prolongé par des tentatives pendant le blocage ? → A: Non — durée fixe de 15 minutes, non prolongée.
- Q: Comportement si le champ `email_verified` est absent de la réponse Google ? → A: Traité comme `false` — flux interrompu.
- Q: Comportement si l'utilisateur ferme la page de consentement RGPD Google sans confirmer ni annuler ? → A: Traité comme un refus — aucun compte créé.
- Q: Emplacement du bouton de déconnexion ? → A: Menu déroulant du profil utilisateur dans la barre de navigation.
- Q: Les erreurs de formulaire sont-elles toutes affichées en même temps ? → A: Oui — toutes les erreurs de validation sont affichées simultanément.
- Q: Lors de la création de compte via Google, l'email ET le pseudo peuvent-ils simultanément créer un conflit ? Dans quel ordre sont-ils traités ? → A: L'email est vérifié en premier — si l'email existe déjà, FR-011 s'applique (connexion au compte existant) et aucun nouveau compte n'est créé, donc la question du pseudo ne se pose pas. Si l'email est inconnu, un nouveau compte est créé avec résolution du pseudo via FR-018.
- Q: Les flux OAuth2 Google simultanés depuis le même navigateur (plusieurs onglets) sont-ils gérés ? → A: Chaque flux utilise son propre paramètre `state` — le premier flux à se terminer crée ou connecte le compte ; les flux suivants aboutissent sur FR-011 (compte existant) ou sur une erreur de state invalide. Aucune exigence métier supplémentaire n'est requise.

### Session 2026-05-23 (plan readiness gaps)

- Q: Quelle est la règle de complexité du mot de passe ? → A: Minimum 8 caractères, aucune contrainte de type de caractère.
- Q: Le compteur de force brute compte-t-il les échecs consécutifs ou le total ? → A: Échecs consécutifs — le compteur est remis à zéro à la première connexion réussie.
- Q: Le déblocage de l'IP après 15 minutes est-il automatique ou manuel ? → A: Automatique — l'IP se débloque passivement à l'expiration des 15 minutes, sans intervention.
- Q: Le cookie "Se souvenir de moi" a-t-il une expiration fixe ou glissante ? → A: Expiration fixe — 30 jours à partir de la date de création du cookie, sans prolongation à chaque activité.
- Q: Le compteur de rate limiting sur l'inscription (5/heure/IP) utilise-t-il une fenêtre glissante ou fixe ? → A: Fenêtre glissante — 5 tentatives maximum dans toute fenêtre de 60 minutes consécutives.
- Q: Quels scopes Google OAuth2 sont requis ? → A: `openid`, `email`, `profile` (identité + email + nom d'affichage + avatar).
- Q: Comment les credentials Google OAuth2 sont-ils gérés ? → A: Variables d'environnement par environnement (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`).
- Q: Quels sont les attributs de sécurité requis pour le cookie de session ? → A: `Secure`, `HttpOnly`, `SameSite=Lax` obligatoires.
- Q: Quel contenu affiche la page de consentement RGPD intermédiaire du flux Google ? → A: Case à cocher de consentement obligatoire + lien vers la politique de confidentialité + deux boutons (Confirmer / Annuler). Les données Google ne sont pas affichées à l'utilisateur.
- Q: À quel moment les données Google temporaires en session sont-elles effacées ? → A: À la fin de la session navigateur (fermeture du navigateur ou expiration de session), quelle que soit l'issue du flux de consentement.
- Q: Y a-t-il une limite au nombre de tentatives pour trouver un pseudo disponible via suffixe incrémental ? → A: Non — le système incrémente sans limite jusqu'à trouver un pseudo disponible.
- Q: Lors de la fusion de compte (FR-015), que devient le profil Google existant ? → A: Le profil Google existant (`display_name`, `avatar_url`, `google_id`) est conservé intact — seul le mot de passe est ajouté au compte.
- Q: Quelle est la destination des journaux d'authentification (FR-020) ? → A: Fichier de log applicatif standard (ex. Monolog) — pas de table dédiée en base de données.
- Q: Les critères SC-001/SC-002 ("moins de 2 minutes", "moins de 30 secondes") sont-ils mesurés comment ? → A: Temps perçu par l'utilisateur (wall clock), mesuré du chargement de la page jusqu'à la redirection post-connexion, en conditions normales sans contrainte de charge.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Inscription classique (Priority: P1)

Un visiteur non connecté souhaite créer un compte sur "La collection des aventuriers" en fournissant un pseudo, une adresse email et un mot de passe.

**Why this priority**: Fondation du système — sans inscription, aucun autre flux ne peut fonctionner. Délivre immédiatement la valeur d'appartenance à la collection.

**Independent Test**: Tester en créant un compte avec des données valides depuis la page d'inscription, puis vérifier que l'utilisateur est redirigé et peut se connecter avec ces identifiants.

**Acceptance Scenarios**:

1. **Given** un visiteur sur la page d'inscription, **When** il saisit un pseudo unique, un email valide et un mot de passe conforme (confirmation incluse), **Then** un compte est créé, l'utilisateur est connecté automatiquement et redirigé vers la page d'accueil.
2. **Given** un visiteur sur la page d'inscription, **When** il saisit un email déjà utilisé, **Then** un message d'erreur explicite lui indique que cet email est déjà associé à un compte.
3. **Given** un visiteur sur la page d'inscription, **When** le mot de passe et sa confirmation ne correspondent pas, **Then** un message d'erreur clair est affiché sans que le compte soit créé.
4. **Given** un visiteur sur la page d'inscription, **When** il soumet le formulaire sans remplir tous les champs obligatoires, **Then** les champs manquants sont mis en évidence avec un message d'erreur spécifique.
5. **Given** un visiteur sur la page d'inscription, **When** il soumet le formulaire sans cocher la case de consentement RGPD, **Then** la soumission est bloquée et un message d'erreur indique que le consentement est requis.
6. **Given** un visiteur sur la page d'inscription, **When** il saisit un pseudo déjà utilisé par un autre compte, **Then** un message d'erreur explicite lui indique que ce pseudo n'est pas disponible.

---

### User Story 2 - Connexion classique (Priority: P2)

Un utilisateur inscrit souhaite accéder à son compte via son email et son mot de passe.

**Why this priority**: Flux de retour principal — les utilisateurs existants doivent pouvoir retrouver leur collection à chaque visite.

**Independent Test**: Tester en se connectant avec un compte préalablement créé, avec l'option "Se souvenir de moi" active et inactive.

**Acceptance Scenarios**:

1. **Given** un utilisateur avec un compte existant sur la page de connexion, **When** il saisit son email et mot de passe corrects, **Then** il est authentifié et redirigé vers la page demandée avant la redirection (ou vers `/` par défaut si aucune URL précédente n'est capturée).
2. **Given** un utilisateur sur la page de connexion, **When** il coche "Se souvenir de moi" et se connecte, **Then** sa session est maintenue lors de sa prochaine visite sans avoir à se reconnecter.
3. **Given** un utilisateur sur la page de connexion, **When** il saisit des identifiants incorrects, **Then** un message d'erreur générique est affiché (sans préciser si c'est l'email ou le mot de passe qui est faux).
4. **Given** un attaquant tentant de forcer un compte via le formulaire de connexion, **When** il dépasse un seuil de tentatives échouées répétées, **Then** de nouvelles tentatives sont temporairement bloquées depuis cette source.

---

### User Story 3 - Connexion / Inscription via Google (Priority: P3)

Un visiteur souhaite créer un compte ou se connecter en utilisant son compte Google, sans avoir à définir de mot de passe.

**Why this priority**: Réduit la friction à l'inscription et améliore le taux de conversion. Secondaire car l'authentification classique doit d'abord fonctionner.

**Independent Test**: Tester le flux complet Google depuis la page de connexion pour un nouvel email (création de compte) et pour un email déjà existant (connexion au compte existant).

**Acceptance Scenarios**:

1. **Given** un visiteur sur la page de connexion ou d'inscription, **When** il clique sur "Se connecter avec Google" et autorise l'accès, **Then** si son email Google n'est pas connu, un compte est automatiquement créé et il est connecté.
2. **Given** un visiteur sur la page de connexion ou d'inscription, **When** il clique sur "Se connecter avec Google" et autorise l'accès, **Then** si son email Google correspond à un compte existant, il est connecté à ce compte existant.
3. **Given** un utilisateur lançant le flux Google, **When** il annule l'autorisation sur la page Google, **Then** il est redirigé vers la page de connexion avec un message informatif (sans erreur bloquante).
4. **Given** un nouvel utilisateur revenant de Google (email inconnu), **When** le système affiche la page de consentement RGPD intermédiaire, **Then** si l'utilisateur refuse le consentement, aucun compte n'est créé et il est redirigé vers la page de connexion.

---

### User Story 4 - Déconnexion (Priority: P1)

Un utilisateur connecté souhaite mettre fin à sa session de manière sécurisée.

**Why this priority**: Indispensable pour la sécurité des comptes, notamment sur les appareils partagés.

**Independent Test**: Se connecter, puis cliquer sur le bouton de déconnexion et vérifier que l'accès aux pages protégées est refusé.

**Acceptance Scenarios**:

1. **Given** un utilisateur connecté, **When** il clique sur "Se déconnecter", **Then** sa session est détruite et il est redirigé vers la page de connexion ou d'accueil publique.
2. **Given** un utilisateur déconnecté, **When** il tente d'accéder à une page nécessitant une authentification, **Then** il est redirigé vers la page de connexion.

---

### Edge Cases

- Si un visiteur s'inscrit classiquement avec l'email d'un compte Google existant, le mot de passe est ajouté au compte existant (pas de doublon). L'utilisateur est ensuite connecté normalement. *(résolu)*
- Si Google retourne `email_verified: false`, le flux OAuth2 est interrompu — message d'erreur affiché, redirection vers la connexion classique. *(résolu)*
- Si Google est indisponible, le système affiche un message d'erreur explicite et redirige vers la connexion classique (aucune erreur 500). *(résolu)*
- Si un utilisateur Google fournit un pseudo déjà utilisé dans la base, le système ajoute un suffixe numérique incrémental (ex. `jean_paul_2`, `jean_paul_3`…). *(résolu)*

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Le système DOIT permettre à tout visiteur de créer un compte en fournissant un pseudo, une adresse email et un mot de passe (avec confirmation). Le mot de passe doit comporter au minimum 8 caractères — aucune contrainte de type de caractère n'est imposée. Le pseudo DOIT respecter le format `^[a-zA-Z0-9_]{3,30}$` (3 à 30 caractères, lettres, chiffres et underscores uniquement). Le pseudo DOIT être unique en base de données (contrainte UNIQUE) — un pseudo déjà utilisé produit le message : *« Ce pseudo n'est pas disponible. »* L'adresse email est validée selon le standard HTML5 (équivalent `type=email`) — un email déjà utilisé produit le message : *« Cette adresse email est déjà associée à un compte. »* Le formulaire d’inscription DOIT contenir exactement les champs suivants : pseudo, adresse email, mot de passe, confirmation du mot de passe, et case à cocher de consentement RGPD — aucun autre champ obligatoire n’est requis.
- **FR-002**: Le système DOIT garantir l'unicité de l'adresse email parmi tous les comptes (qu'ils soient créés classiquement ou via Google).
- **FR-003**: Le système DOIT hacher les mots de passe avec l'algorithme **bcrypt** (facteur de coût minimum : **13**) avant tout stockage — les mots de passe en clair ne doivent jamais être enregistrés.
- **FR-004**: Tout nouvel utilisateur inscrit DOIT recevoir automatiquement le rôle de membre standard (accès utilisateur de base).
- **FR-005**: Le système DOIT permettre à un utilisateur inscrit de se connecter via son email et son mot de passe.
- **FR-006**: Le formulaire de connexion DOIT proposer une option "Se souvenir de moi" maintenant la session pendant 30 jours via un cookie persistant. Ce cookie a une expiration fixe (30 jours à partir de sa création, sans prolongation à chaque activité) et DOIT être émis avec les attributs `Secure`, `HttpOnly` et `SameSite=Lax`. Lors de la déconnexion, ce cookie persistant est supprimé — voir FR-013.
- **FR-007**: Le système DOIT afficher le message *« Identifiant ou mot de passe incorrect. »* (sans divulguer quelle donnée est incorrecte) en cas d'échec de connexion.
- **FR-008**: Le système DOIT bloquer temporairement (15 minutes) toute nouvelle tentative de connexion depuis une IP ayant atteint 10 échecs **consécutifs** (le compteur est remis à zéro à chaque connexion réussie). Les compteurs de tentatives DOIVENT persister entre les requêtes (stockage côté serveur, non volatile entre les appels HTTP). Le déblocage est automatique et passif à l'expiration des 15 minutes — la durée de blocage n'est pas prolongée par de nouvelles tentatives effectuées pendant la période de blocage. Lors d'une tentative bloquée, le système DOIT afficher : *"Trop de tentatives. Réessayez dans X minutes."* où X est le nombre de minutes restantes avant déblocage.
- **FR-009**: Le système DOIT protéger contre les attaques CSRF. Cette protection couvre : tous les formulaires d'inscription et de connexion, l'action de déconnexion (requête POST), la page de consentement RGPD intermédiaire (flux Google), et le paramètre `state` OAuth2 (validation obligatoire sur le callback `/auth/google/callback` pour prévenir le CSRF sur le flux OAuth2).
- **FR-010**: Le système DOIT proposer un bouton "Se connecter avec Google" sur les pages de connexion et d'inscription, visuellement intégré au Design System. Le flux OAuth2 utilise les scopes `openid`, `email` et `profile`. Les credentials Google (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`) sont stockés en variables d'environnement, distincts par environnement. La route de callback OAuth2 est `/auth/google/callback`. Le délai d'attente maximal pour toute réponse du service Google est **10 secondes** — au-delà, le comportement défini en FR-017 est déclenché. Lorsque l'utilisateur clique sur « Se connecter avec Google », le bouton DOIT afficher un état de chargement (indicateur visuel) pendant la redirection vers Google.
- **FR-011**: Lors d'un flux Google OAuth2, si l'email retourné par Google correspond à un compte existant, le système DOIT connecter l'utilisateur à ce compte sans créer de doublon.
- **FR-012**: Lors d'un flux Google OAuth2, si l'email retourné par Google n'existe pas encore, le système DOIT créer automatiquement un nouveau compte avec les informations publiques disponibles (email, pseudo/nom).
- **FR-016**: Le système DOIT vérifier le champ `email_verified` retourné par Google — si `false` ou si le champ est absent, le flux OAuth2 est interrompu avec le message *« Adresse Google non vérifiée. Utilisez la connexion classique. »* et une redirection vers la page de connexion classique.
- **FR-017**: En cas d'indisponibilité du service Google (timeout, erreur réseau) durant le flux OAuth2, le système DOIT afficher le message *« Le service Google est indisponible. Utilisez la connexion classique. »* et rediriger vers la page de connexion classique sans générer d'erreur 500. Le timeout de 10 secondes défini en FR-010 s'applique.
- **FR-013**: Le système DOIT permettre à tout utilisateur connecté de se déconnecter, détruisant immédiatement la session courante uniquement (les sessions actives sur d'autres appareils ne sont pas affectées). Si un cookie persistant « Se souvenir de moi » est présent, il DOIT être explicitement supprimé du navigateur lors de la déconnexion. Le bouton/lien de déconnexion DOIT être accessible depuis le menu déroulant du profil utilisateur dans la barre de navigation, visible sur toutes les pages authentifiées.
- **FR-014**: Les comptes créés via Google DOIVENT pouvoir exister sans mot de passe défini (le champ mot de passe est facultatif pour ce type de compte).
- **FR-015**: Si un visiteur s'inscrit classiquement avec un email déjà associé à un compte Google (sans mot de passe), le système DOIT ajouter le mot de passe fourni au compte existant sans créer de doublon, puis connecter l'utilisateur. Le profil Google existant (`display_name`, `avatar_url`, `google_id`) est conservé intact. Aucun nouveau consentement RGPD n'est requis pour cette opération. La liaison Google OAuth2 reste active — l'utilisateur peut continuer à se connecter via Google après la fusion.
- **FR-018**: Lors de la création automatique d'un compte via Google, si le pseudo proposé par Google est déjà utilisé dans la base, le système DOIT appliquer un suffixe numérique incrémental (`jean_paul_2`, `jean_paul_3`…) sans limite jusqu'à trouver un pseudo disponible. Si Google ne fournit pas de nom d'affichage (champ absent ou vide), le système utilise la partie locale de l'adresse email (partie avant le `@`) comme pseudo de base, soumis aux mêmes règles de format et d'unicité.
- **FR-019**: Tout nouveau compte DOIT faire l'objet d'un consentement RGPD explicite obligatoire avant sa création. Pour l'inscription classique : case à cocher sur le formulaire. Pour le flux Google : page de consentement intermédiaire affichée après le retour de Google et avant la création du compte — les données Google sont temporairement stockées en session. Cette page DOIT contenir : une case à cocher de consentement obligatoire, un lien vers la politique de confidentialité, et deux boutons (Confirmer / Annuler). Les données Google ne sont pas affichées à l'utilisateur. Les données temporaires en session sont effacées à la fin de la session navigateur (fermeture ou expiration), quelle que soit l'issue. Si l'utilisateur ferme la page de consentement ou navigue en arrière sans confirmer ni annuler, le comportement est identique à un refus — aucun compte n'est créé. La route `/politique-de-confidentialite` est supposée exister — sa création est hors scope de cette spécification.
- **FR-020**: Le système DOIT journaliser tous les événements d'authentification clés : connexion réussie, connexion échouée, déconnexion, création de compte, et chaque étape du flux OAuth2 (initiation, retour Google, succès, erreur). Les journaux sont écrits dans le fichier de log applicatif standard (ex. Monolog) — aucune table dédiée en base de données n'est requise. La durée de conservation des journaux est gérée au niveau de l’infrastructure (configuration de déploiement) — aucune limite de rétention applicative n’est définie pour la v1.
- **FR-021**: Le système DOIT limiter les tentatives d'inscription depuis une même IP (comptage par IP uniquement, indépendant de l'adresse email soumise) à 5 par heure, via une **fenêtre glissante** de 60 minutes — au-delà, les nouvelles tentatives sont temporairement bloquées. Les compteurs de la fenêtre glissante DOIVENT persister entre les requêtes (stockage côté serveur). Lors d'une tentative bloquée, le système DOIT afficher : *"Trop de tentatives. Réessayez dans X minutes."* où X est le nombre de minutes restantes avant que la fenêtre glissante libère une nouvelle tentative.

- **FR-022**: La session standard (sans « Se souvenir de moi ») DOIT expirer à la fermeture du navigateur (session cookie, sans TTL fixe). La connexion automatique après inscription classique crée une session standard — aucune session persistante n'est accordée sans activation explicite de « Se souvenir de moi ».
- **FR-023**: Toutes les erreurs de validation de formulaire DOIVENT être affichées simultanément après soumission — l'utilisateur voit l'ensemble des erreurs en une seule tentative, sans validation champ par champ.
- **FR-024**: Toutes les routes de l'application requièrent une authentification, à l'exception de la liste publique suivante : `/` (page d'accueil), `/connexion`, `/inscription`, `/auth/google` (initiation OAuth2), `/auth/google/callback`, `/auth/google/consent` (page de consentement RGPD intermédiaire), `/politique-de-confidentialite`. Tout accès non authentifié à une route protégée entraîne une redirection vers `/connexion`.
- **FR-025**: Après connexion réussie, l'utilisateur DOIT être redirigé vers l'URL cible stockée en session par le composant Symfony Security (`_security.target_path`) — ou vers `/` par défaut si aucune cible n'est mémorisée.
- **FR-026**: Le système DOIT rejeter le flux OAuth2 Google si les scopes `email` ou `profile` ne sont pas accordés par l'utilisateur. Ce cas est traité comme une annulation et affiche le message : *« Connexion Google annulée. »* avec redirection vers la page de connexion classique.
- **FR-027**: Lors d'un refus du consentement RGPD dans le flux Google (bouton Annuler, fermeture de page ou navigation arrière), le message *« Vous devez accepter les conditions pour créer un compte. »* DOIT être affiché et l'utilisateur redirigé vers la page de connexion.

### Key Entities

- **Utilisateur** : Représente un membre de la collection. Clé primaire : UUID v4. Possède une identité unique (email) et un pseudo unique en base (contrainte UNIQUE), un mot de passe optionnel (haché), un ou plusieurs rôles, et peut être lié à une identité Google externe.
- **Session** : Période d'authentification active pour un utilisateur. Peut être persistante (Se souvenir de moi) ou expirer à la fermeture du navigateur.
- **Identité Google** : Référence externe liant un compte utilisateur à un compte Google. Persist : `google_id` (identifiant unique Google), `email`, `display_name`, `avatar_url`. Permet la connexion sans mot de passe.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un visiteur peut créer un compte en moins de 2 minutes, mesuré du chargement de la page d'inscription jusqu'à la redirection post-inscription, en conditions normales (sans contrainte de charge).
- **SC-002**: Un utilisateur existant peut se connecter en moins de 30 secondes, mesuré du chargement de la page de connexion jusqu'à la redirection post-authentification, en conditions normales.
- **SC-003**: Le flux Google OAuth2 complet (clic → autorisation Google → retour sur l'application connecté) se complète en moins de 60 secondes.
- **SC-004**: 100% des mots de passe sont stockés sous forme hachée — aucun mot de passe en clair en base de données.
- **SC-005**: Les tentatives de connexion par force brute depuis une même IP sont bloquées après 10 tentatives échouées consécutives — l'IP est verrouillée pendant 15 minutes.
- **SC-006**: Les pages de connexion et d'inscription s'affichent correctement sur les résolutions desktop et mobile, en conformité visuelle avec le Design System existant. La conformité est validée par une revue visuelle lors de la pull request.
- **SC-007**: 0 doublon de compte pour le même email, quelle que soit la méthode d'inscription utilisée.
- **SC-008**: 100% des événements d'authentification clés (connexion, déconnexion, inscription, OAuth2) sont journalisés et consultables — aucun événement critique silencieux.
- **SC-009**: Les tentatives d'inscription massives depuis une même IP sont bloquées après 5 tentatives par heure.

## Assumptions

- Les comptes utilisateurs de "La collection des aventuriers" sont entièrement indépendants de tout autre service partenaire (pas de base partagée, pas de session commune).
- Un utilisateur qui s'inscrit via Google ne peut pas, dans un premier temps, définir un mot de passe pour son compte (hors scope v1 — peut faire l'objet d'une évolution ultérieure).
- La vérification de l'email lors de l'inscription classique n'est pas requise pour la v1 (pas d'envoi d'email de confirmation) — peut être ajoutée ultérieurement.
- Le pseudo est soumis à une contrainte UNIQUE en base de données. L'email est l'identifiant principal du système, mais le pseudo doit également être unique. Si un pseudo Google est déjà pris, le système applique un suffixe numérique incrémental (ex. `jean_paul_2`, `jean_paul_3`…) sans limite jusqu'à trouver un pseudo disponible.
- Les pages d'inscription et de connexion existent déjà dans la structure de templates Twig du Design System et seront stylisées avec les classes Bootstrap personnalisées du projet.
- La compatibilité navigateur cible les deux dernières versions majeures des navigateurs modernes (Chrome, Firefox, Safari, Edge) — aucun support d'Internet Explorer ou de navigateurs obsolètes n'est requis.
- La gestion de la récupération de mot de passe (mot de passe oublié) est hors scope pour cette spécification.
