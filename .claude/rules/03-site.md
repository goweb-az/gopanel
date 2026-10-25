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

Texniki ünvanlar (`sitemap.xml`, `rss`, `robots.txt`, `ai.txt`, `ads.txt`,
`llms.txt`, `llms-full.txt`, `*.md`) bu iki
middleware-dən **çıxarılır** (`withoutMiddleware`) - əks halda hər bot sorğusu
analitikaya düşür və hesabatı korlayır.

## 3. Çoxdillilik

- UI mətnləri `translations` cədvəlindədir, model sahələri `field_translations`-da
  (`Translation` trait + `$translatedAttributes`).
- Blade-də sabit mətn **hardcode edilmir**. Çağırış HƏMİŞƏ belədir:

  ```blade
  @lang('website.<page>.<group>.<key>')
  ```

  Nümunə:

  ```blade
  <a href="{{ url($currentLocale) }}">@lang('website.general.title.home')</a>      {{-- ✅ --}}
  <a href="{{ url($currentLocale) }}">{{ __('Əsas səhifə') }}</a>                 {{-- ❌ --}}
  ```

  `__('Son yazılar')` kimi «mətnin özü açar» forması yazılmır - mətn dəyişəndə
  açar da dəyişir və bütün dillər itir.
- **`group`:** qısa mətn (başlıq, düymə, menyu, etiket) → `title`; uzun mətn
  (cümlə, paraqraf) → `content`. Başqa qrup yoxdur.
- **`page`:** saytın bir ekranı = bir səhifə (`config/gopanel/translation_pages.php`).
  Eyni ekranın modalı və ya boş vəziyyəti ayrıca səhifə deyil. Header, footer və
  ümumi düymələr `general`-dadır. Kataloqda olmayan səhifəyə yazılan sətir rədd edilir.
- **Açar:** kiçik hərf, rəqəm, `_`, `-`. Nöqtə olmaz.
- **Frontend implement olunanda** şablondakı bütün sözlər
  `database/data/translations/website.json`-a yazılır və
  `php artisan translations:seed-site` işlədilir. Command yalnız çatışmayanları
  əlavə edir, production-da da təkrar işlədilir. Yeni söz əvvəlcə JSON-a, sonra
  Blade-ə yazılır. Yalnız paneldən əlavə edilən söz başqa mühitə keçmir.
- Mühitlər arası köçürmə (prod → lokal) paneldəki **Excel-ə çıxar / Excel-dən yüklə**
  iləndir. Format eynidir, əl ilə SQL dump edilmir.
- Tərcümə keşi: `TranslationCacheService::flush()`. Əl ilə `Cache::flush()` çağırılmır.

**Niyə:** törəmə layihələrdə (aquastores, texnotech, b2bpro) sözlər prod-a
çıxanda itirdi. Paneldən əlavə olunan söz dev-də qalırdı, nöqtəli və ya
kataloqdan kənar açarlar isə səssizcə tapılmırdı.

Detallar: [docs/translations.md](../../docs/translations.md)

## 4. SEO

SEO qatı HAZIRDIR - yenidən yazılmır, aşağıdakılar işlədilir:

| Ehtiyac | Hazır olan |
|---|---|
| Detal səhifəsinin meta-sı | controller: `$this->setPageMeta($item)` (meta sətri yoxdursa title/description-dan qurulur) |
| Texniki səhifə indekslənməsin | `$this->setPageRobots('noindex, follow')` - head-də `robots` TƏK teqdir |
| JSON-LD | `$this->setSchemaGraph([$this->schema()->blogPosting($blog), ...])` → `App\Services\Seo\SchemaService` |
| hreflang + `x-default` + dil düymələri | `AlternatesService` + `LanguageSwitcherService` (slug da tərcümə olunur) |
| Yanlış dildəki slug | `SlugAliasResolver` → 301 (`DynamicContentController`) |
| Publik səhifələrin siyahısı | `App\Services\Seo\PublicContentService` + `config/seo/content.php` |
| Sitemap / RSS | `SitemapService` / `RssService` - yeni model `config/seo/content.php`-ə yazılır, controller/blade-ə toxunulmur |
| `.md` variantı, `/markdown-index.md`, `/llms-full.txt` | `App\Services\Seo\Markdown\MarkdownPageService`, `markdown_url()` |
| robots/ai/ads/llms.txt | `Site\Seo\TxtController`, panel: SEO → SEO və analitika («Maşın faylları» bloku) |
| Meta/schema sabitləri | `config/seo/meta.php`, `config/seo/schema.php`, `config/seo/markdown.php` |

Qaydalar:

- Blade-də `<title>`/`<meta>`/JSON-LD əl ilə yazılmır - JSON blade-də `"{{ }}"`
  ilə qurulanda dırnaq/`</script>` markup-ı sındırır; `SchemaService` massiv qurur.
- Maşın faylları (`.md`, `.txt`, `.xml`) HTML-dən çox məzmun açmır: deaktiv
  qeyd 404-dür. Yeni publik model `PublicContentService` qaydalarından keçir
  (`is_active`, slug, `$controller`), ikinci yoxlama yazılmır.
- Yeni texniki ünvan dil qrupundan ƏVVƏL, `withoutMiddleware(['site.redirects', 'track.analytics'])`
  ilə qeyd olunur və lazımsa `config/seo/markdown.php` → `excluded_prefixes`-ə yazılır.
- SEO keşləri açarda `PublicContentService::fingerprint()` (keş qruplarının
  versiyaları) daşıyır - əl ilə `Cache::forget` yazılmır.

Detallar: [docs/seo.md](../../docs/seo.md), [docs/sitemap-rss.md](../../docs/sitemap-rss.md),
[docs/markdown-pages.md](../../docs/markdown-pages.md), [docs/seo-machine-files.md](../../docs/seo-machine-files.md)

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

## 7. Saytın asset-ləri

Saytın (frontend şablonunun) statik faylları **yalnız** `public/assets/front/`
altındadır:

```text
public/assets/front/
├── css/
│   ├── style.css     şablondan gələn (toxunulmur)
│   └── custom.css    BİZİM yazdığımız bütün CSS
├── js/
│   ├── main.js       şablondan gələn (toxunulmur)
│   └── custom.js     BİZİM yazdığımız bütün JS
├── images/   şablonun şəkilləri, ikonlar
├── fonts/    şriftlər
└── libs/     kənar kitabxanalar (swiper, fancybox...)
```

### Şablon faylları dəyişdirilmir

- Dizaynerdən/şablondan gələn CSS və JS faylları (`style.css`, `main.js` və s.)
  **redaktə edilmir** - olduğu kimi qalır.
- Öz yazdığımız bütün CSS **`css/custom.css`**-ə, bütün JS **`js/custom.js`**-ə
  yazılır. Düzəliş lazımdırsa (rəng, ölçü, mobil) şablon faylında yox,
  `custom.css`-də üstələnir.
- `custom.css` şablon CSS-lərindən **sonra**, `custom.js` şablon JS-lərindən
  **sonra** qoşulur - üstələmə və şablon funksiyalarına müraciət işləsin.

### Hər xırda şey üçün ayrıca fayl yaradılmır

- **CSS:** istisnasız hamısı `custom.css`-dədir. Səhifəyə və ya komponentə görə
  ayrıca CSS faylı yaradılmır. Fayl daxilində bölmələr şərhlə ayrılır
  (`/* ===== Ana səhifə ===== */`).
- **JS:** default yer `custom.js`-dir. Karusel, modal, tab, forma kimi komponent
  üçün ayrıca fayl (`home-carousel.js`, `contact-form.js`) **yaradılmır**.
- **İstisna:** bir səhifənin JS-i artıq çoxdursa **və** yalnız o səhifədə
  işləyirsə, səhifə üçün BİR fayl açılır: `js/pages/<səhifə>.js`
  (`js/pages/home.js`). O, yalnız həmin səhifənin blade-ində
  `@push('scripts')` ilə qoşulur. Bir səhifə = ən çox bir fayl.
- Blade-dən JS-ə dəyər (route, id, config) yalnız `data-*` atributu ilə ötürülür.

**Niyə:** onlarla kiçik fayl hər səhifədə ayrıca sorğu deməkdir və kodun
harada olduğu axtarılmalı olur. Bütün kod bir faylda olanda isə ana səhifənin
ağır JS-i hər səhifəyə yüklənir - səhifə faylı yalnız bu halda açılır.

**Niyə:** şablonun yeni versiyası gələndə (yeni səhifə, düzəliş) onun faylları
sadəcə üzərinə yazılır. Bizim kod şablon faylının içində olsaydı ya itərdi, ya da
iki versiyanı əl ilə sətir-sətir birləşdirmək lazım gələrdi. Ayrıca fayl həm də
«bu sətri kim yazıb, nəyə görə» sualına dərhal cavab verir.

- Blade-də qoşulma `asset_v('assets/front/...')` iləndir - fayl dəyişəndə
  brauzer keşi avtomatik yenilənir.
- Panelin faylları (`public/assets/gopanel/`) sayt blade-lərində işlədilmir,
  saytın faylları da panelə qoşulmur.
- Admin yüklədiyi fayllar (model şəkilləri) bura düşmür - onlar storage-dadır
  və `CdnUrl` ilə verilir (§6).
- Blade-də `<script>`/`<style>` bloku yoxdur (01-umumi §3) - kod bu qovluqdakı
  fayllara yazılır.

**Niyə:** törəmə layihələrdə şablon faylları `public/`-in kökünə, `public/site`
və ya panel qovluğuna səpələnirdi. Hansı faylın sayta, hansının panelə aid
olduğu bilinmirdi, deploy-da və CDN alias-ında (`/assets`) bir yer kifayət
etmirdi.
