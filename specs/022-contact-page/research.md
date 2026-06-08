# Research: Page "Nous Contacter" fonctionnelle

**Feature**: 022-contact-page | **Date**: 2026-06-08

## Status: No NEEDS CLARIFICATION items

All technical choices were resolved during spec clarification (see `spec.md` — Clarifications section)
and confirmed by codebase analysis. No research tasks were required.

## Confirmed Decisions

### Symfony Mailer (envoi email)

- **Decision**: Utiliser `symfony/mailer` 7.2 (déjà installé, `MAILER_DSN=null://null` dans `.env`)
- **Rationale**: Déjà utilisé dans `AuthMailerService`, `EmailVerificationService`, `PasswordResetService` — pattern établi dans le projet
- **Pattern**: `ContactMailerService` injectable via DI, injecte `MailerInterface`, construit un `Email()` avec `->from()` / `->to()` / `->subject()` / `->text()`
- **Variables .env**: `CONTACT_EMAIL_FROM` (expéditeur) et `CONTACT_EMAIL_TO` (destinataire)

### Route et contrôleur

- **Decision**: `ContactController` avec deux routes : `GET /contact` (affichage) et `POST /contact/send` (traitement AJAX)
- **Rationale**: Pattern thin controller + service, identique à `LegalController`/`SuggestionController`
- **Route name**: `app_contact` (convention du projet `app_{nom}`)
- **Response POST**: JSON (`JsonResponse`) — le frontend utilise `fetch()`

### CSRF

- **Decision**: Hidden input `<input type="hidden" name="_token" value="{{ csrf_token('contact') }}">` dans le template ; validation `isCsrfTokenValid('contact', $token)` dans le contrôleur
- **Rationale**: Spécifié explicitement dans FR-007b et la session de clarification

### JavaScript côté client

- **Decision**: Reprendre tel quel le bloc `<script>` de `design/contact.html` dans le template Twig
- **Rationale**: Spec assumption — "Le fichier `design/contact.html` contient déjà le JavaScript de validation côté client complet — ce code DOIT être repris tel quel"
- **Adaptation**: Le submit handler JS est à étendre pour :
  1. Récupérer le token CSRF depuis le champ caché
  2. Envoyer les données via `fetch()` en POST JSON
  3. Désactiver le bouton "Envoyer" pendant la requête
  4. Afficher `.form-success` ou une erreur selon la réponse

### Pré-remplissage utilisateur connecté

- **Decision**: Données passées dans le template Twig via `$this->getUser()` dans le contrôleur GET, variables Twig `userPseudo` et `userEmail`
- **Rationale**: Pattern standard Symfony — pas besoin d'un endpoint dédié ni de Turbo Stream pour du simple pré-remplissage à l'affichage initial

### Footer

- **Decision**: Modifier `templates/components/Layout/Footer.html.twig` — remplacer `<li><a href="#">Devenir modérateur</a></li>` par `<li><a href="{{ path('app_contact') }}">Nous contacter</a></li>`
- **Rationale**: FR-011 ; le lien actuel est une ancre morte (`#`)

### Mentions légales

- **Decision**: Remplacer les deux `href="#contact"` (sections 02 et 05) par `href="{{ path('app_contact') }}"` dans `templates/legal/mentions-legales.html.twig`
- **Rationale**: FR-012 ; actuellement des ancres internes (#contact) qui pointent vers la section contact de la page mentions légales elle-même, pas vers la page de contact
