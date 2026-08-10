<p align="center">
  <img src="https://proweb.az/assets/images/logo/Proweb_Logo.svg" alt="Gopanel Logo" width="320">
</p>

<p align="center">
  <strong>Version:</strong> 1.0.0
</p>

# Gopanel

Gopanel is a Laravel 10 based admin panel starter with modular site, SEO, analytics, translation, role/permission, and update-management features.

## Quick Install

Create a project:

```bash
composer create-project goweb/gopanel
```

Or with a custom folder:

```bash
composer create-project goweb/gopanel your-project-name dev-master
```

Configure `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gopanel
DB_USERNAME=root
DB_PASSWORD=
```

Run setup:

```bash
php artisan key:generate
php artisan migrate --seed
```

## Useful Commands

```bash
php artisan mock:seed
php artisan mock:seed --list
php artisan geoip:restore
php artisan geoip:restore --force
php artisan config:clear
php artisan cache:clear
```

## Documentation

Start at [docs/README.md](docs/README.md) — it indexes everything below.

- [Shared Layer (Support / Services / Queries)](docs/shared-layer.md) — the reusable layer; read before writing a new helper
- [Database Structure](docs/database-structure.md)
- [Installation](docs/installation.md)
- [Mock Seeders and `mock:seed`](docs/mock-seeders.md)
- [Analytics Dashboard](docs/analytics.md)
- [Analytics and Bots Notes](docs/analytics-and-bots-readme.md)
- [GeoIP Databases and `geoip:restore`](docs/geoip.md)
- [SEO, Meta, Redirects, LLMs.txt](docs/seo.md)
- [Translations](docs/translations.md)
- [Menus and Dynamic Routes](docs/menus-and-routes.md)
- [Sitemap and RSS](docs/sitemap-rss.md)
- [Updater System](docs/updater.md)
- [Development Notes](docs/development.md)

### Rules

- [`.claude/rules/`](.claude/rules/README.md) — binding house rules, read before every code change
- [`docs/rules/`](docs/rules/README.md) — implementation specs for building new modules
  (notifications, dashboard, user management, category tree, API, deployment)
- [`docs/tasks/`](docs/tasks/README.md) — archive of completed implementation tasks

## Included Packages

- [Spatie Laravel Permission](https://github.com/spatie/laravel-permission)
- [Spatie Laravel Activity Log](https://github.com/spatie/laravel-activitylog)
- [Opcodes Laravel Log Viewer](https://github.com/opcodesio/log-viewer)

## Main Structure

```text
app/Contracts               -> interfaces for infrastructure adapters
app/DTOs                    -> typed data transfer objects
app/Datatable               -> jQuery datatable classes
app/Enums                   -> code-owned fixed value sets
app/Helpers                 -> shared helpers
app/Http/Resources          -> API response shaping
app/Jobs                    -> queued work
app/Observers               -> model event listeners
app/Policies                -> authorization decisions
app/Queries                 -> all non-trivial SELECT queries (Gopanel/Site/Api)
app/Repositories            -> insert / update / delete only
app/Rules                   -> custom validation rules
app/Services                -> domain services, transactions, external APIs
app/Support                 -> pure stateless primitives (cache keys, dates, URLs)
app/Traits                  -> model helper traits
config/custom               -> project config (activity log, cache, mail, sms, export)
config/gopanel              -> panel config (sidebar, menu, permission list)
resources/views/gopanel     -> admin panel views
resources/views/site        -> site views
resources/views/emails      -> email templates
routes/gopanel.php          -> admin routes
routes/web.php              -> site routes
database/seeders/mock       -> optional demo/test seeders
docs                        -> feature documentation
.claude/rules               -> binding house rules for humans and AI agents
```

Full catalogue of the reusable layer with usage examples:
[docs/shared-layer.md](docs/shared-layer.md).

## License

Copyright (c) 2025 Oruc Seyidov. All rights reserved.

This software is proprietary and confidential. Unauthorized copying of this file, via any medium is strictly prohibited.
