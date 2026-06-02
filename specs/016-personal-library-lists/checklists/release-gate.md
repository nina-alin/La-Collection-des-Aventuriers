# Release Gate Checklist: Gestion de la Bibliothèque Personnelle (Listes Livre)

**Purpose**: Full-coverage formal release gate — validate requirement completeness, clarity, consistency, and measurability before implementation
**Created**: 2026-06-01
**Depth**: Formal release gate
**Audience**: Author self-check
**Feature**: [spec.md](../spec.md) · [plan.md](../plan.md)

---

## Business Rule Requirements

- [x] CHK001 — Est-ce que la règle d'exclusion mutuelle `isOwned` ↔ `isToBuy` est explicitement spécifiée comme **bidirectionnelle** dans les deux sens (activer l'un désactive l'autre, et vice versa) ? [Clarity, Spec §FR-004, Assumptions]
- [x] CHK002 — Les noms exacts et la sémantique des 4 champs booléens (`isOwned`, `isToRead`, `isToBuy`, `isFavorite`) sont-ils cohérents entre spec.md, plan.md, data-model.md et les deux contrats ? [Consistency, Spec §Key Entities]
- [x] CHK003 — L'indépendance de `isToRead` est-elle spécifiée pour **toutes** les combinaisons — owned+toRead, toBuy+toRead, aucun statut+toRead ? [Completeness, Spec §FR-005]
- [x] CHK004 — L'indépendance de `isFavorite` est-elle spécifiée pour le cas où `isOwned` est désactivé (US4.2) ? Le favori doit rester actif même quand on retire le livre de la collection. [Completeness, Spec §US4 Scenario 2]
- [x] CHK005 — Les effets de bord des règles d'auto-cohérence sont-ils spécifiés de façon cohérente dans le **contrat de service** (`UserBookService.md`) ET dans la spec, sans contradiction ? [Consistency, Contract §UserBookService]
- [x] CHK006 — La symétrie de la cascade (`toggleToBuy` désactive `isOwned`) est-elle explicitement documentée dans les requirements, pas seulement dans les Assumptions ? [Completeness, Spec §FR-004, Assumptions]

---

## UI/UX Requirements

- [x] CHK007 — Les exigences d'état visuel actif/inactif sont-elles spécifiées **pour chacun des 4 boutons indépendamment** (y compris l'état du bouton auto-désactivé lors d'une cascade) ? [Completeness, Spec §FR-002]
- [x] CHK008 — Le retour visuel "immédiat" (FR-002) est-il quantifié — le critère <300ms (SC-001) s'applique-t-il au re-rendu perçu côté client ou uniquement à la réponse serveur ? [Clarity, Spec §FR-002 + SC-001]
- [x] CHK009 — Les 8 messages de toast (4 actions × ajout/retrait) sont-ils tous spécifiés avec leur libellé exact dans le contrat de composant ? [Completeness, Contract §Toast Messages Matrix]
- [x] CHK010 — La durée d'affichage du toast (3–5 s) est-elle une **exigence** ou une **assumption** ? Si assumption, est-ce documenté et assumé explicitement ? [Clarity, Spec §Assumptions]
- [x] CHK011 — Les exigences d'accessibilité sont-elles définies pour le groupe de boutons (`role="group"`, `aria-label`, navigation clavier, gestion du focus) ? [Coverage, Spec §FR-011 — ajouté 2026-06-01]
- [x] CHK012 — L'état désactivé/loading pendant une requête en vol est-il spécifié comme comportement **automatique Symfony UX** ou doit-il être décrit visuellement comme exigence explicite ? [Clarity, Spec §FR-008]
- [x] CHK013 — Est-il spécifié que le bouton **auto-annulé** (ex. : "À acheter" éteint lors d'un toggle "Ma Collection") doit changer d'état dans le **même cycle de rendu** que le bouton principal ? [Completeness, Spec §US1 Scenario 3]
- [x] CHK014 — Le message de toast d'**erreur** (libellé exact, type `error`) est-il spécifié pour les cas d'échec réseau et d'erreur serveur ? [Completeness, Contract §Toast Events §Error]

---

## Data Model & Migration Requirements

- [x] CHK015 — La stratégie de migration pour les enregistrements `DANS_MA_COLLECTION`, `A_ACHETER`, `A_LIRE` vers les booléens est-elle spécifiée avec un mapping explicite ? [Completeness, Spec §Assumptions]
- [x] CHK016 — La décision de **suppression** des enregistrements `LU`/`PAS_DANS_MA_COLLECTION` (clean start, perte de données intentionnelle) est-elle documentée comme décision délibérée non réversible ? [Clarity, Spec §Clarifications]
- [x] CHK017 — Les exigences de **non-nullabilité** et de **valeur par défaut** (`false`) pour les 3 nouvelles colonnes booléennes (`is_owned`, `is_to_read`, `is_to_buy`) sont-elles spécifiées pour la migration ? [Completeness, data-model.md §Migration Plan — résolu]
- [x] CHK018 — L'invariant cascade-delete (tous les 4 booléens false → supprimer le record `UserBook`) est-il cohérent entre la spec et le contrat de service ? Est-ce que `isFavorite` est inclus dans la condition de suppression ? [Ambiguity, Spec §Key Entities + Contract §Invariants]
- [x] CHK019 — L'exigence de **contrainte d'unicité** (user_id, book_id) sur la table `UserBook` est-elle spécifiée pour la migration Doctrine ? [Completeness, Contract §Idempotence]
- [x] CHK020 — Le comportement d'un `UserBook` avec `isFavorite=true` mais les 3 autres booleans à `false` est-il spécifié — doit-on conserver ou supprimer ce record ? [data-model.md §Invariant + `isAllInactive()` — record conservé si isFavorite=true, résolu]

---

## Authentication & Security Requirements

- [x] CHK021 — L'exigence `#[IsGranted('ROLE_USER')]` est-elle spécifiée **par méthode `#[LiveAction]`** (granularité correcte), pas seulement au niveau du composant ? [Clarity, Spec §FR-007, Contract §Security]
- [x] CHK022 — La protection CSRF "automatique via Symfony UX" est-elle documentée comme **assumption technique** avec les limites de cette garantie (ex. : requête hors-composant ne serait pas couverte) ? [Assumption, Spec §SC-005]
- [x] CHK023 — La réponse HTTP attendue pour un appel direct non authentifié (401 vs. redirection login) est-elle spécifiée sans ambiguïté ? [Clarity, Spec §Edge Cases, Contract §Security]
- [x] CHK024 — Le garde de visibilité Twig (`{% if app.user %}`) est-il documenté comme couche de défense en profondeur **distincte** du garde de mutation `#[IsGranted]` ? [Completeness, Contract §Security]
- [x] CHK025 — L'exigence que tout appel non authentifié ne produise **aucune modification en base** est-elle explicitement énoncée dans les requirements ? [Completeness, Spec §Edge Cases]

---

## Error Handling & Resilience Requirements

- [x] CHK026 — Le mécanisme de rollback visuel (état restauré sur erreur serveur) est-il spécifié — "calculé depuis la DB à chaque re-rendu" est-il une exigence suffisante ou faut-il un rollback optimiste explicite ? [Clarity, Spec §FR-009, Contract §Computed State]
- [x] CHK027 — La protection double-clic est-elle spécifiée comme comportement **automatique Symfony UX** ou comme exigence explicite de désactivation du bouton ? Si automatique, la dépendance à Symfony UX est-elle documentée comme assumption ? [Clarity, Spec §FR-008]
- [x] CHK028 — Le comportement lors d'une session expirée (HTTP 401/302 pendant un toggle) est-il spécifié avec le **libellé exact du toast** et le comportement attendu (pas de modification d'état, pas de redirection silencieuse) ? [Completeness, Spec §Edge Cases]
- [x] CHK029 — Le cas d'un livre introuvable (404) est-il spécifié comme "les actions sont inatteignables par design" (composant non rendu) ou comme "les actions retournent une erreur explicite" ? [Clarity, Spec §Edge Cases]
- [x] CHK030 — Le modèle de propagation des erreurs Doctrine (exception propagée → composant attrape → toast erreur) est-il spécifié avec ce qui **ne doit pas** être avalé silencieusement ? [Completeness, Contract §Error Handling]
- [x] CHK031 — Le comportement en cas de requêtes concurrent (même utilisateur, même livre, deux toggles simultanés) est-il spécifié ou explicitement hors scope ? [Coverage, Spec §FR-012 — last-write-wins, ajouté 2026-06-01]

---

## Non-Functional Requirements

- [x] CHK032 — L'exigence <300ms (SC-001) est-elle définie sur quel percentile (P50/P95) et depuis quel point de mesure (temps serveur, temps client perçu, temps de re-rendu complet) ? [Clarity, Spec §SC-001]
- [x] CHK033 — L'exigence "100% des cas testés" (SC-002) est-elle définie avec une méthodologie de mesure ou reste-t-elle une assertion non vérifiable ? [Measurability, Spec §SC-002]
- [x] CHK034 — Des exigences de performance sont-elles définies pour la requête DB `findByUserAndBook()` exécutée à **chaque re-rendu** du composant ? [Gap, Plan §Performance Goals]
- [x] CHK035 — L'exigence d'atomicité d'un `flush()` unique pour les modifications d'auto-cohérence (les deux champs modifiés ensemble) est-elle spécifiée comme contrainte d'intégrité ? [Completeness, Contract §Invariants]

---

## Test Coverage Requirements

- [x] CHK036 — Les exigences de tests unitaires sont-elles spécifiées pour **tous** les invariants métier de `UserBookService` (exclusion mutuelle, cascade delete, les 4 méthodes de toggle) ? [Completeness, Spec §SC-004]
- [x] CHK037 — Les exigences de tests fonctionnels couvrent-elles les scénarios d'authentification (connecté vs. non connecté) pour **chaque LiveAction** ? [Completeness, Spec §SC-004]
- [x] CHK038 — Des exigences de test d'idempotence sont-elles définies (toggle × 2 = état initial) pour les 4 méthodes ? [Completeness, Contract §Idempotence]
- [x] CHK039 — Des exigences de test sont-elles définies pour vérifier que l'**effet de bord auto-cohérence** est visible dans le rendu HTML du composant (pas seulement en base) ? [Coverage, Spec §SC-003]
- [x] CHK040 — Des exigences de test de régression sont-elles spécifiées pour la migration — garantir qu'aucun enregistrement `DANS_MA_COLLECTION`, `A_ACHETER`, `A_LIRE` n'est perdu par erreur ? [Coverage, Gap]

---

## Ambiguities & Open Questions

- [x] CHK041 — L'origine du champ `isFavorite` est-elle documentée — était-il déjà présent avant cette feature, et la migration doit-elle le préserver dans son état actuel ? [Ambiguity, Spec §Assumptions]
- [x] CHK042 — La limite de scope (fiche livre uniquement, pas les cartes catalogue) est-elle référencée dans la section **Requirements** et pas seulement dans les Assumptions ? [Clarity, Spec §Assumptions]
- [x] CHK043 — La définition de "bouton actif" (classe CSS `is-active`) et sa représentation visuelle sont-elles spécifiées comme exigence ou entièrement déléguées au CSS existant ? [Ambiguity, Contract §Template]
