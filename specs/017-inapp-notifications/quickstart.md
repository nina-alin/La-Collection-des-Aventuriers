# Quickstart: Développement Local — Notifications In-App

## Prérequis

- PHP 8.2+, Composer, Node 20, Symfony CLI
- PostgreSQL local (ou via Docker)
- Projet déjà installé et fonctionnel

## 1. Installer Symfony Messenger

```bash
composer require symfony/messenger
```

Cela génère `config/packages/messenger.yaml`. Remplacer le contenu par la config Doctrine transport :

```yaml
framework:
  messenger:
    failure_transport: failed
    transports:
      async:
        dsn: '%env(DATABASE_URL)%'
        options:
          use_notify: false
      failed:
        dsn: '%env(DATABASE_URL)%'
        options:
          queue_name: failed
    routing:
      'App\Messenger\Message\NotificationMessage': async
```

## 2. Créer les tables (migration)

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

Tables créées : `notification`, `notification_preference`, `user_collection_subscription` + colonne `user.timezone`.

Créer aussi les tables Messenger :

```bash
php bin/console messenger:setup-transports
```

## 3. Lancer le worker Messenger (terminal séparé)

```bash
php bin/console messenger:consume async --memory-limit=128M -vv
```

## 4. Injecter des notifications de test

```bash
php bin/console app:dev:seed-notifications
# (commande à créer en DataFixtures ou commande dédiée)
```

Ou directement via fixtures Foundry / DataFixtures.

## 5. Vérifier le badge

Charger n'importe quelle page en étant connecté → le badge de cloche doit afficher le bon compteur.

```bash
symfony server:start
# ouvrir http://localhost:8000 en tant qu'utilisateur avec notifications
```

## 6. Tester le panel Live Component

- Cliquer sur la cloche → panneau s'ouvre, notifications groupées par date
- Cliquer sur une notification → marquée lue, redirection vers cible
- Cliquer "Tout marquer lu" → badge passe à 0 sans rechargement

## 7. Tester les préférences

- Aller dans Profil → Paramètres → section Notifications
- Désactiver un type → vérifier que les notifications non lues de ce type sont supprimées
- Déclencher un événement pour ce type → vérifier qu'aucune nouvelle notification n'est créée

## 8. Tests unitaires

```bash
php bin/phpunit tests/Notification/
```

## Dispatcher manuel d'événements (dev)

```php
// Dans une commande ou contrôleur de test
$event = new ContributionValidatedEvent($workEntry, $recipient);
$dispatcher->dispatch($event);
// Worker Messenger traitera le NotificationMessage asynchrone
```

## Fichiers clés

| Fichier | Rôle |
|---------|------|
| `src/Entity/Notification.php` | Entité principale |
| `src/Entity/NotificationPreference.php` | Préférences utilisateur |
| `src/Entity/UserCollectionSubscription.php` | Abonnements collections |
| `src/Messenger/Handler/NotificationMessageHandler.php` | Création notification asynchrone |
| `src/Twig/Components/Notification/NotificationPanelComponent.php` | Live Component panneau |
| `src/Twig/Extension/NotificationExtension.php` | Global `unread_count` Twig |
| `src/EventListener/ContributionValidatedListener.php` | Dispatch point ModerationService |
| `config/packages/messenger.yaml` | Config transport async |
