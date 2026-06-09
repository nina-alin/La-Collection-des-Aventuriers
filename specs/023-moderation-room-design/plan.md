# Implementation Plan: Salle de Modération — Intégration du Design

**Branch**: `023-moderation-room-design` | **Date**: 2026-06-09 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/023-moderation-room-design/spec.md`

## Summary

Intégration du design de la Salle de Modération : comparateur diff côte à côte pour les suggestions PENDING (diff calculé server-side via `jfcherng/php-diff`), panneau latéral sticky de file d'attente, bascule Vue Flux / Vue Tableau, modale de refus avec motif, et section "Gestion globale" des fiches — le tout piloté par fetch JS sur les routes existantes d'approbation/refus, sans rechargement de page.

## Technical Context

**Language/Version**: PHP 8.2, Symfony 7.2 LTS

**Primary Dependencies**:
- `jfcherng/php-diff` (à installer — diff mot-à-mot server-side, non présent dans composer.json)
- Doctrine ORM 3.x, Symfony Security, Twig, Webpack Encore, Stimulus (déjà installés)

**Storage**: PostgreSQL (Platform.sh managed)

**Testing**: PHPUnit 12.5 + Symfony BrowserKit + Panther (déjà configurés)

**Target Platform**: Platform.sh / Linux

**Project Type**: Web application Symfony

**Performance Goals**: Rendu diff < 200ms p95 pour un champ textuel typique (résumé ~500 mots)

**Constraints**:
- Aucun nouveau framework JS (constitution)
- Aucune migration DB requise (tous DTOs, pas de nouvelles colonnes)
- `moderateSuggestion()` dans `ModerationService` ne persiste pas de `SuggestionRefusal` — lacune à corriger dans le flux refus

**Scale/Scope**: ~6 types d'entités, 1 DiffService, 5 normalizers, 2 routes modifiées, 3 routes ajoutées, 1 template principal mis à jour + 4 partials Twig

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I — Complémentarité Stricte | ✅ PASS | Outillage modérateur interne, aucun forum, aucune concurrence avec La Taverne |
| II — Architecture Symfony LTS | ✅ PASS | Thin controllers, DiffService + normalizers en couche Service, Doctrine ORM only. `Symfony\Component\DependencyInjection\ServiceLocator` est le pattern officiel Symfony pour les tagged services — pas l'anti-pattern ServiceLocator générique. Voir Complexity Tracking. |
| III — Workflow de Validation | ✅ PASS | Les suggestions restent PENDING jusqu'à décision du modérateur ; ce feature implémente précisément la transition PENDING→VALIDATED/REFUSED |
| IV — RBAC | ✅ PASS | `#[IsGranted('ROLE_MODERATOR')]` déjà sur `ModerationController`. CSRF token exposé via `data-csrf-token`, vérifié dans chaque route mutante. |
| V — Sécurité et Tests | ⚠️ REQUIRED | PHPUnit requis : `DiffService` (unit), normalizers (unit), routes approve/refuse (functional avec CSRF). Tests à créer dans cette feature. |

**Post-design re-check** : aucune nouvelle colonne DB, aucun nouveau voter, aucun asset framework externe → toutes les gates restent PASS.

## Project Structure

### Documentation (this feature)

```text
specs/023-moderation-room-design/
├── plan.md              ← ce fichier
├── research.md          ← Phase 0 output
├── data-model.md        ← Phase 1 output
├── contracts/
│   ├── routes.md        ← HTTP routes contract
│   └── normalizer-interface.md  ← EntityNormalizerInterface contract
└── tasks.md             ← Phase 2 output (/speckit-tasks)
```

### Source Code

```text
src/
├── Controller/
│   └── ModerationController.php          ← modifié (index, approveSuggestion, refuseSuggestion + 3 new actions)
├── Dto/
│   ├── DiffField.php                     ← nouveau
│   └── DiffResult.php                    ← nouveau
└── Service/
    ├── DiffService.php                   ← nouveau
    ├── ModerationService.php             ← modifié (moderateSuggestion + refusal reason)
    └── Normalizer/
        ├── EntityNormalizerInterface.php  ← nouveau
        ├── BookNormalizer.php             ← nouveau
        ├── ContributorNormalizer.php      ← nouveau (AUTHOR + ILLUSTRATOR + TRADUCTOR via ContributionRole)
        ├── EditorNormalizer.php           ← nouveau
        └── CollectionNormalizer.php       ← nouveau

templates/moderation/
├── dashboard.html.twig                   ← modifié (ajout Sections II + III)
├── _diff_panel.html.twig                 ← nouveau (comparateur + barre d'actions)
├── _queue_panel.html.twig                ← nouveau (panneau latéral "La Suite")
├── _table_view.html.twig                 ← nouveau (Vue Tableau)
└── _entities_table.html.twig            ← nouveau (Gestion globale tbody partial)

assets/controllers/
└── moderation-room_controller.js         ← nouveau (Stimulus : toggle vue, fetch approve/refuse, swap diff panel)

config/services.yaml                      ← modifié (tag EntityNormalizerInterface + ServiceLocator binding)
```

**Structure Decision**: Single Symfony project, backend-first. Twig partials pour les échanges JS (approche HTML-over-the-wire compatible avec Stimulus). Pas de JSON brut d'entité côté JS — le diff est calculé et rendu server-side, JS reçoit uniquement un `nextSuggestionId` et re-fetch le partial HTML.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|--------------------------------------|
| `Symfony\Component\DependencyInjection\ServiceLocator` dans `DiffService` | 6 types d'entités hétérogènes (Book, Contributor×3, Editor, Collection) nécessitent une résolution runtime par clé `SuggestionEntityType` | Un `match` ou `switch` dans le contrôleur ou le service violerait le principe "thin controller" et empêcherait l'ajout de nouveaux types sans modification du dispatcher. Le `ServiceLocator` Symfony est le pattern officiel pour les tagged services avec dispatch runtime — il diffère de l'anti-pattern décrit dans la constitution (container global passé en paramètre). |
