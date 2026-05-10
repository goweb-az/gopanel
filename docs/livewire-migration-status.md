# Livewire v4 Migration Status

Source plan: [`refactor/LIVEWIRE_V4_MIGRATION_PLAN_EN.md`](../refactor/LIVEWIRE_V4_MIGRATION_PLAN_EN.md)

Status legend: `todo` · `in-progress` · `done` · `deprecated` (old code kept but not used)

## Modules

| # | module | pattern | controller | views (gopanel/pages) | datatable | js module | status | notes |
|---|--------|---------|-----------|-----------------------|-----------|-----------|--------|-------|
|  1 | sliders          | modal     | ~~`SliderController`~~    | `pages/slider/*` (legacy) | `SliderDatatable`         | —                       | done | Pilot — Phase 2 ✓ |
|  2 | services         | modal     | ~~`ServiceController`~~   | `pages/services/*` (legacy) | —                       | `services.js`           | done | Phase 3.6 ✓ (SEO meta deferred to Phase 5) |
|  3 | categories       | modal+tree| `CategoryController`      | `pages/category/*`      | —                           | `categories.js`         | todo | Phase 3.7 (nested sortable) |
|  4 | about_us         | single    | ~~`AboutUsController`~~   | `pages/about_us/*` (legacy) | —                       | —                       | done | Phase 3.8 ✓ (CKEditor deferred; using textarea) |
|  5 | site_settings    | single    | ~~`Settings/SiteSettingsController`~~ | `pages/settings/site_settings/*` (legacy) | — | `site.js`            | done | Phase 3.3 ✓ (SEO meta deferred to Phase 5) |
|  6 | languages        | modal     | ~~`Translations/LanguageController`~~ | `pages/settings/languages/*` (legacy) | — | — | done | Phase 3.1 ✓ |
|  7 | translations     | modal+pg  | `Translations/...`        | `pages/translations/*`  | `Translations/...`          | —                       | todo | Phase 3.2 |
|  8 | menu             | full-page | `Settings/MenuController` | `pages/menu/*`          | —                           | —                       | todo | Phase 5.3 (nested DnD) |
|  9 | contact_info     | single    | ~~`Contact/ContactInfoController`~~ | `pages/contact/contact_info/*` (legacy) | —             | —                       | done | Phase 3.4 ✓ |
| 10 | socials          | modal     | ~~`Contact/SocialController`~~ | `pages/contact/socials/*` (legacy) | —                | —                       | done | Phase 3.5 ✓ |
| 11 | blog             | full-page | ~~`BlogController`~~      | `pages/blog/*` (legacy) | `BlogDatatable` (legacy)    | —                       | done | Phase 5.1 ✓ (SEO meta deferred) |
| 12 | products         | full-page | ~~`ProductController`~~   | `pages/products/*` (legacy) | `ProductDatatable` (legacy) | `products.js`         | done | Phase 5.2 ✓ (Action pattern; SEO meta deferred) |
| 13 | admins           | full-page | ~~`Admins/AdminController`~~ | `pages/admins/*` (legacy) | `Admins/AdminDatatable` (legacy) | `admins.js`         | done | Phase 5.4 ✓ (Action pattern) |
| 14 | roles            | full-page | ~~`Admins/RoleController`~~  | `pages/roles/*` (legacy)  | `Admins/RoleDatatable` (legacy)  | —                   | done | Phase 5.4 ✓ (permission matrix) |
| 15 | custom_permissions | modal   | `Admins/PermissionController` | `pages/permissions/*` | —                           | —                       | todo | Phase 5.4 |
| 16 | page_meta_data   | full-page | `Seo/PageMetaController`  | `pages/seo/page_meta/*` | `Seo/PageMetaDatatable`     | `seo.js`                | todo | Phase 5.5 |
| 17 | site_redirects   | full-page | `Seo/SiteRedirectController` | `pages/seo/redirects/*` | `Seo/SiteRedirectDatatable` | —                  | todo | Phase 5.5 |
| 18 | llms_txt         | single    | `Seo/LlmsTxtController`   | `pages/seo/llms_txt/*`  | —                           | —                       | todo | Phase 5.5 |
| 19 | schema_markups   | full-page | `Seo/SchemaController`    | `pages/seo/schema/*`    | `Seo/...`                   | —                       | todo | Phase 5.5 |
| 20 | seo_analytics    | readonly  | `Seo/AnalyticsController` | `pages/seo/analytics/*` | —                           | —                       | todo | Phase 5.5 |
| 21 | activity_logs    | readonly  | ~~`Activity/ActivityLogController`~~ | `pages/activity/*` (legacy) | `Activity/...` (legacy) | `activity/`             | done | Phase 5.6 ✓ (filters + cleanup) |
| 22 | file_logs        | readonly  | ~~`Activity/FileLogController`~~     | `pages/activity/file_logs/*` (legacy) | `Activity/...` (legacy) | —             | done | Phase 5.6 ✓ |
| 23 | data_updates     | readonly  | `System/...`              | `pages/system/*`        | —                           | `updater.js`            | todo | Phase 5.6 |
| 24 | analytics        | readonly  | various                   | `pages/analytics/*`     | `Analytics/...`             | `analytics.js`          | todo | Phase 5.6 |
| 25 | auth/login       | form      | `AuthController`          | `pages/auth/*`          | —                           | —                       | todo | Phase 6.1 |
| 26 | auth/profile     | form      | `AuthController`          | `pages/profile/*`       | —                           | —                       | todo | Phase 6.2 |
| 27 | auth/change-password | form  | `AuthController`          | `pages/profile/*`       | —                           | —                       | todo | Phase 6.3 |
| 28 | dashboard        | page      | `DashboardController`     | `pages/dashboard/*`     | —                           | —                       | todo | Phase 6 (light) |

## Phase tracker

- [x] Phase 0 — Setup & Audit (in progress)
- [ ] Phase 1 — Shared Infrastructure Components
- [ ] Phase 2 — Pilot: Sliders
- [ ] Phase 3 — Modal pattern modules
- [ ] Phase 4 — DataTable → Livewire
- [ ] Phase 5 — Full-page pattern modules
- [ ] Phase 6 — Auth & Profile
- [ ] Phase 7 — Testing, Performance, A11y
- [ ] Phase 8 — Cleanup
- [ ] Phase 9 — Documentation
