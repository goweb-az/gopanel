# SEO, Meta, Redirects, and LLMs.txt

## Meta System

Site meta data is resolved in this order:

```text
Controller single page meta
  -> Menu meta
  -> SiteSetting default meta
```

Important files:

```text
app/Services/Site/Seo/MetaService.php
app/Traits/MetaData.php
resources/views/site/layouts/meta.blade.php
resources/views/site/layouts/head.blade.php
```

For a model to support meta data:

```php
use MetaData;
```

Then call meta sharing from the controller when rendering a single page.

## SEO Analytics Snippets

Admin-managed SEO snippets are stored and rendered in the site layout.

Common fields:

- `head`
- `body`
- `footer`
- `robots_txt`
- `ai_txt`
- `other`

Seeder:

```text
database/seeders/SeoAnalyticsSeeder.php
```

## Site Redirects

Redirect rules are handled by `SiteRedirectMiddleware`.

Rules can use:

- exact match
- regex
- wildcard

Redirect rules can be locale-specific or global.

## LLMs.txt

The project supports an `/llms.txt` endpoint for AI crawlers.

Important files:

```text
app/Models/Seo/LlmsTxt.php
database/seeders/LlmsTxtSeeder.php
```

