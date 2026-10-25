# SEO: meta, head, JSON-LD, hreflang, yönləndirmələr

Starter-də SEO qatı hazırdır - yeni layihədə **yenidən yazılmır**, yalnız
konfiqurasiya doldurulur. Maşın faylları (robots/ai/ads/llms) və Markdown
variantı ayrıca sənədlərdədir:

- [seo-machine-files.md](seo-machine-files.md) - robots.txt, ai.txt, ads.txt, llms.txt, llms-full.txt, panel bloku
- [markdown-pages.md](markdown-pages.md) - hər səhifənin `.md` variantı, `/markdown-index.md`
- [sitemap-rss.md](sitemap-rss.md) - sitemap və RSS

## Fayl xəritəsi

| Ehtiyac | Sinif / fayl |
|---|---|
| `<title>` / `<meta>` dəyərləri | `App\Services\Site\Seo\MetaService` |
| Meta ehtiyat dəyərləri, `og:locale` xəritəsi, default robots | `config/seo/meta.php` |
| canonical + hreflang + `x-default` | `App\Services\Site\Seo\AlternatesService` |
| URL-i başqa dilə çevirmək (slug da tərcümə olunur) | `App\Services\Site\Seo\LanguageSwitcherService` |
| Yanlış dildəki slug → 301 düzgün ünvana | `App\Services\Site\Seo\SlugAliasResolver` |
| JSON-LD qrafı | `App\Services\Seo\SchemaService` + `config/seo/schema.php` |
| JSON-LD çapı | `resources/views/site/schema-markups/render.blade.php` |
| Publik səhifələrin tək mənbəyi | `App\Services\Seo\PublicContentService` + `config/seo/content.php` |
| Head | `resources/views/site/layouts/head.blade.php`, `meta.blade.php`, `inc/canonical.blade.php` |
| Head dəyərlərinin hazırlanması | `App\Providers\ViewServiceProvider::shareSiteSeoExtras()` |

## Meta

Mənbə sırası (birinci dolu olan qazanır):

```text
1. Səhifənin öz qeydi      → controller: $this->setPageMeta($blog)
2. Menyu sətri            → URL-in 2-ci seqmenti = menyunun cari dildəki slug-ı
3. SiteSetting meta sətri → saytın ümumi təsviri
4. config/seo/meta.php    → baza boş olanda
```

Controller-də (`SiteController` törəmələri):

```php
$this->setPageMeta($blog);                                   // meta sətri yoxdursa title/description-dan qurulur
$this->setPageMeta(['title' => 'Axtarış', 'description' => '...']); // menyusuz səhifə
$this->setPageRobots('noindex, follow');                     // texniki səhifə
```

`MetaService`-in köhnə metodları (`compose()`, `share()`, `sharePageMeta()`,
`composeAndShare()`) və `SiteController::meta_share()` geriyə uyğunluq üçün
saxlanılıb. Yeni kodda `composeForRequest()` / `composeForModel()` /
`composeFromArray()` işlədilir.

## Robots

Head-də `robots` teqi **bir dəfə** çap olunur (`$page_robots`). Əvvəl üç
fərqli `robots` teqi var idi - axtarış sistemi onları birləşdirir və `noindex`
heç vaxt qüvvəyə minmirdi. Default `config('seo.meta.robots')`.

## Open Graph / Twitter

`meta.blade.php`: `og:locale` (`az_AZ` formatı, `config/seo/meta.php` →
`og_locales`) + `og:locale:alternate`, `og:site_name` (saytın adı, səhifə
başlığı yox), `og:url` = canonical, `twitter:image`. Şəkil yoxdursa
`config('seo.meta.default_image')` → sayt loqosu.

## hreflang

`AlternatesService` hər aktiv dil üçün `LanguageSwitcherService::urlFor()`
ünvanını verir (slug tərcümə olunur: `/az/bloqlar` ↔ `/en/blogs`) və
`x-default` əlavə edir (default dil: `languages.default`, yoxdursa `app.locale`).
Header/footer dil düymələri də eyni mənbədən gəlir (`$language_urls`).

`/en/bloqlar` kimi yanlış dildəki slug `DynamicContentController`-də
`SlugAliasResolver` ilə `/en/blogs`-a **301** alır (əvvəl 404 idi).

## JSON-LD

Blade-də əl ilə JSON yazılmır. Controller:

```php
$this->setSchemaGraph([
    $this->schema()->blogPosting($blog, $blog->single_url),
    $this->schema()->breadcrumb([['name' => 'Ana səhifə', 'url' => url('az')], ...]),
    $this->schema()->faqPage($faqs),
]);
```

Baza qovşaqları (`Organization` + `WebSite`) avtomatik əlavə olunur; controller
heç nə çağırmasa da `ViewServiceProvider` onları verir. Mövcud metodlar:
`organization`, `webSite`, `breadcrumb`, `blogPosting`, `newsArticle`,
`itemList`, `faqPage`, `webPage`. Şəhər/ölkə/təşkilat tipi `config/seo/schema.php`-dədir.

Köhnə `site/schema-markups/{home,contact,blog/*}.blade.php` faylları silinməyib
(törəmə layihələr `setSchema('...')` ilə işlədə bilər), lakin starter
controller-ləri artıq `setSchemaGraph()` işlədir.

## SEO analitika kodları

`seo_analytics` sətri: `head`, `body`, `footer`, `robots_txt`, `ai_txt`,
`ads_txt`, `other`. Panel: SEO → SEO və analitika. Yadda saxlayanda
`SeoAnalyticsService` `seo_analytics` keşini silir (əvvəl `/robots.txt`
keş təmizlənənə qədər köhnə mətni verirdi).

## Yönləndirmələr

`SiteRedirectMiddleware` (`site.redirects`) `site_redirects` cədvəlinə görə
yönləndirir. Maşın faylları (`*.md`, `*.txt`, `*.xml`, `*.opml`) heç vaxt
yönləndirilmir.

## İstəyə bağlı (starter-də yoxdur)

qrgate-dən köçürülməyən, lazım olsa törəmə layihədə əlavə olunan modullar:
SEO səhifələri modulu (SeoPage), avtomatik cover şəkilləri, daxili keçid
qrafı (internal links), paneldən idarə olunan Schema markup modulu.
