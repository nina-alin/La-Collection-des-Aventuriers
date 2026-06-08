# Data Model: Page "Nous Contacter"

**Feature**: 022-contact-page | **Date**: 2026-06-08

## Entités

### ContactMessage (DTO — pas de persistence Doctrine)

Représente les données validées d'un formulaire de contact avant envoi par email.
Utilisé comme objet de transfert entre le contrôleur et `ContactMailerService`.

| Champ | Type PHP | Contraintes | Notes |
|-------|----------|-------------|-------|
| `prenom` | `?string` | max 100 chars | Null si pseudonyme fourni |
| `nom` | `?string` | max 100 chars | Null si pseudonyme fourni |
| `pseudo` | `?string` | max 100 chars, trim non-vide | Null si prénom+nom fournis |
| `email` | `string` | obligatoire, format RFC 5322, max 254 chars | |
| `raison` | `string` | obligatoire, valeur parmi liste blanche | Voir valeurs autorisées ci-dessous |
| `message` | `string` | obligatoire, trim non-vide, max 5000 chars | |

**Règle d'identité** : `($pseudo non vide) OU ($prenom non vide ET $nom non vide)` — au moins un bloc doit être complet.

**Valeurs autorisées pour `raison`** (liste blanche côté serveur) :
```
question-site, signaler-probleme, erreur-fiche, suggerer-oeuvre,
devenir-moderateur, contester-moderation, donnees-personnelles,
partenariat, autre
```

**Libellés correspondants** (pour le sujet de l'email) :
```
question-site         → "J'ai une question sur le site"
signaler-probleme     → "Je souhaite remonter un problème"
erreur-fiche          → "Je souhaite signaler une erreur dans une fiche"
suggerer-oeuvre       → "Je souhaite suggérer un livre ou une œuvre"
devenir-moderateur    → "Je souhaite devenir modérateur"
contester-moderation  → "Je souhaite contester une décision de modération"
donnees-personnelles  → "Question sur mes données personnelles"
partenariat           → "Partenariat, presse ou association"
autre                 → "Autre"
```

### Identité de l'expéditeur (règle de nommage pour le sujet email)

```
Si pseudo non vide  → identifiant = $pseudo
Sinon               → identifiant = "$prenom $nom"
```

Sujet de l'email : `[Contact] {libellé raison} — {identifiant}`

## Variables d'environnement

| Variable | Description | Exemple |
|----------|-------------|---------|
| `CONTACT_EMAIL_FROM` | Adresse expéditeur des emails de contact | `contact@collection-aventuriers.fr` |
| `CONTACT_EMAIL_TO` | Adresse destinataire (équipe) | `equipe@collection-aventuriers.fr` |

## Pas de migration Doctrine

Ce modèle ne génère aucune entité Doctrine et n'implique aucune migration de base de données.
