# Sitemap və RSS

## Ünvanlar (route adları dəyişməyib)

```text
/sitemap.xml          sitemap.index               indeks - hər dilin sitemap-ı
/{dil}/sitemap.xml    site.{dil}.sitemap.single   həmin dilin URL-ləri
/rss-index.opml       site.rss.index              bütün dillərin feed-ləri (OPML)
/{dil}/rss.xml        site.{dil}.rss.single       həmin dilin RSS 2.0 feed-i
```

Hamısı `application/xml; charset=UTF-8`, XML prologu faylın ən əvvəlindədir.
Route-lar aktiv dillər üzrə `routes/web.php`-də qeyd olunur (`->defaults('locale', ...)`)
və `site.redirects` / `track.analytics`-dən çıxarılıb.

## Məntiq harada

| Fayl | Nə edir |
|---|---|
| `App\Services\Seo\SitemapService` | URL siyahısı: ana səhifə + menyu səhifələri + məzmun modelləri |
| `App\Services\Seo\RssService` | Tarixli yazılar (ən yenisi başda, limit `config('seo.content.rss_limit')`) |
| `App\Services\Seo\PublicContentService` | «Hansı səhifə publikdir, ünvanı nədir» - Markdown ilə ortaq |
| `config/seo/content.php` | Məzmun modellərinin siyahısı |
| `resources/views/site/feed/*.blade.php` | Yalnız çap - sorğu və şərt yoxdur |

Əvvəl sorğu controller-də, filtr və döngü blade-də idi; RSS isə 12 saatlıq
sabit keşdə qalırdı. İndi nəticə `CacheKey::seo(...)` altında keşlənir və açar
bütün keş qruplarının versiyasını daşıyır - paneldə yazı dəyişəndə feed də yenilənir.

## Yeni model əlavə etmək

Kod yazılmır - `config/seo/content.php`-ə bir sətir:

```php
\App\Models\Site\News::class => [
    'changefreq' => 'daily',
    'priority'   => '0.80',
    'rss'        => true,      // tarixli məzmundursa
    'list_menu'  => 'news',    // siyahı menyusunun route_name-i (Markdown siyahısı üçün)
    'limit'      => 1000,
],
```

Model üçün tələblər: `Translation` + `slug` tərcümə sahəsi, `public $controller`
(detal səhifəsini `DynamicContentController` açır), `is_active` sütunu.
Eyni sətir sitemap, RSS, `/markdown-index.md` və `/llms-full.txt`-ə birdən düşür.

Menyu səhifələri avtomatik daxil olur - yalnız `config/site/menu_routes.php`-də
route-u olan (HTML-də açılan) aktiv menyular.

Test: `tests/Feature/Site/FeedEndpointsTest.php`.
