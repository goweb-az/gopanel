# Sayt qaydaları (frontend)

Route `routes/web.php` · Controller `app/Http/Controllers/Site/`
· View `resources/views/site/` · Servis `app/Services/Site/`

## 1. Route-lar dinamikdir

Sayt səhifələrinin çoxu **kodda yazılmır** - `menus` cədvəlindən qurulur:
`routes/web.php` aktiv dilləri gəzir, hər dil üçün prefiks açır və
`Menu::getRoutes($locale)` nəticəsindən route qeydiyyatı aparır.

Nəticə etibarilə:

- Yeni səhifə əlavə etmək ADƏTƏN kod dəyişikliyi tələb etmir - menyu yazısı kifayətdir.
- `routes/web.php`-ə əl ilə yazılan route yalnız **texniki** ünvanlar üçündür
  (sitemap, rss, robots.txt, llms.txt, ödəniş callback-i).
- Route adı formatı: `site.{locale}.{ad}` (məs. `site.az.blog.index`).
  Blade-də link `route("site." . app()->getLocale() . ".blog.index")` kimi qurulur.
- `Schema::hasTable()` yoxlaması saxlanılır - miqrasiyadan əvvəl `artisan`
  komandaları sınmasın.

Detallar: [docs/menus-and-routes.md](../../docs/menus-and-routes.md)

## 2. Middleware sırası

- `language` - aktiv dili təyin edir; dilə bağlı bütün route-larda olmalıdır.
- `site.redirects` - `site_redirects` cədvəlinə görə yönləndirmə.
- `track.analytics` - klik/analitika yazır.

Texniki ünvanlar (`sitemap.xml`, `rss`, `robots.txt`, `llms.txt`) bu iki
middleware-dən **çıxarılır** (`withoutMiddleware`) - əks halda hər bot sorğusu
analitikaya düşür və hesabatı korlayır.

## 3. Çoxdillilik

- Mətnlər `translations` cədvəlindədir (`__('key')`), model sahələri isə
  `field_translations`-da (`Translation` trait + `$translatedAttributes`).
- Blade-də sabit mətn **hardcode edilmir** - tərcümə açarı işlədilir.
- Yeni tərcümə açarı `config/gopanel/translation_pages.php`-dəki uyğun səhifəyə
  aid edilir ki, panelin tərcümə redaktorunda görünsün.
- Tərcümə keşi: `App\Services\Gopanel\Translations\TranslationCacheService`.
  Yazı dəyişəndə keş invalidasiya olunur - əl ilə `Cache::flush()` çağırılmır.

Detallar: [docs/translations.md](../../docs/translations.md)

## 4. SEO

- Meta məlumatları `PageMetaData` modelindən `App\Services\Site\Seo\MetaService`
  ilə həll olunur; blade-də `<title>`/`<meta>` əl ilə yazılmır.
- Alternativ dil linkləri (`hreflang`) `AlternatesService`-dən gəlir.
- Sitemap/RSS-ə yeni model əlavə edəndə `SitemapController`-dəki mənbə siyahısı
  yenilənir - səhifə orada yoxdursa indeksləşməyəcək.

Detallar: [docs/seo.md](../../docs/seo.md), [docs/sitemap-rss.md](../../docs/sitemap-rss.md)

## 5. Keş

Sayt tərəfi oxu ağırlıqlıdır - lookup, menyu, tərcümə, ana səhifə blokları
keşlənir:

```php
use App\Services\Cache\CacheService;
use App\Support\Cache\CacheKey;

$menu = CacheService::remember(
    CacheKey::menu('web', 'header'),
    fn () => $this->menuQuery->header(),
);
```

- Açar **həmişə** `CacheKey` ilə qurulur - əl ilə string yazılmır.
- Model dəyişəndə keş `CacheKey::flushForModelType()` ilə təmizlənir;
  model → qrup xəritəsi `config/custom/cache.php`-dədir.
- `Cache::flush()` bir modul dəyişikliyi üçün **çağırılmır** - bütün sistemin
  keşini uçurur.

## 6. Fayl və şəkil URL-ləri

Model faylının ünvanı blade-də `asset()`/`Storage::url()` ilə yığılmır -
`App\Support\Url\CdnUrl::url($path)` işlədilir. CDN qoşulanda tək dəyişən
(`CDN_URL`) bütün sayt üçün kifayət edir.
