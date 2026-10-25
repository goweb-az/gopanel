# Səhifələrin Markdown variantı (`.md`)

Hər publik səhifənin canonical ünvanına `.md` əlavə etdikdə həmin səhifənin
maşın (AI agent, LLM) üçün Markdown variantı açılır:

```text
/az                 → /az.md
/az/bloqlar         → /az/bloqlar.md
/az/{yazi-slug}     → /az/{yazi-slug}.md
/markdown-index.md  → bütün .md ünvanları, dillər üzrə
/llms-full.txt      → bütün sənədlər bir faylda
```

**Niyə:** agentlər HTML-i (menyu, footer, skript) təmizləməyə token xərcləyir
və tez-tez səhv kəsir. Markdown eyni mətni təmiz verir və öz canonical
ünvanını bildirir.

## Cavab

```text
Content-Type: text/markdown; charset=UTF-8
X-Robots-Tag: noindex, follow          (indeks yalnız HTML-ə düşsün)
Link: <canonical>; rel="canonical"
Content-Language: {dil}
```

HTML səhifə öz Markdown variantını iki yolla elan edir (ikisi də `markdown_url()`-dən):

- `<head>`: `<link rel="alternate" type="text/markdown" href="...md">`
- HTTP başlıq: `Link: <...md>; rel="alternate"; type="text/markdown"`
  (`App\Http\Middleware\Seo\MarkdownAlternateMiddleware`, `web` qrupunda)

## Qaydalar

- Markdown HTML saytdan **çox məzmun açmır**: deaktiv/silinmiş qeyd, route-u
  olmayan menyu, tanınmayan yol → **404**. `markdown_url()` belə səhifə üçün
  `null` qaytarır - sayt heç vaxt 404-ə keçid vermir.
- Yanlış dildəki slug (`/en/bloqlar.md`) `SlugAliasResolver` ilə `/en/blogs.md`-ə 301 alır.
- `config/seo/markdown.php` → `excluded_prefixes`: Markdown variantı olmayan
  prefikslər (panel, api, checkout...). Törəmə layihə öz əməliyyat ünvanlarını əlavə edir.
- Route-lar dil qrupundan **əvvəl** qeyd olunub - əks halda `/{dil}/{slug}`
  catch-all-ı `bloqlar.md`-ni slug kimi oxuyardı.

## Məzmun haradan gəlir

`App\Services\Seo\Markdown\MarkdownPageService` mövcud modellərdən oxuyur
(yeni şablon yoxdur), redaktor HTML-i `HtmlToMarkdown` ilə çevrilir:

| Səhifə | Məzmun |
|---|---|
| Ana səhifə | SiteSetting meta başlığı/təsviri + menyu səhifələrinin siyahısı |
| Menyu səhifəsi | Menyu başlığı/təsviri; `menu_blocks` (məs. `contact` → ünvan/telefon/e-poçt); `config/seo/content.php`-də `list_menu` bu menyudursa həmin modelin yazıları |
| Məzmun qeydi | Başlıq, tarix, `short_description`, `description` |

Bölmə başlıqları `config/seo/markdown.php` → `labels`-dadır (dil üzrə, `default` ehtiyat).

## Keş

Sənədlər `CacheKey::seo(...)` altında, TTL `seo.markdown.cache_ttl`. Açar bütün
keş qruplarının versiyasını daşıyır (`PublicContentService::fingerprint()`):
`CacheInvalidationServiceProvider` model/tərcümə dəyişəndə qrupu artırır və
sənəd özü köhnəlir. Aktivlik yoxlaması (`supports()`) keşlənmir.

## /llms-full.txt

`MarkdownPageService::full()` - bütün sənədlər `---` ilə ayrılaraq. Söndürmək:
`SEO_LLMS_FULL_ENABLED=false` (404 verir). Sənəd sayı:
`SEO_LLMS_FULL_MAX_DOCUMENTS` (default 300).

Test: `tests/Feature/Site/MarkdownPagesTest.php`.
