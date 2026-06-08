# HTTP Route Contract: Page Mentions Légales

## Route: GET /mentions-legales

| Attribute | Value |
|---|---|
| **Method** | `GET` |
| **Path** | `/mentions-legales` |
| **Route name** | `app_mentions_legales` |
| **Controller** | `App\Controller\LegalController::mentionsLegales` |
| **Authentication** | Public (no login required) |
| **Template** | `legal/mentions-legales.html.twig` |

### Response

| Condition | Status | Body |
|---|---|---|
| Normal request | `200 OK` | Rendered Twig template |

### Template variables

| Variable | Type | Source |
|---|---|---|
| `lastUpdated` | `string` | `app.legal.last_updated` Symfony parameter |

### Footer link

The footer component (`templates/components/Layout/Footer.html.twig`) MUST contain:

```twig
<a href="{{ path('app_mentions_legales') }}">Mentions légales</a>
```

replacing the current `href="#"` placeholder.
