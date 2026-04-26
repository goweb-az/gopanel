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

## Included Packages

- [Spatie Laravel Permission](https://github.com/spatie/laravel-permission)
- [Spatie Laravel Activity Log](https://github.com/spatie/laravel-activitylog)
- [Opcodes Laravel Log Viewer](https://github.com/opcodesio/log-viewer)

## Main Structure

```text
app/Datatable               -> jQuery datatable classes
app/Traits                  -> model helper traits
app/Helpers                 -> shared helpers
app/Services                -> domain services
resources/views/gopanel     -> admin panel views
resources/views/site        -> site views
routes/gopanel.php          -> admin routes
routes/web.php              -> site routes
database/seeders/mock       -> optional demo/test seeders
docs                        -> feature documentation
```

## License

Copyright (c) 2025 Oruc Seyidov. All rights reserved.

This software is proprietary and confidential. Unauthorized copying of this file, via any medium is strictly prohibited.
