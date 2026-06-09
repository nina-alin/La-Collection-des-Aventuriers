# HTTP Routes Contract

## Routes modifiées

### `POST /moderation/suggestion/{id}/approve`
**Name**: `moderation_approve_suggestion`
**Auth**: `ROLE_MODERATOR`
**Body** (form-data): `_csrf_token`
**Behavior change**: retourne JSON si `X-Requested-With: XMLHttpRequest` (Stimulus fetch)

**Response JSON (200)**:
```json
{
  "success": true,
  "nextSuggestionId": "018f1234-..." // null si file vide
}
```

**Response JSON (error)**:
```json
{
  "success": false,
  "message": "Suggestion introuvable."
}
```

**Response (non-XHR)**: redirect vers `moderation_dashboard` (comportement actuel conservé)

---

### `POST /moderation/suggestion/{id}/refuse`
**Name**: `moderation_refuse_suggestion`
**Auth**: `ROLE_MODERATOR`
**Body** (form-data): `_csrf_token`, `reason` (string requis)
**Behavior change**: retourne JSON si XHR + persiste `SuggestionRefusal` avec `reason`

**Response JSON (200)**:
```json
{
  "success": true,
  "nextSuggestionId": "018f1234-..." // null si file vide
}
```

**Response JSON (422 — reason manquant)**:
```json
{
  "success": false,
  "message": "Le motif de refus est requis."
}
```

---

## Nouvelles routes

### `GET /moderation/suggestion/{id}/diff-partial`
**Name**: `moderation_suggestion_diff_partial`
**Auth**: `ROLE_MODERATOR`
**Purpose**: Retourne le HTML rendu du comparateur pour une suggestion donnée

**Response (200)**: HTML fragment (rendu de `moderation/_diff_panel.html.twig`)

**Response (404)**: `{"error": "Suggestion introuvable"}` si id invalide

**Données Twig injectées**:
- `suggestion` — objet `Suggestion`
- `diffResult` — objet `DiffResult`
- `csrfToken` — `csrf_token('moderate_' ~ suggestion.id)`

---

### `GET /moderation/entities`
**Name**: `moderation_entities_list`
**Auth**: `ROLE_MODERATOR`
**Query params**: `search` (string, optionnel), `type` (string enum : `BOOK|AUTHOR|ILLUSTRATOR|TRADUCTOR|EDITOR|COLLECTION`, optionnel)
**Purpose**: Retourne le `<tbody>` du tableau Gestion globale filtré

**Response (200)**: HTML fragment (rendu de `moderation/_entities_table.html.twig`)

**Données Twig injectées**:
- `entities` — `array<array{id, name, type, status, updatedAt}>` (max 100 lignes)
- `search` — terme de recherche actuel
- `type` — filtre type actuel

---

## Routes inchangées

| Name | Path | Notes |
|------|------|-------|
| `moderation_dashboard` | `GET /moderation` | Contrôleur étendu pour injecter `firstSuggestion` + `DiffResult` + `pendingSuggestions` |
| `moderation_approve_work_entry` | `POST /moderation/work-entry/{id}/approve` | Inchangé |
| `moderation_reject_work_entry` | `POST /moderation/work-entry/{id}/reject` | Inchangé |
| `moderation_approve_correction` | `POST /moderation/correction-proposal/{id}/approve` | Inchangé |
| `moderation_reject_correction` | `POST /moderation/correction-proposal/{id}/reject` | Inchangé |
