# Gopanel Documentation

All Gopanel documentation lives here. The root `README.md` stays focused on the
project overview and quick installation.

## Where to look

| Folder | Contains | Read it |
|---|---|---|
| [`.claude/rules/`](../.claude/rules/README.md) | Short, binding house rules (layers, Blade, permissions, tests) | **Before every code change** |
| `docs/*.md` | Feature/module reference — how each existing subsystem works | When touching that subsystem |
| [`docs/rules/`](rules/README.md) | Long implementation specs for building a **new** big module | Only when building that module |
| [`docs/tasks/`](tasks/README.md) | Archive of completed implementation tasks | For history / context |

`.claude/CLAUDE.md` is the project brief and points at everything above.

---

## Getting started

- [Installation](installation.md)  
  Requirements, `composer create-project`, `.env`, key generation, migrations, first seed.

- [Mock Seeders and `mock:seed`](mock-seeders.md)  
  Optional demo/test data, the interactive seeder menu, `$mockName`, and how to add new mock seeders.

- [Development Notes](development.md)  
  Traits, permissions, file structure, and short day-to-day references.

- [AI Development Guide](ai-guide.md) *(AZ)*  
  The long practical guide: folder layout, `BaseModel` and traits, controller/view patterns,
  ready-made components and helpers, JS flows, datatables, permissions and sidebar,
  mock seeders, route conventions, activity log, tests, and a step-by-step new-CRUD checklist.
  If `.claude/rules/` and this file disagree, **the rules win** — they are newer and binding.

## Architecture

- [Shared Layer (Support / Services / Queries)](shared-layer.md)  
  The project-agnostic reusable layer: cache (`CacheService` + `CacheKey`), mail, SMS,
  bulk actions, export skeleton, queue monitor, date helpers, CDN URLs, panel stat cards,
  and the `config/custom/*` files that drive them.  
  **Read this before writing a new helper — it probably already exists.**

- [Database Structure](database-structure.md)  
  Migrations, models, relationships, traits, polymorphic structures (translations / meta /
  activity log / menu), cache invalidation, and an ER summary.

## Site features

- [Menus and Dynamic Routes](menus-and-routes.md)  
  Menu structure, multilingual route registration, dynamic page resolution by slug.

- [Translations](translations.md)  
  The `translations` and `field_translations` tables, the `Translation` trait, translation seeders.

- [SEO, Meta, Redirects, LLMs.txt](seo.md)  
  Meta resolution order, SEO snippets, redirects, `llms.txt` / `ai.txt` support.

- [Sitemap and RSS](sitemap-rss.md)  
  XML sitemap and RSS endpoints, and how to add a new model to them.

## Analytics

- [Analytics Dashboard](analytics.md)  
  Tracking flow (middleware → event → listener → service → repository), dashboard widgets,
  UTM tracking, ad platforms.

- [Analytics and Bot Blocking Notes](analytics-and-bots-readme.md)  
  Extended notes: GeoIP enrichment, user-agent parsing, bot filtering.

- [GeoIP and `geoip:restore`](geoip.md)  
  MaxMind GeoLite2 `.mmdb` files, expected paths, restoring from backups, `--force`.

## Operations

- [Updater System](updater.md)  
  `gopanel_updates.json`, GitHub update checks, action types, backup behavior.

- [Domains, CDN & Deployment](rules/domains-cdn-gopanel.md)  
  Domain plan, nginx configuration, `CDN_URL` ↔ `filesystems.public.url` alignment,
  panel host restriction, post-deploy checklist.

---

## Recommended reading order

1. [Installation](installation.md)
2. [`.claude/rules/01-umumi.md`](../.claude/rules/01-umumi.md) — the binding rules
3. [Shared Layer](shared-layer.md) — what already exists
4. [AI Development Guide](ai-guide.md) — the practical patterns
5. [Database Structure](database-structure.md)
6. [Menus and Dynamic Routes](menus-and-routes.md)
7. [Translations](translations.md)
8. [SEO](seo.md) and [Analytics](analytics.md)

## Adding new documentation

1. **A new subsystem in this codebase** → a `.md` file directly under `docs/`,
   linked from the matching section above.
2. **A spec for building a big module that does not exist yet** →
   `docs/rules/`, linked from [rules/README.md](rules/README.md).
3. **A rule that applies to every change** → `.claude/rules/`, not here.
   Keep it short; long specs belong in `docs/rules/`.
4. When a `docs/rules/` spec is fully implemented, move it to `docs/tasks/`
   with a `> ✅ DONE — <date>` banner describing what was actually built
   (see the existing files there for the format).
