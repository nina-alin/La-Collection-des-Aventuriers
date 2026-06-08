# Feature Specification: Page Mentions Légales

**Feature Branch**: `021-mentions-legales-page`

**Created**: 2026-06-07

**Status**: Draft

**Input**: User description: "Intégrer la page de contenu textuel 'Mentions Légales', accessible depuis le footer, avec navigation interne (sommaire interactif / ScrollSpy) et accessibilité du contenu."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Consulter les Mentions Légales (Priority: P1)

Un visiteur du site souhaite consulter les informations légales de l'application. Il clique sur le lien "Mentions légales" dans le footer, arrive sur une page bien structurée avec un sommaire et peut lire l'intégralité des informations légales (éditeur, hébergeur, droits d'auteur, données personnelles, etc.).

**Why this priority**: C'est la fonctionnalité core de ce ticket — la page doit exister, être accessible et afficher un contenu légal complet et lisible. Sans cela, rien d'autre n'a de sens.

**Independent Test**: Peut être testé en naviguant vers la route `/mentions-legales` et en vérifiant que la page s'affiche correctement avec tout le contenu textuel.

**Acceptance Scenarios**:

1. **Given** un visiteur sur n'importe quelle page, **When** il clique sur le lien "Mentions légales" dans le footer, **Then** il est redirigé vers la page `/mentions-legales`
2. **Given** la page Mentions Légales, **When** elle se charge, **Then** elle affiche un fil d'Ariane "ACCUEIL > MENTIONS LÉGALES", un titre principal, la date de dernière mise à jour, un sommaire numéroté, et toutes les sections de contenu
3. **Given** la page Mentions Légales, **When** elle s'affiche sur desktop (≥900px), **Then** la mise en page est en deux colonnes : sommaire à gauche, contenu à droite
4. **Given** la page Mentions Légales, **When** elle s'affiche sur mobile (<900px), **Then** la mise en page est en une seule colonne avec le sommaire accessible avant le contenu principal

---

### User Story 2 - Naviguer via le Sommaire Interactif (Priority: P1)

Un visiteur sur desktop consulte les Mentions Légales. Il utilise le sommaire latéral pour naviguer directement vers une section précise sans avoir à faire défiler manuellement toute la page.

**Why this priority**: Le ScrollSpy est un différenciateur UX essentiel pour ce type de page légale longue — sans lui, la page devient difficile à parcourir. Classé P1 avec le contenu car il est explicitement requis dans les specs.

**Independent Test**: Peut être testé en cliquant sur chaque lien du sommaire et en vérifiant le défilement et la mise en évidence de l'état actif.

**Acceptance Scenarios**:

1. **Given** la page sur desktop avec le sommaire visible, **When** l'utilisateur clique sur "4. Nature du projet" dans le sommaire, **Then** la page défile en douceur (smooth scroll) jusqu'à la section correspondante
2. **Given** l'utilisateur qui fait défiler manuellement la page, **When** une nouvelle section entre dans le viewport, **Then** l'élément correspondant dans le sommaire prend l'état actif (mise en gras, changement de couleur, indicateur latéral)
3. **Given** l'utilisateur qui fait défiler vers le bas, **When** il descend dans la page, **Then** le sommaire reste visible et fixe (sticky) à l'écran
4. **Given** l'utilisateur sur mobile, **When** il consulte la page, **Then** le sommaire est présenté sous forme de liste simple (toujours visible, non rétractable) en haut de page, avant le contenu

---

### User Story 3 - Accéder aux Liens Internes (Priority: P2)

Un visiteur lit les Mentions Légales et rencontre des références à d'autres pages de l'application (page de contact, charte communautaire, politique de confidentialité). Il peut cliquer sur ces liens pour y accéder directement.

**Why this priority**: Les liens internes assurent la cohérence juridique et la navigation fluide entre les documents légaux. P2 car la page est fonctionnelle sans eux, mais leur absence serait un défaut notable.

**Independent Test**: Peut être testé en cliquant sur chaque lien inline et en vérifiant la redirection vers la bonne route.

**Acceptance Scenarios**:

1. **Given** la page Mentions Légales, **When** l'utilisateur repère un lien vers la "page de contact", **Then** le lien est visuellement identifiable (souligné ou coloré selon la maquette) et redirige vers `/contact`
2. **Given** la page Mentions Légales, **When** l'utilisateur clique sur "politique de confidentialité", **Then** il est redirigé vers la route appropriée de l'application
3. **Given** la page Mentions Légales, **When** l'utilisateur clique sur "charte communautaire", **Then** il est redirigé vers la route appropriée de l'application

---

### Edge Cases

- Que se passe-t-il si la date de mise à jour n'est pas configurée dans le CMS/fichier de configuration ? Le champ s'affiche vide (chaîne vide) — aucune date de remplacement n'est affichée.
- Comment le ScrollSpy se comporte-t-il si deux sections courtes sont visibles simultanément à l'écran ? L'élément actif doit correspondre à la section dont le titre est le plus proche du haut du viewport.
- Que se passe-t-il si l'utilisateur arrive sur la page via un lien direct avec ancre (ex: `/mentions-legales#section-04`) ? La page doit défiler automatiquement vers la bonne section à l'ouverture.
- Sur mobile, le sommaire est une liste toujours visible (non rétractable) — confirmé, pas d'accordéon ni de menu déroulant.
- Le contenu légal est indexable : `<title>` et `<meta description>` sont requis (FR-019, FR-020), pas de noindex (FR-021). Les métadonnées structurées (schema.org, JSON-LD) sont explicitement hors scope.

## Requirements *(mandatory)*

### Functional Requirements

**Page & Navigation**

- **FR-001**: Le système DOIT exposer la page Mentions Légales à la route `/mentions-legales`
- **FR-002**: Le footer DOIT contenir un lien "Mentions légales" pointant vers cette route
- **FR-003**: La page DOIT afficher un fil d'Ariane cliquable "ACCUEIL > MENTIONS LÉGALES" en haut de page, avec "ACCUEIL" lié à `/` (landing page publique)
- **FR-004**: La page DOIT afficher une date de dernière mise à jour lue depuis un paramètre Symfony dédié (ex: `app.legal.last_updated` dans `config/services.yaml`), passé au template Twig via le contrôleur — non codée en dur dans le template. Le format d'affichage est `d MMMM Y` (ex : "3 juin 2026"). Si le paramètre est absent ou vide, le champ date s'affiche vide (chaîne vide — aucun texte de remplacement ni date par défaut)

**Mise en page & Responsive**

- **FR-005**: Sur desktop (viewport ≥900px), la page DOIT utiliser une disposition en deux colonnes : sommaire à gauche (240px fixe), contenu à droite (1fr) — seuil issu de la maquette de référence, différent du breakpoint Bootstrap `md` standard
- **FR-006**: Sur mobile (viewport <900px), la page DOIT passer en disposition une colonne, le sommaire apparaissant avant le contenu
- **FR-007**: La colonne de contenu DOIT respecter une largeur maximale lisible (max ~720px) avec marges appropriées

**Sommaire & ScrollSpy**

- **FR-008**: Sur desktop, le sommaire DOIT être sticky (position fixe dans le viewport) pendant le défilement de la page
- **FR-009**: Chaque élément du sommaire DOIT déclencher un défilement fluide (smooth scroll) vers la section correspondante au clic — implémenté via la propriété CSS `scroll-behavior: smooth` appliquée à l'élément `html`
- **FR-010**: Le système DOIT détecter automatiquement la section courante pendant le défilement et mettre à jour l'état actif du sommaire en conséquence — implémenté via le composant natif **Bootstrap Scrollspy** (`data-bs-spy="scroll"`, `data-bs-target`). Le composant DOIT être configuré avec un `rootMargin` de `-88px 0px -65% 0px` (hauteur du header sticky) afin que la mise en évidence tienne compte de l'espace masqué par le header. Au chargement de la page, avant tout défilement, le premier élément du sommaire DOIT être en état actif **sur tous les viewports** (l'activation initiale est inconditionnelle). Sur mobile (viewport <900px), le Scrollspy DOIT être désactivé — seule la mise en évidence dynamique (suivi du défilement) n'est pas active sur ce viewport ; l'état actif initial du premier élément est conservé
- **FR-011**: L'élément du sommaire en état actif DOIT être visuellement distinct : texte en gras, couleur différenciée, indicateur latéral (barre ou trait)

**Composants de Contenu**

- **FR-012**: Chaque section DOIT afficher un titre stylisé avec numéro en deux chiffres ("01", "02"...) suivi du titre en majuscules avec police Serif
- **FR-013**: Les informations structurées (éditeur, hébergeur, contact) DOIVENT être présentées dans des tableaux key-value avec bordures arrondies et fond beige clair
- **FR-014**: Les encarts d'alerte/information DOIVENT s'afficher avec icône (triangle "Attention"), fond coloré distinct, et contenu textuel. L'icône est décorative : elle DOIT porter l'attribut `aria-hidden="true"` — son message est intégralement porté par le texte de l'encart
- **FR-015**: Les liens inline dans le texte DOIVENT être visuellement identifiables (soulignés ou colorés) et pointer vers les routes correctes de l'application

**Accessibilité**

- **FR-016**: La page DOIT utiliser une hiérarchie sémantique correcte (balises `<main>`, `<nav>`, `<section>`, titres `<h1>`/`<h2>`/`<h3>`)
- **FR-017**: Le sommaire DOIT être balisé avec un élément `<aside aria-label="Sommaire">` — créant un landmark ARIA de type `complementary` sans dupliquer le rôle `navigation` du header principal
- **FR-018**: L'état actif du sommaire DOIT être communiqué aux technologies d'assistance (attribut `aria-current="true"` ou équivalent)

**SEO**

- **FR-019**: La page DOIT inclure une balise `<title>` descriptive (ex : "Mentions légales — [Nom de l'application]")
- **FR-020**: La page DOIT inclure une balise `<meta name="description">` résumant brièvement le contenu légal. Le texte exact est hors scope de cette spécification et sera fourni lors de la rédaction du contenu final — aucune limite de caractères ni contrainte de formulation n'est imposée par ce ticket
- **FR-021**: La page NE DOIT PAS inclure de balise `<meta name="robots" content="noindex">` — elle doit être indexable par les moteurs de recherche

### Key Entities

- **LegalPageConfig** : Paramètre Symfony `app.legal.last_updated` déclaré dans `config/services.yaml`, injecté dans le template Twig via le contrôleur. Permet de mettre à jour la date sans modifier le template — seul `config/services.yaml` est édité.
- **Section** : Unité de contenu identifiée par un identifiant d'ancre unique, un numéro d'ordre, un titre et un corps de texte. Chaque section correspond à un élément du sommaire.
- **TableauKeyValue** : Nouveau template partiel Twig (ou macro) à créer dans ce ticket — affiche des paires clé/valeur dans une table stylisée (bordures arrondies, fond beige). Instances : éditeur du site, hébergeur, contact.
- **AlertBlock** : Nouveau template partiel Twig (ou macro) à créer dans ce ticket — affiche un encart d'information/alerte avec icône et texte de mise en évidence.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: La page est accessible depuis le footer en 1 clic depuis n'importe quelle page de l'application
- **SC-002**: Sur desktop, 100% des éléments du sommaire déclenchent un défilement fluide vers la section cible
- **SC-003**: Sur desktop, l'état actif du sommaire se met à jour dans un délai imperceptible (<100ms) pendant le défilement manuel
- **SC-004**: La mise à jour de la date de "Dernière mise à jour" ne nécessite aucune modification du template Twig (seul `config/services.yaml` est édité)
- **SC-005**: La page est entièrement lisible et navigable sur mobile sans défilement horizontal ni contenu tronqué
- **SC-006**: La page respecte un niveau d'accessibilité WCAG 2.1 AA pour la navigation au clavier et les technologies d'assistance
- **SC-007**: Tous les liens inline redirigent vers les routes correctes sans erreur 404

## Assumptions

- **Stack technique** : l'application utilise Symfony (PHP) avec Twig comme moteur de template et Bootstrap comme framework CSS/JS. Le ScrollSpy est implémenté via le composant natif Bootstrap Scrollspy (pas de lib JS tierce). Le routing est géré par le Symfony Router. Les templates sont des fichiers `.html.twig`.
- Le fichier de design `design/mentions-legales.html` référencé dans la description sera créé/fourni avant la phase d'implémentation ; en son absence, l'implémentation se basera sur les spécifications visuelles décrites dans ce document et les conventions du design system existant (`specs/001-frontend-design-system`)
- Le design system du projet (`specs/001-frontend-design-system`) dispose déjà des tokens de couleurs, typographies (Serif pour les titres), et variables Bootstrap/CSS (beige clair) nécessaires — aucun nouveau token global n'est requis
- La page de contenu des Mentions Légales est statique : le texte légal lui-même est codé dans le template Twig, seule la date de mise à jour est externalisée
- La route `/mentions-legales` n'existe pas encore dans l'application et doit être créée (contrôleur Symfony + route)
- Les routes cibles des liens inline (`/contact`, `/politique-de-confidentialite`, `/charte-communautaire`) existent ou seront créées dans des tickets séparés ; si elles n'existent pas encore au moment de l'implémentation, les liens PEUVENT temporairement utiliser `href="#"` en attendant la création de ces routes
- Le seuil de breakpoint mobile/desktop pour cette page est fixé à **900px** — valeur issue de la maquette de référence, qui diffère du breakpoint Bootstrap `md` (768px) standard ; 900px est la valeur à implémenter pour cette page spécifiquement
- Le contenu légal (texte des sections) est fourni en français et ne nécessite pas de gestion multilingue
- Les métadonnées structurées (schema.org, JSON-LD) sont explicitement hors scope pour cette page
- La page n'a pas d'état authentifié/non-authentifié : elle est accessible à tous les visiteurs sans connexion requise

## Clarifications

### Session 2026-06-08 (suite)

- Q: L'activation initiale du premier élément du sommaire (classe `active` + `aria-current="true"`) est-elle conditionnelle au viewport ≥900px ou inconditionnelle ? → A: Inconditionnelle — le premier élément est toujours actif au chargement, sur tous les viewports. Seul le suivi dynamique du défilement (Scrollspy live-tracking) est gardé par `if (window.innerWidth >= 900)`. Les deux instructions sont distinctes dans le code.
- Q: Les liens "page de contact" dans les sections #02 et #05 — utiliser `href="#contact"` (ancre in-page, comme dans la maquette) ou `href="#"` (placeholder spec) ? → A: `href="#"` — placeholder uniforme pour tous les liens vers des routes non encore créées (`/contact`, `/politique-de-confidentialite`, `/charte-communautaire`)

### Session 2026-06-08

- Q: Quel format d'affichage pour `app.legal.last_updated` ? → A: `d MMMM Y` (ex : "3 juin 2026")
- Q: Que s'affiche-t-il si le paramètre `app.legal.last_updated` est absent ou vide ? → A: Champ vide — aucun texte de remplacement ni date par défaut
- Q: Quel est l'état initial du sommaire au chargement de la page (avant tout défilement) ? → A: Le premier élément est actif
- Q: Le contenu exact de la balise `<meta name="description">` est-il spécifié ? → A: Non — hors scope, fourni lors de la rédaction du contenu final
- Q: Le Scrollspy est-il actif sur mobile ? → A: Non — désactivé en dessous du seuil responsive
- Q: L'icône du composant AlertBlock (triangle "Attention") est-elle décorative ou sémantique ? → A: Décorative — `aria-hidden="true"`, le message est porté par le texte
- Q: WCAG 2.1 AA doit-il être décomposé en critères de succès spécifiques dans la spec ? → A: Non — la référence à "WCAG 2.1 AA" est suffisante
- Q: Les métadonnées structurées (schema.org) sont-elles en scope ? → A: Non — explicitement hors scope
- Q: Implémentation du Scrollspy — Bootstrap Scrollspy ou IntersectionObserver custom (comme dans la maquette) ? → A: Bootstrap Scrollspy (`data-bs-spy`) avec `rootMargin: '-88px 0px -65% 0px'`
- Q: Seuil de breakpoint — 768px (spec) ou 900px (maquette) ? → A: 900px (maquette fait référence)
- Q: Élément HTML du sommaire — `<nav>` (spec) ou `<aside>` (maquette) ? → A: `<aside aria-label="Sommaire">`
- Q: Smooth scroll — ajouter ou utiliser des ancres simples comme dans la maquette ? → A: Ajouter — CSS `scroll-behavior: smooth` sur `html`
- Q: Liens inline vers routes non encore créées — `href="#"` ou routes planifiées ? → A: `href="#"` acceptable comme placeholder temporaire

### Session 2026-06-07

- Q: Comportement du sommaire sur mobile : toujours visible ou rétractable ? → A: Liste toujours visible, non rétractable (pas d'accordéon ni de menu déroulant)
- Q: Quelles métadonnées SEO sont requises ? → A: `<title>` et `<meta name="description">` obligatoires, pas de canonical ni d'OG tags
- Q: Quel est le stack technique d'implémentation ? → A: Symfony (PHP) + Twig + Bootstrap — routing Symfony, templates `.html.twig`, ScrollSpy via composant natif Bootstrap
- Q: Comment externaliser la date de mise à jour des mentions légales (LegalPageConfig) ? → A: Paramètre Symfony `app.legal.last_updated` dans `config/services.yaml`, passé au template via le contrôleur
- Q: Les composants TableauKeyValue et AlertBlock existent-ils déjà dans le projet ? → A: Non, nouveaux templates partiels Twig / macros Bootstrap à créer dans ce ticket
- Q: Vers quelle URL le lien "ACCUEIL" du fil d'Ariane doit-il pointer ? → A: `/` (landing page publique)
