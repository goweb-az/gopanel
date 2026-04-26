# Analytics & Bot Blocking Sistemi

## Ümumi Baxış

Bu modul Gopanel CRM üçün ətraflı **ziyarətçi analitikası** və **zərərli bot bloklama** imkanı təmin edir.

### Əsas Xüsusiyyətlər

- 📊 **Real-time analitika** — hər ziyarətçi kliki qeyd olunur
- 🌍 **GeoIP** — ölkə, şəhər, ISP təyinatı (MaxMind GeoLite2)
- 📱 **Cihaz/Brauzer/OS** təyinatı (User-Agent parsing)
- 🔗 **UTM parametr** izləmə
- 📢 **Reklam platforması** tanıma (Google Ads, Facebook, TikTok, LinkedIn və s.)
- 🤖 **Bot bloklaması** — JS cookie yoxlaması ilə

---

## Quraşdırma

### 1. Migrasiyalar

```bash
php artisan migrate
```

Bu əmr aşağıdakı cədvəlləri yaradır:

| Cədvəl | Təyinat |
|---|---|
| `analytics_links` | Ziyarət edilən URL-lər |
| `analytics_devices` | Cihaz növləri (Mobile, Desktop, Tablet...) |
| `analytics_countries` | Ölkələr |
| `analytics_cities` | Şəhərlər |
| `analytics_languages` | Dillər |
| `analytics_operating_systems` | Əməliyyat sistemləri |
| `analytics_browsers` | Brauzerlər |
| `analytics_ad_platforms` | Reklam platformaları |
| `analytics_clicks` | Hər bir klik qeydi |
| `analytics_utm_parameters` | UTM parametrləri |
| `analytics_ad_platform_data` | Reklam parametr detalları |
| `analytics_event_logs` | Event logları |

### 2. GeoIP Databaselər

MaxMind GeoLite2 `.mmdb` fayllarını əldə edin:
- Qeydiyyat: https://www.maxmind.com/en/geolite2/signup
- Faylları `database/geo-data/` qovluğuna yerləşdirin:

```
database/geo-data/
├── GeoLite2-City/GeoLite2-City.mmdb
├── GeoLite2-Country/GeoLite2-Country.mmdb
└── GeoLite2-ASN/GeoLite2-ASN.mmdb
```

### 3. İcazələr

```bash
php artisan db:seed --class=PermissionSeeder
```

### 4. Aktivləşdirmə

**Gopanel → Tənzimləmələr → Sayt Tənzimləmələri** bölməsindən:

| Toggle | Təyinat |
|---|---|
| **Yönləndirmələr** | SEO yönləndirmə qaydalarını aktiv/deaktiv edir |
| **Analitika** | Ziyarətçi izləmə sistemini aktivləşdirir |
| **Bot Bloklaması** | HumanGate JS cookie yoxlamasını aktivləşdirir |

---

## Arxitektura

### Middleware Zənciri

```
Request → HumanGate (bot yoxlaması) → SiteRedirect → TrackAnalytics → Response
```

### Analytics Data Flow

```
TrackAnalyticsMiddleware
    ├── SiteSetting::getCached()->site_analytics yoxlanılır
    ├── Request məlumatları hazırlanır (IP, UA, UTM, Ad Platform)
    └── ClickRegistered event trigger olunur
            └── RegisterClickListener
                    └── AnalyticsService::register()
                            ├── GeoIpHelper → ölkə/şəhər
                            ├── UA parsing → cihaz/brauzer/OS
                            ├── AnalyticsRepository → DB yazma
                            ├── UTM persist
                            ├── Ad Platform persist
                            └── Event log persist
```

### Fayl Strukturu

```
app/
├── Events/Analytics/
│   └── ClickRegistered.php          # Event class
├── Listeners/Analytics/
│   └── RegisterClickListener.php    # Listener (sync)
├── Helpers/Analytics/
│   ├── GeoIpHelper.php              # MaxMind GeoIP wrapper
│   └── TrackingHelper.php           # UA/dil normalizasiya
├── Http/
│   ├── Middleware/
│   │   ├── HumanGate.php            # Bot bloklaması
│   │   └── Seo/
│   │       ├── SiteRedirectMiddleware.php
│   │       └── TrackAnalyticsMiddleware.php
│   └── Controllers/Gopanel/Seo/
│       ├── AnalyticsController.php      # Dashboard API
│       └── AnalyticsDetailController.php # Detail səhifələri
├── Models/Analytics/                # 12 model
├── Repositories/
│   └── AnalyticsRepository.php      # DB əməliyyatları
├── Services/Site/Seo/
│   └── AnalyticsService.php         # Əsas biznes məntiqi
config/seo/
├── analytics.php                    # Analitika konfiqurasiyası
└── bots.php                         # Yaxşı botlar siyahısı
```

---

## Route Middleware İstifadəsi

Analytics middleware-i route-lara əlavə etmək üçün:

```php
// routes/web.php
Route::middleware(['track.analytics'])->group(function () {
    // analitika izlənəcək route-lar
});

// və ya tək route
Route::get('/haqqimizda', [PageController::class, 'about'])
    ->middleware('track.analytics');
```

> **Qeyd:** `site.redirects` middleware-i də eyni şəkildə istifadə olunur.

---

## Konfiqurasiya

### config/seo/analytics.php

| Bölmə | Təyinat |
|---|---|
| `geoip.paths` | MaxMind .mmdb fayl yolları |
| `geoip.default` | Private IP üçün default dəyərlər |
| `devices` | Cihaz növü tanıma (keyword + icon) |
| `ad_platforms` | URL param → platform xəritəsi |
| `ad_logos` | Platform adı → logo URL xəritəsi |
| `oses` | OS tanıma (keyword + logo) |
| `browsers` | Brauzer tanıma (keyword + logo) |

### config/seo/bots.php

| Bölmə | Təyinat |
|---|---|
| `good` | Bloklanmamalı olan bot siyahısı (Googlebot, Bingbot, Twitterbot...) |

---

## Bot Bloklaması Mexanizmi

1. `block_bad_bots` toggle aktiv olduqda `HumanGate` middleware işə düşür
2. API, storage, assets path-ləri avtomatik keçirilir
3. `good` siyahısındakı bot-lara (Googlebot, Bingbot...) icazə verilir
4. HTML GET request-lərdə `__hs` cookie yoxlanılır
5. Cookie yoxdursa → JS ilə cookie set edib reload edən səhifə göstərilir
6. Bot-lar JS icra edə bilmir → bloklanır

> ⚠️ Bu metod əsas surətçıxaran bot-ları dayandırır, amma tam təhlükəsizlik həlli deyil. CloudFlare/Captcha kimi xidmətlər daha güclüdür.

---

## Gopanel Menyu Strukturu

```
📊 Analitika
├── Dashboard         — ümumi statistika
├── Cihazlar          — mobile/desktop/tablet bölgüsü
├── ƏS-lər            — Windows/Android/iOS...
├── Brauzerlər        — Chrome/Safari/Firefox...
├── Ölkələr           — ölkə statistikası + xəritə
├── Şəhərlər          — şəhər statistikası
├── Dillər            — accept-language statistikası
├── Kliklər           — raw klik siyahısı
├── Linklər           — URL hit sayları
├── UTM Parametrlər   — kampaniya tracking
└── Reklam platformaları — Google Ads, Facebook Ads...
```

---

## Troubleshooting

| Problem | Həll |
|---|---|
| Analytics data yazılmır | `site_analytics` toggle-unu aktiv edin |
| GeoIP "Unknown" qaytarır | `.mmdb` fayllarının mövcudluğunu yoxlayın |
| Bot bloklaması işləmir | `block_bad_bots` toggle-unu aktiv edin |
| Cache problemləri | `php artisan cache:clear` və ya Gopanel "Keşi Təmizlə" |
| Event işləmir | `QUEUE_CONNECTION=sync` olduğundan əmin olun |
