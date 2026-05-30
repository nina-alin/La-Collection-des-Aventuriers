# Research: Système de Notation et Commentaires

**Branch**: `009-book-review-rating` | **Phase**: 0

---

## 1. symfony/ux-turbo — Installation & Turbo Stream Pattern

**Decision**: Install `symfony/ux-turbo` + `@hotwired/turbo`. No alternative (plain AJAX) considered.

**Rationale**: Spec requires Turbo Stream (4-target update after POST) and Turbo Frame (filtered list without full reload). Stimulus bundle already present.

**Install**:
```bash
composer require symfony/ux-turbo
npm install --force   # resolves peer-dep conflict on @hotwired/turbo
```

**`assets/app.js` addition**:
```js
import '@symfony/ux-turbo';
```

**Turbo Stream controller pattern** (returns 4-target stream after POST):
```php
use Symfony\UX\Turbo\TurboBundle;

if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
    $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
    return $this->render('livre/_review_stream.html.twig', [...]);
}
return $this->redirectToRoute('app_book_show', ['slug' => $book->getSlug()]);
```

**`_review_stream.html.twig`** (4 targets):
```twig
<turbo-stream action="replace" target="stats-header">
    <template>{% include 'livre/_stats_header.html.twig' %}</template>
</turbo-stream>
<turbo-stream action="replace" target="histogram">
    <template>{% include 'livre/_histogram.html.twig' %}</template>
</turbo-stream>
<turbo-stream action="replace" target="reviews-list">
    <template>{% include 'livre/_reviews_list.html.twig' %}</template>
</turbo-stream>
<turbo-stream action="replace" target="review-form">
    <template>{% include 'livre/_review_form.html.twig' %}</template>
</turbo-stream>
```

**Turbo Frame for filtered list**:
```twig
<turbo-frame id="reviews-list" src="{{ path('app_book_reviews', {slug: book.slug}) }}">
    {% include 'livre/_reviews_list.html.twig' %}
</turbo-frame>
```
Filter link: `<a href="..." data-turbo-frame="reviews-list">`.

**Gotchas**:
- `setRequestFormat` must be called **before** `render()`.
- Frame response must include matching `<turbo-frame id="reviews-list">` or Turbo silently does nothing.
- CSRF tokens work normally — Turbo submits forms as standard HTML form submissions.

---

## 2. Pagination — Doctrine Native Paginator

**Decision**: Use `Doctrine\ORM\Tools\Pagination\Paginator` directly — no extra bundle.

**Rationale**: Zero new dependencies. Sufficient for 10-per-page simple ordered list. `knplabs/knp-paginator-bundle` has slow Symfony 7 adoption; `pagerfanta` is overkill for single-adapter use.

**Repository method**:
```php
use Doctrine\ORM\Tools\Pagination\Paginator;

public function findPaginatedByBook(Book $book, string $filter, int $page, int $perPage = 10): Paginator
{
    $qb = $this->createQueryBuilder('r')
        ->where('r.book = :book')
        ->setParameter('book', $book)
        ->orderBy('r.updatedAt', 'DESC')
        ->setFirstResult(($page - 1) * $perPage)
        ->setMaxResults($perPage);

    if ($filter === 'avec_commentaire') {
        $qb->andWhere('r.comment IS NOT NULL');
    }

    return new Paginator($qb, fetchJoinCollection: false);
}
```

`count($paginator)` = total results. `ceil(count / perPage)` = total pages.

**Pagination links in Turbo Frame** (preserve `filter` param, override `page`):
```twig
{% set params = app.request.query.all|merge({page: p, slug: book.slug}) %}
<a href="{{ path('app_book_reviews', params) }}" data-turbo-frame="reviews-list">{{ p }}</a>
```

**Hide controls when total ≤ perPage**: `{% if totalPages > 1 %}`.

---

## 3. User Initials Derivation

**Decision**: Parse `User.displayName` — split on first space, take first char of each part, uppercase.

**Rationale**: `User` entity has no separate firstName/lastName fields. `displayName` is populated from Google OAuth (full name). `pseudo` is a login handle, not a display name for initials.

```php
public function getUserInitials(?User $user): ?string
{
    if ($user === null || $user->getDisplayName() === null) {
        return null; // → render placeholder image
    }
    $parts = explode(' ', trim($user->getDisplayName()), 2);
    if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
        return null; // → render placeholder image
    }
    return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
}
```

Returns `null` → template renders `<img src="/images/avatar-placeholder.svg">`.

---

## 4. Relative Dates — Browser Timezone

**Decision**: Stimulus controller using `Intl.RelativeTimeFormat`.

**Rationale**: Server can't know browser timezone at render time. Dates stored as UTC in DB. JS reads `data-timestamp` attribute (ISO 8601), computes relative string in browser locale.

```js
// assets/controllers/relative-date_controller.js
import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    static values = { timestamp: String };
    connect() {
        const date = new Date(this.timestampValue);
        const diff = (date - Date.now()) / 1000;
        const fmt = new Intl.RelativeTimeFormat(document.documentElement.lang || 'fr', { numeric: 'auto' });
        // pick unit based on diff magnitude
        this.element.textContent = this._format(fmt, diff);
    }
    _format(fmt, diffSec) {
        const abs = Math.abs(diffSec);
        if (abs < 60)   return fmt.format(Math.round(diffSec), 'second');
        if (abs < 3600) return fmt.format(Math.round(diffSec / 60), 'minute');
        if (abs < 86400) return fmt.format(Math.round(diffSec / 3600), 'hour');
        if (abs < 2592000) return fmt.format(Math.round(diffSec / 86400), 'day');
        if (abs < 31536000) return fmt.format(Math.round(diffSec / 2592000), 'month');
        return fmt.format(Math.round(diffSec / 31536000), 'year');
    }
}
```

Twig usage:
```twig
<span data-controller="relative-date"
      data-relative-date-timestamp-value="{{ review.updatedAt|date('c') }}">
    {{ review.updatedAt|date('d/m/Y') }}  {# fallback for no-JS #}
</span>
```

---

## 5. Upsert Strategy — Unique Constraint Race Condition

**Decision**: Find-or-create in `ReviewService`, catch `UniqueConstraintViolationException` for concurrent race conditions.

**Rationale**: Doctrine doesn't expose PostgreSQL `ON CONFLICT DO UPDATE` natively. Service-layer find-or-create handles 99% of cases; exception catch handles the remaining race condition returning 409.

```php
try {
    $review = $this->reviewRepository->findOneBy(['user' => $user, 'book' => $book])
        ?? new Review();
    // set fields...
    $this->entityManager->persist($review);
    $this->entityManager->flush();
} catch (UniqueConstraintViolationException $e) {
    // concurrent duplicate — return 409
}
```

---

## 6. Infrastructure Impact

**New dependency**: `symfony/ux-turbo` — PHP only, no Platform.sh services change needed. No `.platform.app.yaml`, `.platform/routes.yaml`, `.platform/services.yaml` changes required (no new managed service).
