# Contract: ProfileMenu Twig Component

## Class

`App\Twig\Components\Layout\ProfileMenu` — `#[AsTwigComponent]`

## Constructor injection

```php
public function __construct(
    private readonly ProfileMenuService $menuService,
    private readonly Security $security,
) {}
```

## Public props (set before mount)

None — all data is derived internally in `mount()`.

## mount() behavior

```php
public function mount(): void
{
    /** @var User $user */
    $user = $this->security->getUser();
    $this->menuData = $this->menuService->getMenuData($user);
}
```

## Exposed to template

| Variable | Type | Description |
|---|---|---|
| `menuData` | `ProfileMenuDto` | Full aggregated data for the menu |

## Template

`templates/components/Layout/ProfileMenu.html.twig`

## Usage in Navbar.html.twig

```twig
{% if is_granted('IS_AUTHENTICATED_REMEMBERED') %}
    <twig:Layout:ProfileMenu />
{% endif %}
```

## Root element structure

```html
<div class="menu-anchor"
     data-controller="profile-menu"
     data-profile-menu-open-value="false">

  <!-- Trigger button -->
  <button type="button"
          class="sh-user"
          id="user-trigger"
          aria-haspopup="menu"
          aria-expanded="false"
          aria-controls="user-menu"
          aria-label="Menu utilisateur"
          data-profile-menu-target="trigger"
          data-action="click->profile-menu#toggle">
    <!-- avatar + pseudo -->
  </button>

  <!-- Backdrop (mobile) -->
  <div class="menu-backdrop"
       data-profile-menu-target="backdrop"
       data-action="click->profile-menu#close"></div>

  <!-- Menu card -->
  <div class="menu-card user-card"
       id="user-menu"
       data-anchor="user"
       role="menu"
       aria-label="Compte utilisateur"
       data-profile-menu-target="card">

    <!-- header / sections / logout — see quickstart.md for full markup -->

  </div>
</div>
```
