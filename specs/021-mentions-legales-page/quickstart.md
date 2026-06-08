# Quickstart: Page Mentions Légales

## Updating the Last-Updated Date

Edit `config/services.yaml` and update the `app.legal.last_updated` parameter:

```yaml
parameters:
    app.legal.last_updated: "3 juin 2026"
```

The format is a pre-formatted French string: `d MMMM Y` (e.g., `"3 juin 2026"`).
To leave the field blank, set it to an empty string or remove it entirely.

No template changes required — only `config/services.yaml` is edited (SC-004).

## Accessing the Page

The page is accessible at `/mentions-legales`. It is publicly accessible without authentication.

## Adding a New Section

1. Add an anchor to the section: `<section class="doc-section" id="my-anchor">`
2. Add a `<h2>` with `<span class="num">NN</span>` prefix
3. Add the corresponding TOC entry in `<aside class="toc">` inside the template
4. The Bootstrap Scrollspy will detect it automatically (no JS changes needed)

## Running Tests

```bash
php bin/phpunit tests/Functional/Controller/LegalControllerTest.php
```
