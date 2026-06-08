# Implementation Plan: Page "Nous Contacter" fonctionnelle

**Branch**: `022-contact-page` | **Date**: 2026-06-08 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/022-contact-page/spec.md`

## Summary

Rendre fonctionnelle la page `/contact` : intégration du design `design/contact.html` en template Twig, validation JS côté client (reprise telle quelle), validation serveur complète, envoi d'email via Symfony Mailer (`ContactMailerService`), protection CSRF, pré-remplissage pour les utilisateurs connectés, mise à jour du footer et des mentions légales.

## Technical Context

**Language/Version**: PHP 8.2 / Symfony 7.2 LTS

**Primary Dependencies**: Symfony Mailer 7.2 (déjà installé, `MAILER_DSN` dans `.env`), Symfony Security (session `app.user`), Twig, Bootstrap + design tokens du projet

**Storage**: Aucune persistance en base — traitement par envoi d'email uniquement

**Testing**: PHPUnit (convention du projet) — tests fonctionnels pour `ContactController`, tests unitaires pour `ContactMailerService`

**Target Platform**: Platform.sh (Linux, déploiement identique aux autres features)

**Project Type**: Web application Symfony (MVC, templates Twig)

**Performance Goals**: Aucune exigence particulière au-delà des standards du site

**Constraints**: Pas de rate limiting dans ce ticket ; JavaScript requis (noscript notice prévue)

**Scale/Scope**: Page unique, 2 nouveaux fichiers PHP, 1 nouveau template Twig, 2 templates modifiés, 2 variables `.env`

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| # | Principle | Status | Notes |
|---|-----------|--------|-------|
| I | Complémentarité Stricte | ✅ PASS | Page de contact = utilitaire du site, pas de forum ni contenu concurrent avec La Taverne |
| II | Architecture Symfony LTS | ✅ PASS | `ContactController` thin (HTTP uniquement), `ContactMailerService` pour toute la logique métier, DI throughout, pas de Doctrine (pas de persistence) |
| III | Workflow de Validation du Contenu | ✅ PASS | Aucun contenu soumis en base — le message transite par email uniquement |
| IV | RBAC | ⚠️ DEVIATION (justifiée — voir Complexity Tracking) | Route POST publique par exigence métier (FR-001) ; CSRF token présent mais `#[IsGranted]` impossible |
| V | Sécurité et Couverture de Tests | ✅ PASS | Tests fonctionnels contrôleur + tests unitaires service à écrire |

## Project Structure

### Documentation (this feature)

```text
specs/022-contact-page/
├── plan.md              # Ce fichier
├── research.md          # Phase 0 — N/A (aucun unknown)
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
└── tasks.md             # Phase 2 output (/speckit-tasks)
```

### Source Code (repository root)

```text
src/
├── Controller/
│   └── ContactController.php          # NOUVEAU — GET /contact + POST /contact/send
└── Service/
    └── ContactMailerService.php       # NOUVEAU — logique envoi email

templates/
├── contact/
│   └── contact.html.twig              # NOUVEAU — intégration design/contact.html
├── components/Layout/
│   └── Footer.html.twig               # MODIFIÉ — "Devenir modérateur" → "Nous contacter"
└── legal/
    └── mentions-legales.html.twig     # MODIFIÉ — 2 ancres #contact → path('app_contact')

.env / .env.dist                       # MODIFIÉ — ajout CONTACT_EMAIL_FROM + CONTACT_EMAIL_TO
config/services.yaml                   # MODIFIÉ — binding des deux paramètres pour ContactMailerService

tests/
├── Controller/
│   └── ContactControllerTest.php      # NOUVEAU — tests fonctionnels
└── Service/
    └── ContactMailerServiceTest.php   # NOUVEAU — tests unitaires
```

**Structure Decision**: Single Symfony project, extension du pattern existant (cf. `LegalController` + `AuthMailerService`). Pas d'ajout d'infrastructure Platform.sh car Symfony Mailer est déjà configuré.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|--------------------------------------|
| Principe IV — `#[IsGranted]` absent sur POST `/contact/send` | FR-001 : la page de contact doit être accessible sans authentification — c'est l'exigence principale de la feature | Restreindre la route à `ROLE_USER` exclurait les visiteurs non connectés, ce qui contredit directement FR-001 et US-1. La protection CSRF seule est suffisante pour une page de contact publique standard. |
