# Contracts: Follow/Unfollow Endpoints

**Type**: HTTP (Symfony routes, JSON responses)

---

## POST /follow/contributor/{id}

**Description**: Toggle follow/unfollow pour un Créateur. Idempotent.

**Auth**: `ROLE_USER` requis (`#[IsGranted('ROLE_USER')]`)

**Route name**: `follow_contributor_toggle`

**Request**:
```
POST /follow/contributor/{id}
Content-Type: application/x-www-form-urlencoded

_token={csrf_token}
```

- `{id}` : UUID du Contributor (string)
- `_token` : CSRF token, name = `follow_contributor_{id}`

**Response 200 (success)**:
```json
{
  "followed": true,
  "token": "<nouveau_csrf_token>"
}
```

- `followed`: état résultant (true = l'utilisateur suit maintenant, false = l'utilisateur ne suit plus)
- `token`: nouveau token CSRF (name = `follow_contributor_{id}`) pour permettre le prochain toggle

**Response 403** : CSRF invalide → `{"error": "Invalid CSRF token"}`

**Response 404** : Contributor introuvable

**Response 401** : Non connecté (ne se produit pas en pratique — visiteur → modal avant appel)

---

## POST /follow/collection/{id}

**Description**: Toggle follow/unfollow pour une Collection. Idempotent.

**Auth**: `ROLE_USER` requis

**Route name**: `follow_collection_toggle`

**Request**:
```
POST /follow/collection/{id}
Content-Type: application/x-www-form-urlencoded

_token={csrf_token}
```

- `{id}` : UUID de la Collection (string)
- `_token` : CSRF token, name = `follow_collection_{id}`

**Response 200 (success)**:
```json
{
  "followed": true,
  "token": "<nouveau_csrf_token>"
}
```

**Response 403** : CSRF invalide

**Response 404** : Collection introuvable

---

## GET /collections

**Description**: Page liste des Collections avec filtres et toggle "Uniquement ceux que je suis".

**Auth**: Publique (toggle masqué pour guests)

**Route name**: `app_collections`

**Query params**:

| Param | Type | Default | Notes |
|-------|------|---------|-------|
| `followed` | `true` | (absent) | Activer le filtre "Uniquement ceux que je suis" (ROLE_USER seulement) |
| `genre` | string | (absent) | Filtre par genre (`GenreCollection` enum value) |
| `statut` | string | (absent) | Filtre par statut (`StatutCollection` enum value) |
| `page` | int | `1` | Pagination |

**Response**: HTML (Twig render)

---

## Stimulus `follow_controller.js` — interface

**Identifier**: `follow`

**Values**:
```js
static values = {
  url: String,        // endpoint URL (ex. /follow/contributor/xxx)
  token: String,      // CSRF token courant
  followed: Boolean,  // état initial
  authenticated: Boolean, // l'user est-il connecté ?
}
```

**Targets**:
```js
static targets = ['button', 'icon', 'label']
```

**Events émis**:
- `follow:open-login-modal` (window) — si `authenticatedValue === false` au clic
- `follow:error` (element) — en cas d'erreur serveur, pour le toast

**Actions**:
- `toggle()` — appelé au clic sur le bouton

**Usage Twig (exemple Contributor)**:
```twig
<div data-controller="follow"
     data-follow-url-value="{{ path('follow_contributor_toggle', {id: c.id}) }}"
     data-follow-token-value="{{ csrf_token('follow_contributor_' ~ c.id) }}"
     data-follow-followed-value="{{ isFollowed ? 'true' : 'false' }}"
     data-follow-authenticated-value="{{ is_granted('ROLE_USER') ? 'true' : 'false' }}">
  <button type="button"
          data-follow-target="button"
          aria-pressed="{{ isFollowed ? 'true' : 'false' }}"
          aria-label="{{ isFollowed ? 'Ne plus suivre' : 'Suivre' }} {{ c.firstName }} {{ c.lastName }}">
    <svg data-follow-target="icon">...</svg>
    <span data-follow-target="label">{{ isFollowed ? 'Suivi' : 'Suivre' }}</span>
  </button>
</div>
```
