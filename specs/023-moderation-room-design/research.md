# Research: Salle de Modération — Intégration du Design

## PHP Diff Library (word-level)

**Decision**: `jfcherng/php-diff`

**Rationale**:
- Supporte le diff caractère/mot via `CharacterDiff` et `WordDiff` renderers
- Retourne un tableau annoté d'opérations (`INSERT`/`DELETE`/`EQUAL`) exploitable pour générer les classes `.ins`/`.del` dans Twig
- Activement maintenu (2024), PHP 7.4+, aucune dépendance externe
- Licence MIT

**Alternatives considérées**:
- `sebastian/diff` : line-level uniquement, pas de word diff — éliminé
- `wikimedia/diff` : word-level possible mais API plus bas niveau, moins documentée — éliminé
- Implémentation maison via `str_word_count` + LCS : maintenance burden, tests à écrire — éliminé

**Installation**: `composer require jfcherng/php-diff`

---

## Mapping SuggestionEntityType → Entité Doctrine

| SuggestionEntityType | Entité Doctrine | Repository | Notes |
|----------------------|-----------------|------------|-------|
| `BOOK` | `Book` | `BookRepository` | id: `int` (GeneratedValue) |
| `AUTHOR` | `Contributor` | `ContributorRepository` | id: `Uuid`. Role AUTHOR via `ContributionRole` (pas de colonne type sur Contributor) |
| `ILLUSTRATOR` | `Contributor` | `ContributorRepository` | Même entité que AUTHOR |
| `TRADUCTOR` | `Contributor` | `ContributorRepository` | Même entité que AUTHOR |
| `EDITOR` | `Editor` | `EditorRepository` | id: `int` (GeneratedValue) |
| `COLLECTION` | `Collection` | `CollectionRepository` | id: `Uuid` |

**Conséquence sur les normalizers** : AUTHOR, ILLUSTRATOR, TRADUCTOR partagent un seul `ContributorNormalizer` (les 3 types produisent les mêmes champs). Le `DiffService` résout `ContributorNormalizer` pour ces trois clés via trois entrées dans le ServiceLocator mapping.

**Champ `sourceEntityId` de `Suggestion`** : de type `Uuid`. Pour `Book` et `Editor` (id `int`), le repository doit recevoir la valeur convertie — `(int)$suggestion->getSourceEntityId()->toRfc4122()` ne fonctionnera pas. Résolution : les normalizers reçoivent l'`object $entity` déjà chargé par le contrôleur. Le contrôleur charge l'entité source via le repository correct en passant le `Uuid` converti si besoin. Voir `contracts/normalizer-interface.md` pour le détail du contrat.

**Clarification**: Pour `Book` (id int) et `Editor` (id int), `sourceEntityId` (Uuid) doit être converti. On stocke l'id réel dans `sourceEntityId` via `Uuid::fromString((string) $intId)` côté suggestion submission, ou on fait une recherche par un autre critère. À vérifier dans `SuggestionService::submit()` — pour l'instant, le contrôleur tentera `$repo->find($suggestion->getSourceEntityId()->toRfc4122())` et gérera le cas non trouvé (NULL → NEW_ENTRY comportement).

---

## Gestion du CSRF dans les fetch JS

**Decision**: Token `csrf_token('moderate_' . $id)` généré en Twig, exposé sur `data-csrf-token` des boutons Valider/Refuser. JS lit l'attribut et l'envoie en `FormData` clé `_csrf_token`. Le contrôleur vérifie avec `$this->isCsrfTokenValid('moderate_' . $id, ...)`.

**Rationale**: Pattern déjà utilisé dans `ModerationController` pour les routes existantes. Cohérence totale, aucun nouveau mécanisme.

---

## Rendu diff côté serveur vs client

**Decision**: Server-side Symfony service (`DiffService`). Le contrôleur passe un `DiffResult` pré-calculé à Twig. JS ne reçoit jamais de données brutes d'entité.

**Rationale**: Conforme à la spec (FR-004 explicite). Évite de sérialiser des graphes d'objets Doctrine en JSON. Simplifie le JS (pas de logique diff côté client). Compatible avec l'approche HTML-over-the-wire (partials Twig).

---

## Architecture JS — Vue toggle et fetch

**Decision**: Stimulus controller unique `moderation-room` avec targets : `diffPanel`, `tableView`, `queuePanel`, `fluxToggle`, `tableToggle`.

**Rationale**:
- Stimulus est déjà installé (`symfony/stimulus-bundle ^2.35`)
- Un seul controller couvre tout le comportement JS de la page
- Approche HTML partials : après approve/refuse, JS reçoit `{nextId}` puis fetch `GET /moderation/suggestion/{nextId}/diff-partial` → swaps `diffPanel` innerHTML

**Constitution gate**: Stimulus est un framework JS léger déjà présent dans le projet — pas d'introduction de nouveau framework.

---

## ModerationService — lacune refusal reason

**Decision**: Modifier `ModerationService::moderateSuggestion()` pour accepter un `?string $reason` optionnel. Quand `$newStatus === REFUSED && $reason !== null`, créer et persister un `SuggestionRefusal`.

**Rationale**: Le `SuggestionRefusal` existe en base et la spec requiert que le motif soit persisté (FR-016, Acceptance Scenario 2.4). Le contrôleur actuel ne passe pas le reason — c'est une lacune existante que cette feature corrige.

---

## Route Gestion Globale — server-side search/filter

**Decision**: Nouvelle route `GET /moderation/entities?search=&type=` retournant un partial HTML `_entities_table.html.twig` (uniquement le `<tbody>`). JS remplace le `<tbody>` cible. Timeout debounce 300ms sur l'input de recherche.

**Rationale**: Server-side conforme à la clarification spec (Session 2026-06-09). Pas d'API JSON à définir — retour HTML pur évite la sérialisation et reste cohérent avec l'approche Twig du projet. Pas de pagination pour l'instant (hors scope).

**Entités listées**: toutes les entités modérables (Book, Contributor, Editor, Collection) avec un repository générique ou des appels parallèles. Implémentation initiale : 4 queries séquentielles, merge PHP, tri par `updatedAt` DESC. Optimisation possible en Phase 2 si lenteur.
