# Upgrading to v3.0 (AdminLTE 4 / Bootstrap 5.3)

v3.0 moves the user interface from AdminLTE 3 / Bootstrap 4.6 to **AdminLTE 4 / Bootstrap 5.3**.
Business features, data and URLs are unchanged, but the HTML of every screen has changed.

If you run an unmodified copy of WEM, follow [Upgrade steps](#upgrade-steps) and you are done.
If you customised views, scripts or `config/adminlte.php`, read
[What changed](#what-changed) first.

## Before you start

- PHP 8.2+ and Laravel 12 (already required by v2.x).
- Node.js and npm on the server, to rebuild the front-end bundle.
- A fresh backup: `php artisan backup:run`.

## Upgrade steps

```bash
php artisan down
php artisan backup:run
git pull && composer install --no-dev -o && npm ci && npm run build
php artisan migrate --pretend    # one additive migration: users.ui_preset, users.ui_primary
php artisan migrate
php artisan optimize:clear && php artisan config:cache && php artisan view:cache
# never run php artisan route:cache (it breaks the localised routes)
php artisan queue:restart
php artisan up
```

Then ask users to reload the application once with **Ctrl+F5**: browsers may keep the
AdminLTE 3 stylesheets in cache.

If you deploy by copying files instead of using git, delete these folders first, as they are
no longer shipped: `public/vendor/adminlte`, `public/vendor/bootstrap`, `public/vendor/popper`,
`public/vendor/overlayScrollbars`, `public/vendor/chart.js`, `public/vendor/flag-icon-css`,
`public/vendor/select2-bootstrap4-theme`, `public/vendor/summernote`. On Windows,
`overlayScrollbars` (old) and `overlayscrollbars` (new) are the same folder: remove the old one
before copying the new one.

### Rolling back

Restore the backup taken above and check out your previous v2.x tag. The migration is
reversible on its own:

```bash
php artisan migrate:rollback --path=database/migrations/2026_10_13_000001_add_ui_appearance_to_users_table.php
```

## What changed

### Packages and assets

| | v2.x | v3.0 |
|---|---|---|
| `jeroennoten/laravel-adminlte` | 3.x | 4.x |
| AdminLTE / Bootstrap | 3.2 / 4.6 | 4 / 5.3 |
| Bootstrap on a page | twice (AdminLTE's 4.6 + Vite's 5.3) | once (AdminLTE's 5.3) |
| jQuery | loaded by AdminLTE and by the Vite bundle | loaded once, by the `JQuery` plugin of `config/adminlte.php`, for Select2 only |
| Icons | Font Awesome | Font Awesome (unchanged); Bootstrap Icons for the AdminLTE views |
| Colour palette | AdminLTE 3 (`purple`, `lightblue`…) | AdminLTE 4 (`violet`, `sky`…) |

- `resources/sass/app.scss` no longer compiles Bootstrap: AdminLTE pages get it from AdminLTE.
  Pages outside the AdminLTE layout (public quote/order/delivery links, customer portal,
  setup wizard, site visit, attendance) load the new `resources/sass/standalone.scss`.
- The Vite bundle no longer imports `bootstrap` nor `jquery`. Use `window.bootstrap`
  (provided by AdminLTE) in your scripts, and do not bundle a second copy of Bootstrap: it
  would register its event listeners twice (dropdowns opening and closing at once).
- The open-source logo moved from `public/vendor/adminlte/dist/img/` to `public/img/`.

### Customised views and components

Every Blade view and React component of WEM has been converted. If you added or modified
your own, convert them too:

```bash
node tools/bs5-codemod.cjs           # dry run: what would change, per rule
node tools/bs5-codemod.cjs --write   # apply
```

The script renames whole class names only (`ml-2` → `ms-2`, `float-right` → `float-end`,
`badge-success` → `text-bg-success`, `form-group` → `mb-3`, `custom-select` → `form-select`,
`btn-default` → `btn-outline-secondary`, `bg-light` → `bg-body-tertiary`, AdminLTE 3 colour
names…). It leaves PDF and e-mail views alone (they have their own CSS), as well as `<style>`
blocks and stylesheets. Review the diff, then check the following by hand:

| AdminLTE 3 / Bootstrap 4 | AdminLTE 4 / Bootstrap 5 |
|---|---|
| `data-toggle`, `data-target`, `data-dismiss` | `data-bs-toggle`, `data-bs-target`, `data-bs-dismiss` |
| `data-widget="pushmenu"`, `data-card-widget="collapse"` | `data-lte-toggle="sidebar"`, `data-lte-toggle="card-collapse"` |
| `.main-header`, `.main-sidebar`, `.content-wrapper`, `.main-footer`, `.nav-sidebar` | `.app-header`, `.app-sidebar`, `.app-main`, `.app-footer`, `.sidebar-menu` |
| `body.dark-mode` | `[data-bs-theme="dark"]` (on `<html>`) |
| `<button class="close">&times;</button>` | `<button class="btn-close"></button>` |
| `.input-group-prepend` / `.input-group-append` wrappers | removed: `.input-group-text` and buttons go straight into `.input-group` |
| `<div class="custom-file">…</div>` | `<input type="file" class="form-control">` |
| `<select class="form-control">` | `<select class="form-select">` |
| `btn bg-teal` | `btn-teal` |
| `small-box` with `<div class="icon"><i …></i></div>` | `<i class="small-box-icon …"></i>` |
| `.card` has a 1 rem bottom margin | no default margin: add `mb-3` (`<x-adminlte-card>` adds `mb-4` itself) |
| `$('#modal').modal('show')` | `bootstrap.Modal.getOrCreateInstance(el).show()` |

`print/custom/*` views (customised PDF layouts) are not concerned: PDFs keep their own CSS.

If you overrode AdminLTE views in `resources/views/vendor/adminlte`, compare them with the v4
originals (`vendor/jeroennoten/laravel-adminlte/resources/views`) and reapply your changes on
top of those: the v3 markup no longer matches the layout.

### `config/adminlte.php`

The file was rebuilt from the v4 package configuration, keeping the WEM menu and plugins. If
you customised it, port your changes onto the new file rather than keeping the old one
(several keys were renamed or removed by the package: `layout_dark_mode`, `sidebar_mini`,
`enabled_laravel_mix`, sidebar skin classes, logo paths). See the package's
[upgrade guide](https://jeroennoten.github.io/Laravel-AdminLTE/sections/overview/upgrading_from_3x.html).

### Appearance

Each user can now pick a theme (sidebar and top bar colours) and a primary colour in
**Profile → Appearance**. The default stays the current look (the Nest2Prod theme, as long as
`WEBERP_COLOR_THEME=nest2prod`). Light / dark / pro mode is still switched from the top bar.
