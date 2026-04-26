# Sitemap and RSS

## Sitemap

Endpoints:

```text
/sitemap.xml
/{locale}/sitemap.xml
```

The sitemap includes:

- home pages
- active menu pages
- active blog pages

To add a new model to the sitemap:

1. Add a cached list method, such as `getCachedAll()`.
2. Add a single URL accessor, such as `getSingleUrlAttribute`.
3. Register the model in the sitemap controller/view.

## RSS

Endpoints:

```text
/rss-index.opml
/{locale}/rss.xml
```

RSS currently uses active blog posts and exposes title, description, link, publish date, and guid.

