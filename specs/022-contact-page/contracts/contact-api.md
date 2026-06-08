# API Contract: Contact Endpoint

**Feature**: 022-contact-page | **Date**: 2026-06-08

## Endpoints

### GET /contact

Affiche la page de contact.

**Auth**: Publique (pas d'authentification requise)

**Response**: `200 OK` — rendu HTML du template `contact/contact.html.twig`

**Behaviour**: Si `app.user` est connecté, les variables Twig `userPseudo` (string|null) et `userEmail` (string) sont passées au template pour pré-remplissage.

---

### POST /contact/send

Traite la soumission du formulaire de contact.

**Auth**: Publique (pas d'authentification requise)

**Content-Type request**: `application/json`

**Request body**:

```json
{
  "_token": "string (CSRF token, required)",
  "prenom":  "string|null (max 100 chars)",
  "nom":     "string|null (max 100 chars)",
  "pseudo":  "string|null (max 100 chars)",
  "email":   "string (required, valid email, max 254 chars)",
  "raison":  "string (required, whitelist value)",
  "message": "string (required, non-empty, max 5000 chars)"
}
```

**Responses**:

| HTTP | Body | Condition |
|------|------|-----------|
| `200 OK` | `{"success": true}` | Email envoyé avec succès |
| `400 Bad Request` | `{"success": false, "message": "Requête invalide."}` | Body JSON malformé ou Content-Type incorrect |
| `403 Forbidden` | `{"success": false, "message": "Requête invalide."}` | Token CSRF invalide ou absent |
| `422 Unprocessable Entity` | `{"success": false, "errors": ["...message..."]}` | Erreur de validation métier (identité, email, raison, message) |
| `500 Internal Server Error` | `{"success": false, "message": "Une erreur est survenue, veuillez réessayer."}` | Échec d'envoi Symfony Mailer |

**Validation rules** (côté serveur, miroir du JS client) :
1. Token CSRF : `isCsrfTokenValid('contact', $_token)` — sinon 403
2. Identité : `(trim($pseudo) !== '') OR (trim($prenom) !== '' AND trim($nom) !== '')` — sinon 422
3. Email : format valide + non vide — sinon 422
4. Raison : valeur dans la liste blanche — sinon 422
5. Message : trim non vide — sinon 422
6. Longueurs max : prenom/nom/pseudo ≤ 100, email ≤ 254, message ≤ 5000 — sinon 422

**Note**: Le contrôleur ne loggue pas les détails d'erreur Mailer en réponse (protection contre la fuite d'information).
