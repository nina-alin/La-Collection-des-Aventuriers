# Feature Specification: Landing Page Publique Dynamique

**Feature Branch**: `020-landing-page-dynamic`

**Created**: 2026-06-07

**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Visiteur non-connecté découvre la plateforme (Priority: P1)

Un visiteur arrive sur la page d'accueil sans compte. Il voit une page publique attrayante avec des données réelles sur la communauté : nombre de fiches, d'auteurs, nouveautés de la semaine. Il peut rechercher un titre, explorer le catalogue ou créer un compte.

**Why this priority**: Point d'entrée principal de la plateforme pour tout nouveau visiteur. Sans cette page, aucune conversion n'est possible.

**Independent Test**: Ouvrir la page d'accueil sans être connecté → vérifier que la page s'affiche avec des données dynamiques (stats, marquee) et que les boutons d'action fonctionnent.

**Acceptance Scenarios**:

1. **Given** un visiteur non-connecté, **When** il accède à la racine du site, **Then** il voit la landing page publique (non le dashboard connecté)
2. **Given** la landing page chargée, **When** les données backend sont disponibles, **Then** la pilule de stats affiche le vrai nombre de fiches, d'auteurs et de nouveautés de la semaine
3. **Given** la landing page chargée, **When** le bandeau défilant se charge, **Then** il affiche des entités réelles (livres, auteurs, collections) en défilement continu
4. **Given** un utilisateur connecté, **When** il accède à la racine, **Then** il est redirigé vers son tableau de bord personnel (comportement inchangé)

---

### User Story 2 — Visiteur effectue une recherche depuis la landing page (Priority: P2)

Un visiteur tape un terme dans la barre de recherche centrale et valide. Il arrive directement sur le catalogue avec ses résultats filtrés.

**Why this priority**: La recherche est le chemin d'acquisition le plus direct vers le contenu. Un visiteur qui trouve ce qu'il cherche est plus susceptible de s'inscrire.

**Independent Test**: Saisir "Lone Wolf" dans la barre de recherche → valider → vérifier l'arrivée sur le catalogue avec les résultats filtrés et le terme pré-rempli.

**Acceptance Scenarios**:

1. **Given** la barre de recherche, **When** le visiteur saisit un terme et appuie sur Entrée, **Then** il est redirigé vers le catalogue avec le terme de recherche actif
2. **Given** la barre de recherche, **When** le visiteur clique sur "Explorer", **Then** même comportement que la touche Entrée
3. **Given** la barre de recherche vide, **When** le visiteur clique sur "Explorer le catalogue", **Then** il est redirigé vers le catalogue sans filtre actif

---

### User Story 3 — Visiteur interagit avec le bandeau défilant (Priority: P2)

Un visiteur passe la souris sur un élément du bandeau défilant. Le défilement s'arrête pour lui permettre de lire et cliquer. Il clique sur une fiche et est redirigé vers la page de cette entité.

**Why this priority**: Le marquee est la vitrine du contenu disponible. Il doit être interactif, pas juste décoratif.

**Independent Test**: Survoler un élément du marquee → vérifier l'arrêt du défilement → cliquer → vérifier la redirection vers la bonne fiche.

**Acceptance Scenarios**:

1. **Given** le bandeau en défilement, **When** la souris survole un élément, **Then** le défilement se met en pause
2. **Given** un élément survolé, **When** la souris quitte l'élément, **Then** le défilement reprend
3. **Given** un élément cliquable, **When** le visiteur clique dessus, **Then** il est redirigé vers la page de l'entité correspondante (livre, auteur ou collection)

---

### User Story 4 — Visiteur voit les statistiques animées en scrollant (Priority: P3)

En scrollant vers la section "L'Archive en Mouvement", le visiteur voit trois grands compteurs s'animer de 0 jusqu'au chiffre réel. L'animation ne se déclenche qu'une fois, quand la section entre dans le viewport.

**Why this priority**: Effet visuel d'impact pour renforcer la crédibilité de la plateforme. Améliore l'expérience sans impacter la fonctionnalité principale.

**Independent Test**: Charger la page → scroller jusqu'à la section stats → vérifier l'animation des compteurs depuis 0 vers les valeurs réelles.

**Acceptance Scenarios**:

1. **Given** la section stats hors du viewport au chargement, **When** l'utilisateur scrolle jusqu'à elle, **Then** les compteurs s'animent de 0 vers les valeurs cibles
2. **Given** la section stats déjà visible, **When** l'utilisateur scrolle ailleurs puis revient, **Then** l'animation ne se rejoue pas
3. **Given** les valeurs cibles, **When** l'animation se termine, **Then** les chiffres affichés correspondent exactement aux données backend

---

### User Story 5 — Visiteur bascule le thème clair/sombre (Priority: P3)

Le visiteur clique sur le bouton thème (lune/soleil) dans la navbar. L'ensemble du site bascule en mode sombre. Le choix est mémorisé pour ses prochaines visites.

**Why this priority**: Confort visuel. Fonctionnalité attendue sur les plateformes modernes.

**Independent Test**: Cliquer sur le bouton thème → vérifier le changement visuel → recharger la page → vérifier que le thème choisi est conservé.

**Acceptance Scenarios**:

1. **Given** le mode clair par défaut, **When** le visiteur clique sur le bouton thème, **Then** l'interface bascule en mode sombre
2. **Given** le mode sombre actif, **When** le visiteur recharge la page, **Then** le mode sombre est conservé
3. **Given** le mode sombre actif, **When** le visiteur re-clique sur le bouton, **Then** l'interface revient en mode clair

---

### Edge Cases

- Que se passe-t-il si le backend ne répond pas pour les stats ? → La pilule de stats est masquée et les compteurs affichent "--" (jamais "0", ce qui serait trompeur).
- Que se passe-t-il si le bandeau est vide (aucune entité populaire) ? → Le bandeau est masqué (`display: none` sur le bloc entier).
- Que se passe-t-il sur mobile où le hover n'existe pas ? → Le bandeau défile normalement ; tap = navigation directe (comportement natif `<a>`, pas de pause intermédiaire). La pause au survol est desktop uniquement.
- Que se passe-t-il si un utilisateur déjà connecté accède à la landing page (ex: via un lien direct) ? → Redirection automatique vers le dashboard.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Le système DOIT afficher une page d'accueil publique accessible sans authentification à la racine du site (lorsque l'utilisateur n'est pas connecté).
- **FR-002**: Le système DOIT rediriger un utilisateur connecté qui accède à la racine vers son tableau de bord personnel.
- **FR-003**: La navbar DOIT contenir un logo/titre cliquable (retour haut de page), un bouton de bascule de thème et un bouton "Se connecter".
- **FR-004**: Le bouton "Se connecter" DOIT rediriger vers la page de connexion/inscription.
- **FR-005**: Le bouton de thème DOIT basculer entre mode clair et sombre, mémoriser le choix via localStorage (clé `'theme'`, valeur `'parchment'` pour clair / `'dark'` pour sombre — cohérent avec le contrôleur existant `profile_menu_controller`), et l'appliquer sur l'ensemble du site — le layout de base lit localStorage et applique `data-theme` sur `<html>` à chaque chargement de page.
- **FR-006**: La pilule de statistiques DOIT afficher dynamiquement : le nombre total de fiches, le nombre total d'auteurs, et le nombre de nouvelles fiches ajoutées dans les 7 derniers jours.
- **FR-007**: La barre de recherche DOIT rediriger vers la page catalogue avec le terme de recherche pré-rempli comme filtre actif, à la validation (Entrée ou clic bouton).
- **FR-008**: Le bandeau défilant DOIT afficher dynamiquement une liste d'entités populaires (livres, auteurs, collections), en défilement horizontal continu et infini.
- **FR-018**: Pendant le chargement des données API (stats pilule, marquee, compteurs), la page DOIT afficher des skeleton placeholders (zones grises animées) respectant la mise en page définie dans `design/landing.html`.
- **FR-009**: Le défilement du bandeau DOIT se mettre en pause au survol d'un élément et reprendre à la sortie.
- **FR-010**: Chaque élément du bandeau DOIT être un lien cliquable vers la fiche de l'entité correspondante : `/livre/{slug}` pour les livres, `/collections/{slug}` pour les collections, `/authors/{slug}` pour les auteurs.
- **FR-011**: Le bouton "Nous rejoindre" DOIT rediriger vers la page de création de compte.
- **FR-012**: Le bouton "Explorer le catalogue" DOIT rediriger vers le catalogue sans filtre actif.
- **FR-013**: La section "L'Archive en Mouvement" DOIT afficher trois compteurs animés avec des valeurs issues du backend : **livres répertoriés** (`total_books`), **aventuriers inscrits** (`total_users`), **livres répertoriés** (`total_books` — le label "contributions validées" du design est abandonné car aucune métrique distincte n'existe en backend ; voir Clarifications).
- **FR-014**: L'animation des compteurs DOIT se déclencher uniquement lors de la première entrée de la section dans le viewport (via IntersectionObserver ou équivalent).
- **FR-015**: Les sections "Écosystème" et "Trois Piliers" sont statiques — aucun branchement de données requis.
- **FR-016**: La section CTA finale DOIT reprendre les mêmes boutons "Nous rejoindre" et "Voir le catalogue" avec les mêmes comportements.
- **FR-017**: La page DOIT respecter intégralement le design défini dans `design/landing.html` (structure, typographie, espacements, couleurs).
- **FR-020**: Le layout de base DOIT inclure un script inline dans `<head>` (avant tout chargement CSS) qui lit localStorage et applique immédiatement la classe dark mode sur `<body>`, afin d'éviter tout flash de thème au chargement.
- **FR-019**: La page DOIT respecter les critères WCAG AA : attributs ARIA appropriés sur tous les éléments interactifs (boutons, liens, champ de recherche), navigation clavier complète (focus visible, ordre logique), skip-link vers le contenu principal, et ratios de contraste conformes.

### Key Entities

- **Statistiques globales** : agrégats du catalogue — `total_books` (total livres), `total_users` (utilisateurs inscrits, label UI "aventuriers inscrits"), `new_this_week` (nouvelles fiches semaine). Note : aucune métrique "contributions" distincte n'existe — le troisième compteur de la section "Archive" réutilise `total_books` (voir FR-013).
- **Entités du marquee** : livres, auteurs, collections — identifiées par leur nom, type, et URL de fiche
- **Préférence de thème** : clé localStorage `'theme'`, valeurs `'parchment'` (mode clair) / `'dark'` (mode sombre) — cohérent avec `profile_menu_controller` existant

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: La page d'accueil publique se charge et affiche des données dynamiques en moins de 2 secondes sur une connexion standard.
- **SC-002**: 100% des liens de la page (navbar, marquee, CTAs) redirigent vers la destination correcte.
- **SC-003**: Le choix de thème est conservé après rechargement de page dans 100% des cas.
- **SC-004**: Les compteurs animés affichent exactement les valeurs retournées par le backend à la fin de l'animation.
- **SC-005**: Le comportement de pause du marquee au survol fonctionne sur tous les navigateurs desktop modernes.
- **SC-006**: Un utilisateur connecté accédant à la racine est redirigé vers son dashboard en moins de 500ms.
- **SC-007**: Tous les éléments interactifs sont accessibles au clavier (Tab/Entrée/Espace) et dotés d'attributs ARIA conformes WCAG AA.

## Clarifications

### Session 2026-06-07 (release gate)

- Q: Valeur localStorage pour le thème clair — `'light'` (T017 initial) ou `'parchment'` (profile_menu_controller existant) ? → A: `'parchment'` — le nouveau `theme_controller` DOIT utiliser `'parchment'`/`'dark'` pour rester compatible avec `profile_menu_controller` qui gère le toggle côté dashboard utilisateur connecté.
- Q: Le troisième compteur "contributions validées" — aucune métrique distincte n'existe, quelle alternative ? → A: Label renommé en "livres répertoriés", métrique `total_books` (proxy intentionnel) — FR-013 et tasks T016 mis à jour en conséquence.
- Q: L'assertion "endpoints existants déjà en place" est inexacte — les endpoints `/api/public/*` n'existent pas, faut-il corriger ? → A: Oui — les controllers et repositories existent, mais les endpoints API publics sont nouveaux. Assumption corrigée.

### Session 2026-06-07 (suite)

- Q: La bascule de thème s'applique-t-elle à l'ensemble du site ou seulement à la landing page ? → A: Site-wide — le layout de base lit localStorage sur chaque page et applique la classe sur `<body>` globalement.
- Q: Bandeau vide — masqué ou message neutre ? → A: Masqué (`display: none` sur le bloc entier).
- Q: Flash de thème au chargement — toléré ou à éviter ? → A: À éviter — script inline dans `<head>` (avant tout CSS) applique la classe dark mode immédiatement si localStorage indique dark.
- Q: Sur mobile, tap sur élément du marquee — navigation directe ou pause d'abord ? → A: Navigation directe — tap = clic natif `<a>`, la pause au survol est desktop uniquement.
- Q: Marquee infinite loop — JS duplique les items ou l'API garantit assez d'entités ? → A: L'API garantit suffisamment d'items — pas de duplication JS nécessaire, CSS animation seule suffit.

### Session 2026-06-07

- Q: Les données de stats et du marquee viennent-elles d'endpoints existants ou de nouveaux ? → A: Les controllers et repositories backend existent (BookRepository, ContributorRepository, CollectionRepository, UserRepository — méthodes validées), mais les endpoints publics `/api/public/stats` et `/api/public/marquee` sont nouveaux et doivent être créés (T005 LandingService + T006 PublicApiController).
- Q: Pendant le chargement API, que doit afficher la page ? → A: Skeleton placeholders (zones grises animées).
- Q: Si le backend est indisponible, que montrent pilule de stats et compteurs ? → A: Masquer la zone ou afficher "--" (jamais "0").
- Q: "Aventuriers inscrits" (FR-013) et "utilisateurs inscrits" (Key Entities) désignent-ils la même métrique ? → A: Oui — même chiffre (nombre de comptes), label UI "aventuriers inscrits", terme technique "utilisateurs inscrits".
- Q: Quels patterns de routes pour les liens du marquee ? → A: `/livre/{slug}` (livres), `/collections/{slug}` (collections), `/authors/{slug}` (auteurs — route en anglais confirmée via ContributorController).
- Q: Les endpoints backend existants pour stats et marquee — quels sont leurs chemins exacts ? → A: Chemins à découvrir dans les controllers existants au moment de l'implémentation.
- Q: La landing page doit-elle respecter des exigences d'accessibilité formelles ? → A: WCAG AA — attributs ARIA complets, navigation clavier testée, skip-link.
- Q: Durée et easing de l'animation des compteurs — spécifiés dans `design/landing.html` ou libre choix ? → A: Libre choix — 2s ease-out.
- Q: Sous-titre des livres dans le marquee — `frenchPublicationYear` ou `originalPublicationYear` ? Fallback si null ? → A: `frenchPublicationYear`; si null, afficher `"Livre"` (sans année).

## Assumptions

- Le mode clair est le thème par défaut de la landing page publique.
- La préférence de thème est stockée dans le navigateur (pas en base de données) — applicable à toutes les pages du site (landing, catalogue, dashboard, fiches) pour visiteurs non-connectés comme connectés.
- "Populaires" pour le marquee signifie : les entités les mieux notées ou les plus récemment consultées — le critère exact est laissé à l'implémentation (une liste mixte de 20-30 éléments suffit). L'API garantit assez d'items pour remplir le viewport — pas de duplication JS côté client.
- La landing page est accessible à `/` uniquement quand l'utilisateur n'est pas connecté — le routing conditionnel est géré côté serveur.
- Les routes existantes (`/connexion`, `/inscription`, `/catalogue`) restent inchangées.
- Le paramètre URL de recherche utilisé par le catalogue est `q` (vérifié dans `ActiveFilterState::fromRequest()` ligne 59 — mappe vers la propriété interne `searchQuery`, mais le paramètre HTTP est bien `?q=`).
- Les repositories et méthodes de données existent (tous validés). Les endpoints publics `/api/public/stats` et `/api/public/marquee` sont nouveaux — créés par T005 (LandingService) et T006 (PublicApiController).
- La section "Écosystème" et "Trois Piliers" ne nécessitent aucune donnée dynamique — texte en dur dans le template.
- L'animation des compteurs ne se joue qu'une fois par chargement de page (pas de rejeu au re-scroll). Durée : 2s, easing : ease-out.
- Le design `design/landing.html` est la référence finale et absolue pour le HTML/CSS — aucune liberté de design n'est accordée à l'implémenteur.
