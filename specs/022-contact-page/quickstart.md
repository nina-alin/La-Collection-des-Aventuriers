# Quickstart: Page "Nous Contacter"

**Feature**: 022-contact-page | **Date**: 2026-06-08

## Résumé des fichiers à créer / modifier

### Créer

| Fichier | Rôle |
|---------|------|
| `src/Controller/ContactController.php` | Route GET `/contact` + POST `/contact/send` |
| `src/Service/ContactMailerService.php` | Logique envoi email (réutilise le pattern `AuthMailerService`) |
| `templates/contact/contact.html.twig` | Intégration de `design/contact.html` en Twig |
| `tests/Controller/ContactControllerTest.php` | Tests fonctionnels |
| `tests/Service/ContactMailerServiceTest.php` | Tests unitaires |

### Modifier

| Fichier | Changement |
|---------|------------|
| `.env` / `.env.dist` | Ajouter `CONTACT_EMAIL_FROM=` et `CONTACT_EMAIL_TO=` |
| `config/services.yaml` | Binding des deux variables pour `ContactMailerService` |
| `templates/components/Layout/Footer.html.twig` | Remplacer lien `#` "Devenir modérateur" → `path('app_contact')` "Nous contacter" |
| `templates/legal/mentions-legales.html.twig` | 2 occurrences `href="#contact"` → `href="{{ path('app_contact') }}"` |

## ContactController — squelette

```php
#[Route('/contact', name: 'app_contact', methods: ['GET'])]
public function index(): Response
{
    $user = $this->getUser();
    return $this->render('contact/contact.html.twig', [
        'userPseudo' => $user?->getPseudo(),     // string|null
        'userEmail'  => $user?->getEmail() ?? '',
    ]);
}

#[Route('/contact/send', name: 'app_contact_send', methods: ['POST'])]
public function send(Request $request, ContactMailerService $mailer): JsonResponse
{
    // 1. Décoder JSON
    // 2. Valider CSRF → 403 si invalide
    // 3. Valider données → 422 si invalide
    // 4. ContactMailerService::send() → 500 si exception
    // 5. Retourner 200 + {success: true}
}
```

## ContactMailerService — squelette

```php
public function send(
    ?string $prenom, ?string $nom, ?string $pseudo,
    string $email, string $raison, string $message
): void {
    $identifiant = $pseudo ?: "$prenom $nom";
    $sujet = "[Contact] {$this->raisonLabel($raison)} — $identifiant";

    $mail = (new Email())
        ->from($this->from)
        ->to($this->to)
        ->subject($sujet)
        ->text(...);

    $this->mailer->send($mail);
}
```

## Template Twig — points d'adaptation clés

1. Étendre `base.html.twig` (`{% extends 'base.html.twig' %}`)
2. Ajouter `<input type="hidden" name="_token" value="{{ csrf_token('contact') }}">` dans le formulaire
3. Pré-remplir `value="{{ userEmail }}"` et `value="{{ userPseudo ?? '' }}"` sur les champs correspondants
4. Ajouter le bloc `<noscript>` (FR-006b)
5. Adapter le JS submit pour : récupérer `_token`, envoyer `fetch('/contact/send', {method:'POST', body: JSON.stringify({...})})`  désactiver le bouton pendant la requête, et gérer les réponses JSON

## Variables d'environnement à ajouter

```env
CONTACT_EMAIL_FROM=contact@collection-aventuriers.fr
CONTACT_EMAIL_TO=equipe@collection-aventuriers.fr
```

## Vérification rapide

```bash
# Démarrer le serveur local
symfony server:start

# Accéder à la page
open http://localhost:8000/contact

# Vérifier les routes
bin/console debug:router | grep contact
```
