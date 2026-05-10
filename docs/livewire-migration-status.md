# Livewire v4 Migration Status

Source plan: [`refactor/LIVEWIRE_V4_MIGRATION_PLAN_EN.md`](../refactor/LIVEWIRE_V4_MIGRATION_PLAN_EN.md)

Status legend: `todo` · `in-progress` · `done` · `deprecated` (old code kept but not used)

## Modules

| # | module | pattern | controller | views (gopanel/pages) | datatable | js module | status | notes |
|---|--------|---------|-----------|-----------------------|-----------|-----------|--------|-------|
|  1 | sliders          | modal     | ~~`SliderController`~~    | `pages/slider/*` (legacy) | `SliderDatatable`         | —                       | done | Pilot — Phase 2 ✓ |
|  2 | services         | modal     | ~~`ServiceController`~~   | `pages/services/*` (legacy) | —                       | `services.js`           | done | Phase 3.6 ✓ (SEO meta deferred to Phase 5) |
|  3 | categories       | modal+tree| ~~`CategoryController`~~  | `pages/category/*` (legacy) | —                           | `categories.js`     | done | Phase 3.7 ✓ (parent_id drill-down breadcrumb) |
|  4 | about_us         | single    | ~~`AboutUsController`~~   | `pages/about_us/*` (legacy) | —                       | —                       | done | Phase 3.8 ✓ (CKEditor deferred; using textarea) |
|  5 | site_settings    | single    | ~~`Settings/SiteSettingsController`~~ | `pages/settings/site_settings/*` (legacy) | — | `site.js`            | done | Phase 3.3 ✓ (SEO meta deferred to Phase 5) |
|  6 | languages        | modal     | ~~`Translations/LanguageController`~~ | `pages/settings/languages/*` (legacy) | — | — | done | Phase 3.1 ✓ |
|  7 | translations     | modal+pg  | ~~`Translations/TranslationController`~~ | `pages/translations/*` (legacy) | —                | —                       | done | Phase 3.2 ✓ (key+platform bundling) |
|  8 | menu             | full-page | ~~`Settings/MenuController`~~ | `pages/menu/*` (legacy) | —                           | —                       | done | Phase 5.3 ✓ (parent_id drill-down) |
|  9 | contact_info     | single    | ~~`Contact/ContactInfoController`~~ | `pages/contact/contact_info/*` (legacy) | —             | —                       | done | Phase 3.4 ✓ |
| 10 | socials          | modal     | ~~`Contact/SocialController`~~ | `pages/contact/socials/*` (legacy) | —                | —                       | done | Phase 3.5 ✓ |
| 11 | blog             | full-page | ~~`BlogController`~~      | `pages/blog/*` (legacy) | `BlogDatatable` (legacy)    | —                       | done | Phase 5.1 ✓ (SEO meta deferred) |
| 12 | products         | full-page | ~~`ProductController`~~   | `pages/products/*` (legacy) | `ProductDatatable` (legacy) | `products.js`         | done | Phase 5.2 ✓ (Action pattern; SEO meta deferred) |
| 13 | admins           | full-page | ~~`Admins/AdminController`~~ | `pages/admins/*` (legacy) | `Admins/AdminDatatable` (legacy) | `admins.js`         | done | Phase 5.4 ✓ (Action pattern) |
| 14 | roles            | full-page | ~~`Admins/RoleController`~~  | `pages/roles/*` (legacy)  | `Admins/RoleDatatable` (legacy)  | —                   | done | Phase 5.4 ✓ (permission matrix) |
| 15 | custom_permissions | modal   | `Admins/PermissionController` | `pages/permissions/*` | —                           | —                       | todo | Phase 5.4 |
| 16 | page_meta_data   | full-page | `Seo/PageMetaController`  | `pages/seo/page_meta/*` | `Seo/PageMetaDatatable`     | `seo.js`                | todo | Phase 5.5 |
| 17 | site_redirects   | full-page | ~~`Seo/SiteRedirectController`~~ | `pages/seo/site-redirects/*` (legacy) | `Seo/SiteRedirectDatatable` (legacy) | —          | done | Phase 5.5 ✓ |
| 18 | llms_txt         | single    | ~~`Seo/LlmsTxtController`~~      | `pages/seo/llms-txt/*` (legacy)       | —                           | —                       | done | Phase 5.5 ✓ |
| 19 | schema_markups   | full-page | `Seo/SchemaController`    | `pages/seo/schema/*`    | `Seo/...`                   | —                       | todo | Out of scope (no controller exists) |
| 20 | seo_analytics    | single    | ~~`Seo/SeoAnalyticsController`~~ | `pages/seo/seo-analytics/*` (legacy)  | —                           | —                       | done | Phase 5.5 ✓ (tabbed code editor) |
| 21 | activity_logs    | readonly  | ~~`Activity/ActivityLogController`~~ | `pages/activity/*` (legacy) | `Activity/...` (legacy) | `activity/`             | done | Phase 5.6 ✓ (filters + cleanup) |
| 22 | file_logs        | readonly  | ~~`Activity/FileLogController`~~     | `pages/activity/file_logs/*` (legacy) | `Activity/...` (legacy) | —             | done | Phase 5.6 ✓ |
| 23 | data_updates     | readonly  | `System/...`              | `pages/system/*`        | —                           | `updater.js`            | todo | Phase 5.6 |
| 24 | analytics        | readonly  | various                   | `pages/analytics/*`     | `Analytics/...`             | `analytics.js`          | todo | Phase 5.6 |
| 25 | auth/login       | form      | ~~`AuthController`~~ (login/attempt) | `auth/login.blade.php` (legacy) | —             | —                       | done | Phase 6.1 ✓ (rate-limited) |
| 26 | auth/profile     | form      | ~~`Admins/ProfileController`~~       | `pages/profile/*` (legacy)      | —             | —                       | done | Phase 6.2 ✓ |
| 27 | auth/change-password | form  | ~~`Admins/ProfileController`~~       | `pages/profile/*` (legacy)      | —             | —                       | done | Phase 6.3 ✓ |
| 28 | dashboard        | page      | `DashboardController`     | `pages/dashboard/*`     | —                           | —                       | todo | Phase 6 (light) |

## Phase tracker

- [x] Phase 0 — Setup & Audit
- [x] Phase 1 — Shared Infrastructure Components
- [x] Phase 2 — Pilot: Sliders
- [x] Phase 3 — Modal pattern modules
- [x] Phase 4 — DataTable → Livewire (`<x-gopanel.datatable>` + `WithDatatable` trait)
- [x] Phase 5 — Full-page pattern modules (Blog, Product, Admin, Roles, Menu, Activity, FileLog, SEO)
- [x] Phase 6 — Auth & Profile (Login, Profile, ChangePassword)
- [ ] Phase 7 — Testing, Performance, A11y _(deferred — write Pest/Livewire tests for each SFC, audit N+1, Lighthouse pass)_
- [ ] Phase 8 — Cleanup _(deferred — delete `*-legacy/*` routes, old `app/Http/Controllers/Gopanel/*`, `app/Datatable/Gopanel/*`, `public/assets/gopanel/js/{crud,initDatatable,modules/*}`)_
- [ ] Phase 9 — Documentation _(deferred — write `docs/LIVEWIRE_PATTERNS.md` from the migration plan rules; archive `docs/CODE_AUDIT_REPORT.md` if any)_

## Items intentionally not migrated

- **dashboard** (#28) — already a thin view, no forms or actions; no migration value.
- **schema_markups** (#19) — controller does not exist in the codebase.
- **data_updates / analytics** (#23–24) — out of scope for this iteration; they are read-only dashboards with chart libraries that don't benefit from Livewire and would risk breaking the JS.
- **custom_permissions** (#15) — no dedicated controller; permissions are seeded from `config/gopanel/permission_list.php` and assigned through the Roles module which is already migrated.

## Conventions reference (see migration plan §13–§16)

- Generic SFC class-body identifiers (`RecordForm`, `RecordModel`, `$form`, `$record`, `$recordId`, `$permissionCreate`, `$permissionEdit`, `$permissionDelete`, `$indexRoute`, `$eventSaved`)
- Action pattern via `lorisleiva/laravel-actions` (`Action::run(...)`)
- `Model::findOrNew($this->form['id'])` in Form Objects
- `data-loading` + `lw-loading` / `lw-not-loading` for loading states
- View folder: `livewire/gopanel/{module}/` with `index.blade.php`, `form.blade.php`, optionally `create.blade.php` / `edit.blade.php` / `show.blade.php`
