
<p align="center">
  <img src="https://proweb.az/uploads/images/statics/06df94f842-Proweb-bu-gunun-reqemsal-dunyasi-ucun-innovativ-veb-heller.png" alt="Gopanel Logo" width="320">
</p>

<p align="center">
  <strong>Versiya:</strong> 1.0.0  
</p>

---

# Gopanel – Laravel əsaslı hazır admin panel

**Gopanel** Laravel 10 ilə hazırlanmış, istifadəyə tam hazır və genişlənə bilən bir admin panel şablonudur.  
Yeni layihələr üçün sürətli başlanğıc və modul əsaslı inkişaf imkanları təqdim edir.

---

## 🚀 Qurulum

Layihəni yaratmaq üçün terminalda aşağıdakı əmrlərdən birini istifadə edin:

```bash
composer create-project goweb/gopanel
```

və ya qovluq adı ilə:

```bash
composer create-project goweb/gopanel your-project-name dev-master
```

---

## ⚙️ Verilənlər bazası ayarları


`.env` faylını açın və aşağıdakı kimi düzəliş edin:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gopanel
DB_USERNAME=root
DB_PASSWORD=
```

Sonra terminalda aşağıdakı əmrləri icra edin:

```bash
php artisan key:generate
php artisan migrate --seed
```

---

## 📦 Daxil edilən paketlər

- [Spatie Laravel Permission](https://github.com/spatie/laravel-permission)
- [Spatie Laravel Activity Log](https://github.com/spatie/laravel-activitylog)
- [Opcodes Laravel Log Viewer](https://github.com/opcodesio/log-viewer)

---

## 📁 Qovluq quruluşu

```
app/Datatable               → Jquery datatable uyğun classlar
app/Traits                  → Modellər üçün köməkçi traitlər
app/Helpers                 → Əlavə helper funksiyalar
resources/views/gopanel     → Panel interfeysi
routes/gopanel.php          → Admin yönləndirmələri
routes/web.php              → Web yönləndirmələri
```

---

## 🧩 İstifadə olunan traitlər və strukturlar

### 🔹 UID + ID birlikdə istifadə etmək üçün:

**Migration:**
```php
use Illuminate\Support\Facades\DB;
$table->uuid('uid')->unique()->default(DB::raw('UUID()'));
```

**Modeldə:**
```php
use AddUuid;
```

---

### 🔹 Fayl yolu və slug

```php
protected $files = ['image']; // Məsələn: image_url qaytarar
public $slug_key = 'title';   // Slug üçün əsas sütun
public $translatedAttributes = ['title', 'description', 'slug']; // Tərcümə edilən sütunlar
```

**Qeyd:** Translation üçün ayrıca migrationda göstərməyə ehtiyac yoxdur.

---

### 🔹 Translation Trait

Tərcümə dəstəyi verir və `$translatedAttributes` ilə birlikdə işləyir.

---

### 🔹 FormatsDate Trait

Tarixləri avtomatik olaraq Azərbaycan dilində formatlamağa imkan verir.

---

### 🔹 HasArchive Trait

Model arxivlənəcəkdirsə:

**Migration:**
```php
$table->timestamp('archived_at')->nullable();
```

**Model:**
```php
use HasArchive;
```

---

### 🔹 MetaData Trait

Modeldə metadata (title, description, keywords) saxlamaq üçün istifadə olunur.

---

### 🔹 UiElements Trait

Modeldə checkbox və switch kimi inputların UI hissələrini avtomatik idarə etmək üçün istifadə olunur.

---


# 🔐 Rol və İcazə Sistemi

**Gopanel**, `Spatie Laravel Permission` paketi üzərindən rol və icazə sistemini tam şəkildə dəstəkləyir.

---

## 🧩 Konfiqurasiya: `config/gopanel/permission_list.php`

İcazələrin qruplar və guard-lar üzrə strukturlaşdırıldığı yerdir.

**Məqsəd:** Yeni icazələr əlavə edərkən buraya yazılır, seeder faylı vasitəsilə verilənlər bazasına yazılır.

**Struktur:**
```php
return [
    'web' => [
        'blog' => [
            ['name' => 'blog.create', 'title' => 'Bloq yarat'],
            ['name' => 'blog.edit', 'title' => 'Bloq redaktə et'],
        ],
        'services' => [
            ['name' => 'service.view', 'title' => 'Xidmətləri görüntülə'],
        ],
    ],
    'api' => [
        'user' => [
            ['name' => 'user.update', 'title' => 'İstifadəçini yenilə'],
        ],
    ],
];
```
**İcazələri bazada yeniləmə:**

```bash
php artisan config:clear
php artisan db:seed --class=PermissionSeeder
```
### 🔹 Admin panel template 

[Skote - Admin & Dashboard Template](https://themesbrand.com/skote/layouts/index.html)

---

# 🌐 Sayt tərəf – Funksionallıq sənədləşdirməsi

---

## 1. Menyu sistemi

Menyular `menus` cədvəlində saxlanılır və `Menu` modeli (`App\Models\Navigation\Menu`) ilə idarə olunur.

### Əsas struktur
- Hər menyu: `route_name`, `type`, `position`, `parent_id`, `sort_order`, `is_active`, `is_dropdown` sahələri
- `parent_id` ilə **parent → children** ağac strukturu qurulur
- Tərcümə: `title`, `slug`, `description` → `field_translations` cədvəlində (`Translation` trait)
- Meta data: `MetaData` trait ilə `page_meta_data` cədvəlinə bağlıdır

### Menyu mövqeləri
`MenuPositionEnum` ilə idarə olunur: `header`, `footer_community`, `other` və s.

### Route qeydiyyatı
`routes/web.php` faylında hər aktiv dil üçün `Menu::getRoutes($locale)` çağırılır.  
Hər menyu üçün `config/site/menu_routes.php` faylından `route_name` ilə controller/method cütü tapılır:

```php
// config/site/menu_routes.php
return [
    'blogs' => [
        'class'  => BlogController::class,
        'method' => 'index',
        'name'   => 'blog.index',
    ],
    'contact' => [
        'class'  => StaticPageController::class,
        'method' => 'contact',
        'name'   => 'contact.index',
    ],
];
```

Nəticədə avtomatik olaraq `az/bloqlar`, `en/blogs`, `ru/blogi` kimi URL-lər yaranır.

### Yeni menyu əlavə qaydası
1. `MenuSeeder`-ə yeni menyu məlumatı əlavə et (`route_name`, `slug`, `title`)
2. `config/site/menu_routes.php`-ə controller/method cütü əlavə et
3. `php artisan db:seed --class=MenuSeeder && php artisan cache:clear`

### Keşləmə
- `Menu::getSiteMenu()` → sayt header/footer üçün (30 gün)
- `Menu::getRoutes($locale)` → route qeydiyyatı üçün (30 gün)
- `Menu::getBySlug($slug)` → meta hesablanması üçün (30 gün)

---

## 2. Meta sistemi (SEO Meta Data)

Saytda meta məlumatları çoxsəviyyəli prioritet sistemi ilə idarə olunur.

### Prioritet zənciri
```
Controller (Single) → Menyu (List/Static) → SiteSetting (Default)
```

| Səviyyə | Misal | Necə işləyir |
|---|---|---|
| **Controller** | Blog single | `$this->meta_share($blog)` → `MetaService::sharePageMeta()` |
| **Menyu** | Blog list, Əlaqə | `MetaService::compose()` → URL segment-dən menyu tapıb meta alır |
| **Default** | Ana səhifə (meta yoxdursa) | `SiteSetting::getCached()->meta()->first()` |

### İşləmə mexanizmi
- `ViewServiceProvider::shareSiteMetaData()` → `site.layouts.head` composer-ində çağırılır
- Əgər controller artıq `meta_title` share edibsə, composer skip edir
- `MetaService::compose()` URL-dən `segment(2)` götürür → `Menu::getBySlug()` ilə menyunu tapır → meta-nı oxuyur

### Fayl strukturu
- `app/Services/Site/Seo/MetaService.php` → meta hesablama/paylaşma
- `app/Traits/MetaData.php` → `meta()`, `metaAll()`, `getMeta()` relation-lar
- `resources/views/site/layouts/meta.blade.php` → og:title, og:description, twitter:card
- `resources/views/site/layouts/head.blade.php` → bütün meta tag-lar, canonical, alternates

### Yeni model üçün meta əlavə
1. Modelə `use MetaData;` trait əlavə et
2. Controller-dən `$this->meta_share($item)` çağır
3. Admin paneldə meta form-u ilə məlumat daxil et

---

## 3. Analitik kodları

Admin paneldə (`/gopanel/seo/seo-analytics`) idarə olunan kod parçaları.

### Sahələr
| Sahə | Harada render olunur | İstifadə |
|---|---|---|
| `head` | `<head>` tag-ı içində | Google Analytics, FB Pixel, meta verification |
| `body` | `<body>` tag-ının əvvəlində | GTM noscript, chat widget |
| `footer` | `</body>` əvvəlində | JS tracking scripts |
| `robots_txt` | `/robots.txt` endpoint-i | Robots.txt məzmunu |
| `ai_txt` | `/ai.txt` endpoint-i | AI crawlers üçün |
| `other` | Ehtiyat sahə | Əlavə kodlar |

### İşləmə
- `SeoAnalytics::getCached()` → `ViewServiceProvider`-dən `site.*` view-larına share olunur
- Head: `{!! $seoAnalytics->head !!}` → `site/layouts/head.blade.php`
- Body: `{!! $seoAnalytics->body !!}` → `site/layouts/main.blade.php`
- Footer: `{!! $seoAnalytics->footer !!}` → `site/assets/scripts.blade.php`
- Textarea-lar fullscreen redaktə funksiyası ilə (`public/assets/gopanel/js/modules/seo.js`)

---

## 4. Link yönləndirmələri (Site Redirects)

Admin paneldə (`/gopanel/seo/site-redirects`) idarə olunan URL yönləndirmə qaydaları.

### İşləmə
- `SiteRedirectMiddleware` hər sorğuda aktiv qaydaları yoxlayır
- Qaydalar: `source` (şablon), `target` (hədəf URL), `http_code` (301/302), `locale`, `priority`
- Match tipləri (`RedirectMatchTypeEnum`): exact, regex, wildcard
- Hit sayğacı: hər uyğunluqda `registerHit()` çağırılır

### Prioritet
1. Dilə uyğun qaydalar (locale) → yüksək prioritet → aşağı ID
2. Dildən asılı olmayan qaydalar (locale=NULL) → fallback

### Keşləmə
- `site_redirect_rules_{locale}` → 5 dəqiqə
- Config-dən `gopanel.site_redirect_status` ilə söndürülə bilər

---

## 5. LLMs.txt

Süni intellekt crawlers (ChatGPT, Claude, Gemini) üçün sayt haqqında məlumat faylı.

### Struktur
- Model: `App\Models\Seo\LlmsTxt` → `llms_txts` cədvəli
- Admin: `/gopanel/seo/llms-txt` → fullscreen textarea ilə redaktə
- Endpoint: `/llms.txt` → `TxtController::llms()` ilə `text/plain` qaytarır
- Head tag: `<link rel="alternate" type="text/plain" href="/llms.txt" title="LLM Information">`

### Seeder
`LlmsTxtSeeder` ilə default məzmun yaradılır.

---

## 6. Sitemap

Axtarış motorları üçün XML sitemap generasiyası.

### Endpoint-lər
| URL | Təsvir |
|---|---|
| `/sitemap.xml` | Master sitemap index (bütün dilləri əhatə edir) |
| `/{locale}/sitemap.xml` | Dilə görə single sitemap |

### Daxil edilən məlumatlar
- **Ana səhifə**: hər dil üçün priority=1.00, changefreq=daily
- **Menyular**: aktiv menyuların URL-ləri, priority=0.80, changefreq=weekly
- **Bloqlar**: `Blog::getCachedAll()` ilə bütün aktiv bloqlar, priority=0.80, changefreq=weekly

### Yeni model əlavə qaydası
1. Modelə `getCachedAll()` və `getSingleUrlAttribute()` əlavə et
2. `SitemapController::single()` metoduna yeni modeli əlavə et
3. `sitemap-single.blade.php` view-una yeni bölmə əlavə et

---

## 7. RSS Feed

RSS 2.0 formatında feed generasiyası.

### Endpoint-lər
| URL | Təsvir |
|---|---|
| `/rss-index.opml` | OPML index (bütün dillərin RSS-ləri) |
| `/{locale}/rss.xml` | Dilə görə RSS feed |

### Daxil edilən məlumatlar
- `Blog::getCachedAll()` ilə bütün aktiv bloqlar
- Hər blog: title, description, link, pubDate, guid

---

## 8. Dil sistemi

Çoxdilli sayt strukturu.

### İşləmə mexanizmi
- `Language` modeli → `languages` cədvəli (`is_active`, `sort_order`, `code`)
- `LanguageMiddleware` → URL-dən dil kodunu çıxarır → `app()->setLocale()` ilə tətbiq edir
- Default dil: `az` (URL-də prefix olmadan)
- Digər dillər: `/en/...`, `/ru/...` prefiksi ilə

### Dil dəyişdirmə
`Language::switchLanguage()` metodu cari URL-i digər dilə çevirir:
- `/az/bloqlar` → `/en/blogs` (slug tərcüməsi ilə)
- Ana səhifə: `/az` → `/en`

### Alternates (hreflang)
`AlternatesService::compose()` hər səhifə üçün bütün dillərdə alternate URL-lər generasiya edir:
```html
<link rel="alternate" hreflang="az" href="http://site.com/az/bloqlar" />
<link rel="alternate" hreflang="en" href="http://site.com/en/blogs" />
```

### Canonical
`site/inc/canonical.blade.php` → `<link rel="canonical" href="..." />`

### Tərcümə sistemi
- `Translation` trait → `field_translations` cədvəli (polimorfik)
- `$translatedAttributes = ['title', 'description', 'slug']` → modeldə tanımlanır
- `TranslationHelper::basic($model, $translations, $key)` → toplu tərcümə yaratma

---

## 9. Dinamik route sistemi

`routes/web.php` faylında bütün sayt route-ları avtomatik qeydə alınır.

### İşləmə sxemi
```
Hər aktiv dil üçün:
  1. Ana səhifə: /{locale} → HomeController@index
  2. Config route-lar: Menu::getRoutes() → config/site/menu_routes.php-dən controller tapılır
  3. 404 səhifə: /{locale}/404 → StaticPageController@fallback
  4. Catch-all: /{locale}/{slug} → DynamicContentController@index
```

### DynamicContentController + ContentResolver
Config-də qeydə alınmamış hər hansı `/{locale}/{slug}` sorğusu bu axınla həll olunur:

```
Sorğu: /az/veb-sayt-hazirlanmasinin-esas-merhelele
  │
  ▼
DynamicContentController::index('veb-sayt-hazirlanmasinin-esas-merhelele')
  │
  ├─ 1. FieldTranslation::getBySlug($slug)
  │     → field_translations cədvəlindən slug-ı tapır
  │     → Nəticə: model_type=App\Models\Site\Blog, model_id=5
  │
  ├─ 2. $data->model (morphTo relation)
  │     → Blog modeli yüklənir (id=5)
  │
  ├─ 3. ContentResolver::handle($model)
  │     → $model->controller property oxunur
  │     → BlogController instance yaradılır
  │     → BlogController::single($blog) çağırılır
  │
  └─ 4. Nəticə qaytarılır (blog detail view)
```

#### ContentResolver (`app/Services/Site/ContentResolver.php`)
```php
class ContentResolver
{
    public function handle($model)
    {
        $controller = $model->controller;           // Model-dən controller class-ı al
        $controllerInstance = app($controller);      // Laravel container ilə instance yarat
        return $controllerInstance->single($model);  // single() metodunu çağır
    }
}
```

#### Model tərəfdə controller tanıtma
Hər model öz detail səhifəsini hansı controller-in idarə edəcəyini bildirir:
```php
// App\Models\Site\Blog
class Blog extends BaseModel
{
    public $controller = \App\Http\Controllers\Site\BlogController::class;
    // ...
}
```

#### Controller tərəfdə single() metodu
```php
// App\Http\Controllers\Site\BlogController
public function single(Blog $blog)
{
    $this->check_item($blog);        // is_active yoxlanışı
    $blog->incrementViews();          // baxış sayğacı
    $this->meta_share($blog);         // meta data share (title, description)
    $this->setSchema("...", [...]);    // schema markup
    return view("site.pages.blog.single", compact("blog"));
}
```

### Yeni dinamik model əlavə qaydası
1. Modelə `public $controller = XXXController::class;` property əlavə et
2. Modelə `use MetaData, Translation;` trait-lərini əlavə et
3. `$translatedAttributes`-a `'slug'` daxil et
4. Controller-ə `single($model)` metodu yaz (meta_share, setSchema, view return)
5. Seeder-dən model+slug yarat → avtomatik `/{locale}/{slug}` ilə əlçatan olacaq

### Route prioritet sırası
1. `/{locale}` → ana səhifə (dəqiq uyğunluq)
2. `/{locale}/{config_slug}` → config route-lar (bloqlar, əlaqə)
3. `/{locale}/404` → 404 səhifə
4. `/{locale}/{slug}` → catch-all dinamik route

---

## 📜 Lisenziya

<!-- Bu layihə MIT lisenziyası ilə yayımlanır.   -->
<!-- © [Oruc Seyidov](https://github.com/orucseyidov) -->

Copyright © 2025 [Oruc Seyidov](https://github.com/orucseyidov). All rights reserved.

This software is proprietary and confidential. Unauthorized copying of this file, via any medium is strictly prohibited.

