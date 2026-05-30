# API Contracts — 011 Page Contributeur Suggestions

**Date**: 2026-05-30 | **Branch**: `011-contributor-page`

All endpoints require `ROLE_USER` authentication (Symfony Security session cookie).
All mutating endpoints require CSRF token in `X-CSRF-Token` header or form field `_token`.

---

## 1. Page — Suggestion Portal

```
GET /mes-suggestions
Auth: ROLE_USER (#[IsGranted])
Response: HTML (200) — renders suggestion/index.html.twig with WizardComponent + side panel
Redirect: → /connexion if unauthenticated
```

---

## 2. Polling — Suggestion Status Feed

```
GET /api/suggestions/feed
Auth: ROLE_USER
Query params: none (returns current user's data from security context)
Response 200 (application/json):
{
  "suggestions": [
    {
      "id": "uuid-v7",
      "entityType": "BOOK",           // SuggestionEntityType value
      "mode": "NEW_ENTRY",            // SuggestionMode value
      "entityName": "string",         // extracted from formData.title or equivalent
      "status": "PENDING",            // SuggestionStatus value
      "submittedAt": "ISO-8601",      // relative display computed client-side
      "refusal": null | {
        "moderatorName": "string",
        "reason": "string",
        "actions": ["VOIR_FICHE"]     // SuggestionRefusalAction[] — only known keys
      }
    }
  ],
  "counts": {
    "total": 12,
    "pending": 3,
    "validated": 8,
    "refused": 1
  },
  "pendingCount": 3                   // for quota check + mobile badge
}
```

**Notes**:
- Returns max 50 most recent suggestions ordered by `submittedAt DESC` (hard cap, FR-021)
- Unknown `SuggestionRefusalAction` keys omitted from `actions` array (FR-045)
- `entityName` extracted server-side from `formData` JSON — no polymorphic join needed

---

## 3. Autocomplete — Entity Search

```
GET /api/suggestions/autocomplete/{type}
Auth: ROLE_USER
Path params:
  type: book | author | illustrator | traductor | editor | collection
Query params:
  q: string (min 2 chars)
Response 200 (application/json):
{
  "results": [
    { "id": "uuid-or-int", "label": "string" }
  ]
}
Response 400: { "error": "Invalid type or query too short" }
```

**Notes**:
- Max 10 results per request
- Case-insensitive LIKE search on primary name field
- Returns `[]` results (not 404) when no match found
- Client-side: 3-second timeout → fallback to free text (FR-031)

---

## 4. Entity On-the-Fly Creation

```
POST /api/suggestions/entities/{type}
Auth: ROLE_USER
CSRF: X-CSRF-Token header required
Path params:
  type: author | illustrator | traductor | editor | collection  (NOT book — books require full wizard)
Content-Type: application/json
Body: { "name": "string" }
Response 201 (application/json):
{
  "id": "uuid",
  "label": "string"
}
Response 400: { "error": "Name cannot be blank" }
Response 409: { "error": "Entity already exists", "existing": { "id": "uuid", "label": "string" } }
```

**Notes**:
- Creates minimal entity record (name only; other fields left null/default)
- For `Contributor` types (author/illustrator/traductor): sets `firstName` from first word, `lastName` from remainder; generates slug via `ContributorSlugger`
- For `editor` type: creates `Editor` entity
- For `collection` type: creates `Collection` entity
- Rate-limited: max 10 creations per user per hour (Symfony Rate Limiter)

---

## 5. Suggestion Submission (LiveComponent Action)

LiveComponent actions are not traditional REST endpoints — they go through Symfony UX LiveComponent's internal routing. Documented here for contract clarity.

```
LiveComponent Action: submitSuggestion()
Triggered by: "Soumettre à la modération" button click in WizardComponent
Server-side validation:
  - Pending count < 20 (FR-018, FR-039)
  - Form data valid (all required fields, no blocking errors)
  - Source entity still exists if mode=CORRECTION (FR-056)
  - Cover image processed if provided
On success:
  - Persists Suggestion entity (status=PENDING)
  - Resets WizardComponent state (step=1, formData=[], mode=null)
  - Returns component re-render with success flash + updated panel data
On error:
  - Returns component re-render with error state; redirects to first errored step (FR-057)
```

---

## 6. Cover Image Upload (LiveComponent Action)

```
LiveComponent Action: uploadCover(UploadedFile $file)
Triggered by: Stimulus upload controller dispatching LiveComponent event after client-side validation
Server-side:
  - Validates MIME type (jpeg/png/webp) + size ≤ 4MB (FR-014)
  - Calls CoverImageProcessor::process() → saves to public/uploads/covers/tmp/{uuid}.jpg
  - Sets $this->coverImageTempPath on component
On error: sets $this->errors['cover'] with message (FR-054)
```

---

## 7. Uniqueness Check — Title/Subtitle

```
GET /api/suggestions/check-unique
Auth: ROLE_USER
Query params:
  field: title | subtitle
  value: string
  entityType: book (only BOOK supported currently)
Response 200:
{
  "unique": true | false,
  "existing": null | { "id": "int", "label": "string", "url": "/livres/slug" }
}
```

**Notes**:
- Case-insensitive exact match against `book.title` / `book.original_title`
- Result is informational only — non-blocking warning (FR-034, FR-008)
- If endpoint unavailable: client shows neutral state + help text (FR-055)
