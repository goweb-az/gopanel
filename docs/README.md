# Gopanel Documentation

This directory contains separate documentation files for the main Gopanel modules and workflows. The root `README.md` stays focused on the project overview and quick installation, while detailed documentation lives here.

## Documentation Sections

- [Installation](installation.md)  
  Project setup, `.env` configuration, migrations, and first seed flow.

- [Mock Seeders and `mock:seed`](mock-seeders.md)  
  Optional demo/test data, the interactive seeder menu, `$mockName`, and how to create new mock seeders.

- [Analytics Dashboard](analytics.md)  
  Analytics flow, middleware, event/listener, dashboard widgets, UTM tracking, and ad platforms.

- [Analytics and Bots Notes](analytics-and-bots-readme.md)  
  Extended notes about analytics and bot tracking.

- [GeoIP and `geoip:restore`](geoip.md)  
  MaxMind GeoLite2 files, restoring from backups, `geoip:restore --force`, and expected paths.

- [SEO, Meta, Redirects, LLMs.txt](seo.md)  
  Meta resolution, SEO snippets, redirects, and LLMs.txt support.

- [Translations](translations.md)  
  The `translations` and `field_translations` tables, the `Translation` trait, and translation seeders.

- [Menus and Dynamic Routes](menus-and-routes.md)  
  Menu structure, multilingual route registration, and dynamic page resolution by slug.

- [Sitemap and RSS](sitemap-rss.md)  
  XML sitemap, RSS endpoints, and how to add new models.

- [Updater System](updater.md)  
  `gopanel_updates.json`, GitHub update checks, action types, and backup behavior.

- [Development Notes](development.md)  
  Traits, permissions, file structure, and short development references.

## Recommended Reading Order

1. [Installation](installation.md)
2. [Mock Seeders and `mock:seed`](mock-seeders.md)
3. [GeoIP and `geoip:restore`](geoip.md)
4. [Analytics Dashboard](analytics.md)
5. [SEO, Meta, Redirects, LLMs.txt](seo.md)
6. [Menus and Dynamic Routes](menus-and-routes.md)

## Adding New Documentation

When adding documentation for a new module:

1. Create a separate `.md` file under `docs/`.
2. Add the link to the “Documentation Sections” list in this file.
3. If needed, also add the link to the documentation list in the root `README.md`.

