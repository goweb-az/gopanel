# GoPanel — Database Architecture

Bu sənəd `gopanel` layihəsinin tam database arxitekturasını təsvir edir. Sıralama:

1. [Migrationlar (cədvəl strukturu)](#1-migrationlar-cədvəl-strukturu)
2. [Modellər (Eloquent təmsilləri)](#2-modellər-eloquent-təmsilləri)
3. [Modellər arası əlaqələr (Relationships)](#3-modellər-arası-əlaqələr-relationships)
4. [Traits (model davranış qarışıqları)](#4-traits-model-davranış-qarışıqları)
5. [Polimorfik strukturlar (translations / meta / activity log / menu)](#5-polimorfik-strukturlar)
6. [Cache strategiyası və avtomatik invalidasiya](#6-cache-strategiyası-və-avtomatik-invalidasiya)
7. [Diaqram (ER xülasəsi)](#7-diaqram-er-xülasəsi)

> Qeyd: Layihə **Laravel** + **Spatie Permission** + **Spatie Activity Log** + **Sanctum** + **Custom Translation/Meta sistemi** üzərində qurulub. Saytın çox dilli işləməsi `field_translations` (model tərcümələri) və `translations` (sistem dilləri) cədvəlləri ilə təmin olunur.

---

## 1. Migrationlar (cədvəl strukturu)

Migrationlar timestamp sırasına görə qruplanıb (Geography → Auth → İçərik → Permission → Navigation → SEO → Analytics → Catalog).

### 1.1 Geography

#### `countries`
| Sütun | Tip | Qeyd |
|---|---|---|
| `id` | bigint PK | |
| `code` | char(5) | not null |
| `name` | varchar(100) | not null |
| `phone` | int | nullable |
| `symbol` | varchar(10) | nullable |
| `capital` | varchar(80) | nullable |
| `currency` | char(5) | nullable |
| `continent` | varchar(30) | nullable |
| `continent_code` | char(50) | nullable |
| `alpha_3` | char(3) | nullable |
| `is_active` | bool | default `true` |
| timestamps + softDeletes | | |

#### `states`
- `id`, `country_id` (FK → `countries.id`, **cascade**), `state_name`, `state_code`, timestamps, softDeletes.

#### `cities` (yeni — 2026_04_27)
- `id`, `country_id` (FK → `countries.id`, **cascade**), `name`, `district`, `state`, `postal_code`, `latitude`, `longitude`, `population`, `area`, `is_active`, timestamps, softDeletes.
- Unikal: `[country_id, name]`. İndeks: `[country_id, is_active]`.
- Migration `Schema::hasTable('cities')` yoxlaması ilə qorunur.

#### `languages`
- `id`, `country_id` (FK → `countries.id`, **cascade**), `code` (varchar 10), `name`, `default` (bool), `is_active`, `is_show`, `sort_order`, timestamps, softDeletes.

#### `currency`
- `id`, `country_id` (FK → `countries.id`, **cascade**), `code`, `name`, `symbol`, timestamps, softDeletes.

---

### 1.2 Auth & Identity

#### `users` (frontend istifadəçiləri)
- `id`, `name`, `email` (unique), `email_verified_at`, `password`, `remember_token`, timestamps.

#### `admins` (panel istifadəçiləri)
- `id`, `uid` (uuid, unique, default `UUID()`), `full_name`, `email` (unique), `email_verified_at`, `password`, `is_active` (default true), `is_super` (default false), `image`, `remember_token`, timestamps, softDeletes.
- Migration `up()` bitəndə **2 default admin** yaradılır:
  - `admin@gmail.com` / `12345` — Super Admin
  - `test@gmail.com` / `12345` — Adi admin

#### `password_reset_tokens`
- `email` (PK), `token`, `created_at`.

#### `failed_jobs`
- `id`, `uuid` (unique), `connection`, `queue`, `payload`, `exception`, `failed_at`.

#### `personal_access_tokens` (Sanctum)
- `id`, `tokenable_type` + `tokenable_id` (morphs), `name`, `token` (unique), `abilities`, `last_used_at`, `expires_at`, timestamps.

---

### 1.3 Spatie Permission

`config/permission.php`-dən gələn cədvəl adlarına əsasən yaradılır.

#### `permissions`
- `id`, `name`, `guard_name`, **`title` (sonradan əlavə)**, **`group` (sonradan əlavə)**, timestamps. Unikal: `[name, guard_name]`.

#### `roles`
- `id`, (optional `team_foreign_key`), `name`, `guard_name`, timestamps. Unikal `[name, guard_name]` (və ya teams aktivdirsə teamlə birgə).

#### `model_has_permissions`, `model_has_roles`, `role_has_permissions`
- Standart Spatie pivot strukturu. Composite primary keys, FK-lar `permissions` və `roles`-a `cascade` ilə bağlıdır.

> Sonradan gələn migration `2025_06_25_171734_add_group_and_title_to_permissions_table.php` `permissions` cədvəlinə `title` və `group` sütunları əlavə edir (UI üçün qruplaşdırma və başlıq).

---

### 1.4 Tərcümə Sistemi

#### `translations` (sistem dil sətirləri — `__()` üçün)
- `id`, `locale`, `key`, `value` (text, nullable), `platform` (char(15), default `website`), `filename` (nullable), `group` (nullable), timestamps, softDeletes.
- Unikal: `[key, locale, platform]`.

#### `field_translations` (model atributlarının tərcüməsi — polimorfik)
- `id`, `model_type`, `model_id`, `locale` (index), `key`, `value` (text), timestamps, softDeletes.
- Unikal: `[model_type, model_id, locale, key]`.

> Bu **iki müxtəlif məqsəd** üçün cədvəldir: `translations` PHP/JSON dil fayllarına yazılan global açar/dəyər lüğətidir. `field_translations` isə hər model qeydinin (məs. Blog, Service) hər dildə fərdi `title`, `description`, `slug` saxladığı yerdir.

---

### 1.5 İçərik (Site)

#### `blogs`
- `id`, `views` (int, default 0), `is_active` (default true), `image` (nullable), `date_time` (timestamp), timestamps, softDeletes.
- Mətn sahələri (`title`, `description`, `slug`) `field_translations`-də saxlanır.

#### `sliders`
- `id`, `link`, `is_active`, `sort_order` (default 0), `image`, timestamps, softDeletes.
- Tərcümə: `title`, `description`, `link_title`.

#### `about_us`
- `id`, `image` (nullable), timestamps. (softDelete yoxdur)
- Tərcümə: `title`, `description`.

#### `services`
- `id`, `sort_order` (default 0), `icon_type` (default `font`), `icon`, `image`, timestamps, softDeletes.
- `2026_03_10_000009`: `icon_type` sütunu idempotent şəkildə əlavə olunub (sənədli double-add qoruması ilə).
- Tərcümə: `title`, `short_description`, `description`.

#### `products` (yeni — 2026_05_02)
- `id`, `uid` (uuid unique default `UUID()`), `price` (decimal(12,2) default 0), `discount` (decimal(12,2) nullable), `image`, `is_active` (index), timestamps, softDeletes.
- Tərcümə: `title`, `short_description`, `description`, `slug`.

---

### 1.6 Settings & Contact

#### `site_settings`
- `id`, `site_status`, `login_status`, `register_status`, `payment_status`, `site_redirect_status`, `site_analytics`, `block_bad_bots` (hamısı bool), `logo_light`, `logo_dark`, `mail_logo`, `gopanel_logo`, timestamps, softDeletes.

#### `contact_info`
- `id`, `phone`, `mobile`, `whatsapp`, `support_whatsapp`, `sales_whatsapp`, `info_email`, `support_email`, `map` (text), timestamps, softDeletes.
- Tərcümə: `page_title`, `page_description`, `adress`.

#### `socials`
- `id`, `name`, `icon` (text), `icon_type` (string — enum PHP-də), `url`, `target_blank` (default true), `is_active` (default true), `sort_order` (default 0), timestamps, softDeletes.

---

### 1.7 Naviqasiya

#### `categories`
- `id`, `uid` (uuid unique), `parent_id` (FK → `categories.id`, **set null**), `icon`, `color` (varchar 20), `icon_type` (default `font`), `sort_order`, `is_active`, `show_in_home`, `show_in_menu`, `home_order`, timestamps, softDeletes.
- İndekslər: `parent_id`, `sort_order`, `show_in_home`, `show_in_menu`.
- Tərcümə: `name`, `description`, `slug`.

#### `menus`
- `id`, `parent_id` (self-FK, **null on delete**), `type` (string — `route|static|functional|dynamic`), `position` (`header|footer`), `route_name`, `function_name`, **`menuable_type` + `menuable_id`** (nullableMorphs), `sort_order`, `is_active`, `is_dropdown`, timestamps, softDeletes.
- Tərcümə: `title`, `description`, `slug`.

---

### 1.8 SEO

#### `page_meta_data`
- `id`, `model_type` (nullable), `model_id` (nullable), `source`, `locale` (default `az`), `title`, `description`, `keywords` (text), `image`, timestamps, softDeletes.
- İndekslər: `[model_type, model_id]`, `[locale]`.

#### `llms_txts`
- `id`, `content` (longText, nullable), timestamps. (softDelete yoxdur)

#### `seo_analytics`
- `id`, `head` (text), `body`, `footer`, `robots_txt`, `ai_txt`, `other` (hamısı nullable text), timestamps, softDeletes.

#### `site_redirects`
- `id`, `locale` (varchar 8, nullable, index), `source` (varchar 2048, index), `match_type` (enum: `RedirectMatchTypeEnum::values()`), `regex_flags` (varchar 8, nullable), `target` (varchar 2048, nullable), `http_code` (smallInt default 301), `is_active`, `priority` (smallInt default 0, index), `starts_at`, `ends_at`, `hits` (uBigInt default 0), `last_hit_at`, `notes` (varchar 500), timestamps, softDeletes.
- Composite index: `[is_active, locale, match_type, priority]`.

---

### 1.9 Activity Logging

#### `activity_log` (Spatie)
- `id`, `log_name`, `description` (text), `subject_type` + `subject_id` (nullable morphs), `event`, `causer_type` + `causer_id` (nullable morphs), `properties` (json), `batch_uuid` (uuid, nullable), timestamps. İndeks `log_name`.

#### `file_logs`
- `id`, `admin_id` (FK → `admins.id`, **set null**), `user_id` (FK → `users.id`, **set null**), `channel`, `level`, `message` (text), `context` (json), `log_details` (json), timestamps. (softDelete yoxdur)

---

### 1.10 Analytics (öz analitika modulu)

#### `analytics_links`
- `id`, `locale` (varchar 5), `url` (text), `slug` (varchar 255, nullable), `hit_count`, `first_visited_at`, `last_visited_at`, timestamps. Unikal: `[locale, slug]`.

#### `analytics_devices` / `analytics_browsers` / `analytics_operating_systems`
- Eyni sxem: `id`, `device_type`/`name`, `icon`, `hit_count`, `first_visited_at`, `last_visited_at`, timestamps. Hər biri unikal sahəyə (`device_type`, `name`) malikdir.

#### `analytics_countries`
- `id`, `name`, `iso_code` (unikal), `flag`, `hit_count`, `first_visited_at`, `last_visited_at`, timestamps.

#### `analytics_cities`
- `id`, `country_id` (FK → `analytics_countries.id`, **cascade**), `name`, `hit_count`, `first_visited_at`, `last_visited_at`, timestamps.

#### `analytics_languages`
- `id`, `code` (unikal), `name`, `flag`, `hit_count`, `first_visited_at`, `last_visited_at`, timestamps.

#### `analytics_ad_platforms`
- `id`, `name`, `slug` (unikal), `logo`, `hit_count`, `first_visited_at`, `last_visited_at`, timestamps.

#### `analytics_clicks` (mərkəzi fakt cədvəli)
- `id`, `link_id` (FK cascade), `device_id` (set null), `os_id` (set null), `browser_id` (set null), `country_id` (set null), `city_id` (set null), `language_id` (set null), `ip_address` (varchar 45), `latitude`, `longitude`, `isp`, `url`, `referer` (text), timestamps.

#### `analytics_utm_parameters`
- `id`, `click_id` (FK → `analytics_clicks.id`, **cascade**), `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`, timestamps.

#### `analytics_ad_platform_data`
- `id`, `click_id` (FK cascade), `platform_id` (FK → `analytics_ad_platforms.id`, **cascade**), `param_key`, `param_value`, timestamps.

#### `analytics_event_logs`
- `id`, `click_id` (FK cascade), `event_type`, `event_value`, timestamps.

---

## 2. Modellər (Eloquent təmsilləri)

### 2.1 `App\Models\BaseModel`
Demək olar ki, bütün domen modelləri bunu extend edir.

```12:43:app/Models/BaseModel.php
class BaseModel extends Model
{
    use HasRouteKey;
    use HasFiles;
    use LogsAdminActivity;
    use Cacheable;

    protected $logEnabled = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (in_array('order', $model->getFillable())) {
                $maxOrder = static::max('order');
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function getModelClass()
    {
        return get_class($this);
    }

    public function incrementViews()
    {
        $this->increment('views');
        $this->save();
    }
}
```

Daxili olaraq qoşulur:
- `HasRouteKey` — `uid` varsa onu, yoxsa `id`-ni route key kimi istifadə edir.
- `HasFiles` — `_url` və `_view` virtual atributları (`image_url`, `image_view`).
- `LogsAdminActivity` — Spatie Activity Log + auth (`gopanel`/`web`) ilə inteqrasiya. `logEnabled = false` defaultdur.
- `Cacheable` — `getCachedAll/Forever/First/By` + auto flush.

### 2.2 Modellərin domen üzrə xəritəsi

| Domen | Cədvəl | Model | BaseModel? | Əlavə Trait/Use |
|---|---|---|---|---|
| Auth | `users` | `App\Models\User\User` | yox (Authenticatable) | `LogsActivity`, `HasApiTokens` |
| Auth | `admins` | `App\Models\Gopanel\Admin` | yox (Authenticatable) | `AddUuid`, `HasRouteKey`, `HasRoles`, `LogsActivity`, `SoftDeletes` |
| Auth | `roles` | `App\Models\Gopanel\CustomRole` extends `Spatie\...\Role` | – | `HasRouteKey`, `LogsActivity` |
| Auth | `permissions` | `App\Models\Gopanel\CustomPermission` extends `Spatie\...\Permission` | – | `LogsActivity` |
| Geography | `countries` | `Country` | bəli | – |
| Geography | `states` | `State` | bəli | – |
| Geography | `cities` | `City` | bəli | `SoftDeletes` |
| Geography | `languages` | `Language` | bəli | `AddUuid`, `SoftDeletes` |
| Geography | `currency` | `Currency` | bəli | `SoftDeletes` |
| Translations | `translations` | `Translations\Translation` | bəli | `UiElements`, `SoftDeletes` |
| Translations | `field_translations` | `Translations\FieldTranslation` | bəli | `SoftDeletes` |
| Site | `blogs` | `Site\Blog` | bəli | `Translation`, `MetaData`, `SoftDeletes` |
| Site | `sliders` | `Site\Slider` | bəli | `Translation`, `SoftDeletes` |
| Site | `about_us` | `Site\AboutUs` | bəli | `Translation`, `MetaData` |
| Site | `services` | `Site\Service` | bəli | `Translation`, `MetaData`, `SoftDeletes` |
| Site | `products` | `Site\Product` | bəli | `Translation`, `MetaData`, `AddUuid`, `SoftDeletes` |
| Settings | `site_settings` | `Settings\SiteSetting` | bəli | `MetaData`, `SoftDeletes` |
| Contact | `contact_info` | `Contact\ContactInfo` | bəli | `Translation`, `SoftDeletes` |
| Contact | `socials` | `Contact\Social` | bəli | `SoftDeletes` |
| Navigation | `categories` | `Navigation\Category` | bəli | `Translation`, `MetaData`, `AddUuid`, `SoftDeletes` |
| Navigation | `menus` | `Navigation\Menu` | bəli | `Translation`, `MetaData`, `SoftDeletes` |
| SEO | `page_meta_data` | `Seo\PageMetaData` | bəli | `SoftDeletes` |
| SEO | `llms_txts` | `Seo\LlmsTxt` | bəli | – |
| SEO | `seo_analytics` | `Seo\SeoAnalytics` | bəli | – |
| SEO | `site_redirects` | `Seo\SiteRedirect` | bəli | – |
| Activity | `activity_log` | `Activity\Activity` extends `Spatie\...\Activity` | – | – |
| Activity | `file_logs` | `Activity\FileLog` | bəli | – |
| Activity | (köhnə) | `Models\DataUpdate` | – | (legacy, `companies` modeli olmadığına görə işlək deyil) |
| Analytics | `analytics_*` | `Analytics\AnalyticsLink/Click/Device/...` | bəli | – |

> **Qeyd**: `app/Models/BaseModel.php.bak.php` köhnə yedək faylıdır, kodda istifadə olunmur.

### 2.3 Bəzi modellərin xüsusi davranışları

- **`Translation` modeli** (`translations` cədvəli): `saved` event-ində `resources/lang/{locale}/{filename}.php` və `resources/lang-json/{locale}/{filename}.json` fayllarına avtomatik yazılır; `deleted`-də həmin fayllardan açar silinir. Boş `platform` → `TranslationPlatfroms::WEBSITE`.
- **`FieldTranslation`**: `creating` zamanı `key === 'slug'` olduqda `Str::slug` ilə **avtomatik unikal slug** generasiyası (eyni `model_type` + `locale` daxilində duplikat yoxlamaqla `name-1`, `name-2` …).
- **`Language`**: `saved/deleted` → `site_languages` cache-i flush. `getDefault()`, `ensureSingleDefault()`, `ensureFallbackDefault()`, `getActiveCodesForRouteRegex()`, `switchLanguage()` köməkçi metodları.
- **`Slider` / `Service` / `Menu`**: `creating` zamanı `sort_order` avtomatik `max+1` olur.
- **`Menu`**: `addGlobalScope('sort_order')` ilə həmişə sıralı qaytarılır. `route_slug`/`route_title`/`route_description` virtual sütunları `getRoutes($locale)` join sorğusu ilə dolur.
- **`SiteRedirect`**: `scopeActive`, `scopeForLocale`, `matches($path, $locale)` (exact/prefix/contains/regex) və `registerHit()`.
- **`Admin`**: `getRoleSummaryAttribute`, `getPermissionSummaryAttribute`, `getAvatarUrlAttribute` (UI Avatar fallback). `getTotalPermissionsCount` permanent cache (`gopanel_permissions_total_count`).

---

## 3. Modellər arası əlaqələr (Relationships)

### 3.1 Geography
- `Country` `hasMany` `City`
- `State` `belongsTo` `Country`
- `City` `belongsTo` `Country`
- `Language` `belongsTo` `Country`
- `Currency` `belongsTo` `Country`

> **Diqqət**: `states` cədvəli mövcuddur, lakin `Country` modelində `hasMany(State::class)` əlaqəsi tərif edilməyib (yalnız `cities()` var). Ehtiyac olarsa əlavə edilməlidir.

### 3.2 Auth & Permission
- `Admin` ilə `CustomRole` arası — Spatie `HasRoles` (pivot `model_has_roles`).
- `CustomRole` ilə `CustomPermission` arası — Spatie (pivot `role_has_permissions`).
- `Admin` ilə `CustomPermission` arası — Spatie (pivot `model_has_permissions`).

### 3.3 Translations (polimorfik)
Hər model `Translation` trait vasitəsilə:

```27:30:app/Traits/Content/Translation.php
public function translations()
{
    return $this->morphMany(FieldTranslation::class, 'model');
}
```

İstifadə edənlər: `Blog`, `Slider`, `AboutUs`, `Service`, `Product`, `Category`, `Menu`, `ContactInfo`.

### 3.4 SEO Meta (polimorfik)
`MetaData` trait `PageMetaData` ilə morph-One/morph-Many qurur:

```17:28:app/Traits/Content/MetaData.php
public function meta($locale = null)
{
    $locale = $locale ?? app()->getLocale();
    return $this->morphOne(PageMetaData::class, 'model')->where('locale', $locale);
}

public function metaAll()
{
    return $this->morphMany(PageMetaData::class, 'model');
}
```

İstifadə edənlər: `Blog`, `AboutUs`, `Service`, `Product`, `Category`, `Menu`, `SiteSetting`.

### 3.5 Navigation
- `Category` self-referential: `parent` (`belongsTo Category`), `children` (`hasMany`), `childrenRecursive`.
- `Category` ↔ `News` (`belongsToMany News::class, 'news_categories'`) — **lakin `News` modeli/migrationu hazırda repoda yoxdur**, bu əlaqə legacy/futuristikdir.
- `Menu` self-referential `parent`/`children`.
- `Menu` polimorfik `menuable()` (`morphTo`) — istənilən modeli menyu elementi kimi əlavə etmək.

### 3.6 Activity & FileLog
- `Activity` (Spatie): `causer()` `morphTo`, `subject()` `morphTo`.
- `LogsAdminActivity` trait `activities()` `morphMany(Activity::class, 'causer')` təmin edir → bütün BaseModel-lərdə mövcuddur.
- `FileLog` `belongsTo Admin` (`admin_id`) və `belongsTo User` (`user_id`).

### 3.7 Analytics
Mərkəz: `AnalyticsClick`.

- `AnalyticsLink` `hasMany` `AnalyticsClick` (`link_id`).
- `AnalyticsClick`:
  - `belongsTo` `AnalyticsLink` (`link_id`)
  - `belongsTo` `AnalyticsDevice` (`device_id`)
  - `belongsTo` `AnalyticsOperatingSystem` (`os_id`)
  - `belongsTo` `AnalyticsBrowser` (`browser_id`)
  - `belongsTo` `AnalyticsCountry` (`country_id`)
  - `belongsTo` `AnalyticsCity` (`city_id`)
  - `belongsTo` `AnalyticsLanguage` (`language_id`)
  - `hasOne` `AnalyticsUtmParameter` (`click_id`)
  - `hasMany` `AnalyticsAdPlatformData` (`click_id`)
  - `hasMany` `AnalyticsEventLog` (`click_id`)
- `AnalyticsCountry` `hasMany` `AnalyticsCity` + `hasMany` `AnalyticsClick`.
- `AnalyticsCity` `belongsTo` `AnalyticsCountry` + `hasMany` `AnalyticsClick`.
- `AnalyticsDevice` / `Browser` / `OperatingSystem` / `Language` — hər biri `hasMany AnalyticsClick`.
- `AnalyticsAdPlatform` `hasMany` `AnalyticsAdPlatformData`.
- `AnalyticsUtmParameter` / `AnalyticsAdPlatformData` / `AnalyticsEventLog` `belongsTo AnalyticsClick`.
- `AnalyticsAdPlatformData` `belongsTo AnalyticsAdPlatform` (`platform_id`).

---

## 4. Traits (model davranış qarışıqları)

`app/Traits/` qovluğu üç alt qovluğa bölünüb: **System**, **Content**, **Activity**, **Ui**.

### 4.1 `Traits\System\HasRouteKey`
`getIdentifierIdAttribute` — `uid` fillable-da varsa onu, yoxsa `id`. `resolveByKey($value)` numeric/uuid avtomatik seçir.

### 4.2 `Traits\System\AddUuid`
`bootAddUuid` — `creating` zamanı boşdursa `Str::uuid()` ilə `uid` doldurur. `findByUid($uid)` yoxdursa 404.

### 4.3 `Traits\System\HasArchive`
Global scope `ArchivedScope` əlavə edir. `archive()` / `restoreFromArchive()` və `scopeArchived` / `scopeNotArchived`. Hələlik aktiv istifadəsi domen modellərində birbaşa görünmür (utility).

### 4.4 `Traits\System\Cacheable`
- `cacheKeyPrefix()` → `cache.{table}`
- `cacheTtl()` default 5 gün
- `getCachedAll($ttl)` → filterli list (is_active/status true, slug doluysa, `order` varsa ASC, yoxsa `id` DESC)
- `getCachedForever()` → eyni filtrlər forever
- `getCachedFirst($id)`, `getCachedBy($column, $value)`, `remember($suffix, $callback)`
- `flushCache()` — `all`, `forever`, `site_{table}` (geriyə uyğunluq) açarlarını silir.
- `bootCacheable()` — `saved`/`deleted`-də avtomatik flush.

### 4.5 `Traits\Content\HasFiles`
- `getAttribute` override edir:
  - `$model->image_url` → `getFileUrl('image')` (public path varsa `url(...)`, yoxsa raw value).
  - `$model->image_view` → `getFieldView('image')` (şəkil üçün lightbox `<a><img></a>`, başqa fayl üçün "Fayla bax" linki).
- `protected $files = ['image', ...]` məcburidir.

### 4.6 `Traits\Content\Translation`
- `bootTranslation` — `translatedAttributes` doluysa `with('translations')` global scope, model `deleting` olduqda translations də silinir.
- `translations()` `morphMany FieldTranslation`.
- `getTranslation($attribute, $locale, $fallback)` — `field_translations`-dən qiymət gətirir, yoxdursa fallback-a qayıdır.
- `translateOrDefault($locale)` — model klonu yaradır və bütün `translatedAttributes`-i o dilə görə doldurur.
- `__get` və `getAttribute` override — `$blog->title` avtomatik tərcümə sütununu çəkir.
- `toArray()` — `translatedAttributes`-i serializasiyaya əlavə edir.

### 4.7 `Traits\Content\MetaData`
- `bootMeta` — `deleting` zamanı `metaAll()->delete()`.
- `meta($locale)` `morphOne PageMetaData`, `metaAll()` `morphMany`.
- `getMeta($attribute, $locale)` köməkçi.
- `getMetaImage($locale)` — public path-da varsa `url`, yoxsa raw.

### 4.8 `Traits\Activity\LogsAdminActivity`
- Spatie `LogsActivity`-ni qanunlarına uyğun extend edir: `$logEnabled === false` olduqda log atılmır, `config('custom.activity_messages.{ModelName}')` yoxdursa da log atılmır.
- `tapActivity` ilə `causer` `gopanel`/`web` guard-larından çıxarılır, `ActivityLogHelper::resolveDescription` çağırılır.
- `activities()` `morphMany(Activity::class, 'causer')`.

### 4.9 `Traits\Ui\FormatsDate`
- `formatDate($key, $format)` — Carbon az lokal ilə.
- Avtomatik accessor-lar: `created_at_formatted`, `updated_at_formatted`, `started_at_formatted`, `finished_at_formatted`, `archived_at_formatted`, `startdate_formatted`, `enddate_formatted`.

### 4.10 `Traits\Ui\UiElements`
Panelə HTML elementləri verən accessor/metodlar:
- `check_inputs`, `star_icon`, `editBtn/deleteBtn/actions(...)`
- `status_badge`, `is_active_badge`, `is_current_badge`
- `double_click_edit($row)` — sahəni inline redaktə üçün hazırlayır (`gopanel.general.editable` route ilə).
- `is_active_btn(...)` və `toggle_btn(...)` — Bootstrap switch / switchbutton komponentləri (data-id, data-row, data-model, data-url ilə).

---

## 5. Polimorfik strukturlar

### 5.1 Tərcümə (`field_translations`)
| Sahə | Açıklama |
|---|---|
| `model_type` | `App\Models\Site\Blog` və s. |
| `model_id` | hədəf model-in id-si |
| `locale` | `az`, `en`, `ru` … |
| `key` | `title`, `description`, `slug`, … |
| `value` | mətn |

Unikal kombinasiya `[model_type, model_id, locale, key]`. Slug avtomatik unikal generasiya olunur (FieldTranslation::booted).

### 5.2 SEO Meta (`page_meta_data`)
Model + `locale` + `source` üzrə morph-One. `model_type`/`model_id` nullable olduğuna görə **standalone** (model olmayan) meta qeydləri də yaradıla bilər.

### 5.3 Activity Log (`activity_log`)
- `subject_*` morph: hansı model üzərində dəyişiklik olub.
- `causer_*` morph: kim etdi (Admin/User).
- `properties` json `old`/`attributes` saxlayır (Spatie).
- `LogsAdminActivity` trait causer-i `gopanel`/`web` guard-dan oturdur.

### 5.4 Menu (`menus.menuable_*`)
`menuable_type`/`menuable_id` nullable morphs — Menu istənilən modeli (məsələn `Category`, `Service`, `Product`) işarələyə bilir.

### 5.5 Personal Access Tokens
Sanctum `tokenable_*` morph — `User` və ya `Admin` token sahibi ola bilər.

---

## 6. Cache strategiyası və avtomatik invalidasiya

| Açar | Mənbə | TTL | Flush |
|---|---|---|---|
| `cache.{table}.all` / `.forever` | `Cacheable` trait | 5 gün / Forever | `saved`/`deleted` |
| `site_languages` (köhnə açar) | `Language::getCachedAll` | 5 gün | `Language` saved/deleted |
| `slug_{slug}_{locale}` | `FieldTranslation::getBySlug` | 30 gün | manual |
| `site_blog_{locale}_{slug}` | `Blog::getBySlug` | 5 gün | manual |
| `site_blogs_all_{locale}` | `Blog::getCachedAll` (override) | 5 gün | manual |
| `site_menu_view`, `site_menu_view_{locale}` | `Menu::getView/getSiteMenu` | 30 gün | manual |
| `site_menu_routes_newx_{locale}` | `Menu::getRoutes` | 30 gün | manual |
| `site_menu_by_slug_{locale}_{slug}` | `Menu::getBySlug` | 30 gün | manual |
| `site_menu_by_route_name_{locale}_{rn}` | `Menu::getByRouteName` | forever | manual |
| `site_field_translations_current_{code}_{slug}` | `Menu::getCurrentSlugData` | forever | manual |
| `site_settings{locale}` | `SiteSetting::getCached` | forever | manual |
| `contact_info` | `ContactInfo::getCached` | forever | manual |
| `social_links` | `Social::getCached` | forever | manual |
| `seo_analytics`, `llms_txt` | müvafiq modellər | forever | manual |
| `gopanel_permissions_total_count` | `Admin::getTotalPermissionsCount` | forever | manual |
| `admin_avatar_{id}` | `Admin::getAvatarUrlAttribute` | forever | manual |

> Forever cache-ləri əldə dəyişmə zamanı `Cache::forget(...)` ilə təmizləməyi unutmamaq lazımdır (`Cacheable` trait yalnız `cache.*` açarlarını avtomatik flush edir).

---

## 7. Diaqram (ER xülasəsi)

```text
                   ┌──────────────┐
                   │  countries   │◄────────┐
                   └──────┬───────┘         │
       ┌──────────────────┼──────────┬──────┼──────────────┐
       ▼                  ▼          ▼      ▼              ▼
   states           languages    currency  cities       (geo lookup)


   admins ── HasRoles ──► roles ──► permissions
      ▲                     ▲           ▲
      └─ model_has_roles    └─ role_has_permissions

   activity_log (subject morph + causer morph)
   file_logs ── admin_id / user_id


   FieldTranslation (model_type, model_id, locale, key, value)
       ▲ morphTo
       │
   ┌───┴────────────────────────────────────────────────┐
   │ Blog, Slider, AboutUs, Service, Product,           │
   │ Category, Menu, ContactInfo                        │
   └───────────────────────────────────────────────────-┘

   PageMetaData (model_type, model_id, locale)
       ▲ morphTo (eyni hədəflər + SiteSetting)

   Menu (parent/children + menuable morph)
   Category (parent/children)


                ┌───────────────────────┐
                │   analytics_clicks    │
                └───────┬───────────────┘
        ┌───────┬──────┼────────┬───────┬───────┐
        ▼       ▼      ▼        ▼       ▼       ▼
     links  devices  os    browsers  countries  languages
                                       │
                                     cities

     analytics_clicks ─► utm_parameters (1:1)
                       ─► ad_platform_data ──► ad_platforms (n:1)
                       ─► event_logs (1:n)
```

---

## Əlavə qeydlər və potensial təkmilləşmələr

1. `Country` modelinə `hasMany(State::class)` və `hasMany(Language::class)` əlaqələri əlavə edilə bilər — migration var, model yox.
2. `app/Models/BaseModel.php.bak.php` faylı silinə bilər (yedək, kodda istifadə olunmur).
3. `Category::news()` `News` modeli yoxdur — istifadəyə qədər comment-out / silinmə düşünülə bilər.
4. `Models\DataUpdate` legacy modeldir (`Company` yoxdur), sistemə nə qoşulur?
5. `analytics_links.url` `text` tipidir, lakin `slug` `varchar(255)` ilə birgə unikal — uzun URL-lər üçün uyğundur, lakin index-lənmir (axtarışda `slug` istifadə olunur).
6. `field_translations.value` `text`-dir — hər dəfə `with('translations')` global scope eager load edilir (`Translation` trait), bəzi siyahı sorğularında yük yarada bilər; lazımsız hallarda `withoutGlobalScope` ilə optimallaşdırıla bilər.
7. Spatie Permission cache açarı `config('permission.cache.key')` migration sonu flush olunur — istehsalda `php artisan permission:cache-reset` çağırmaq tövsiyə olunur.
