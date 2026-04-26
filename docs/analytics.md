# Analytics

The analytics module records site visits and powers the Gopanel analytics dashboard.

## Flow

```text
TrackAnalyticsMiddleware
  -> ClickRegistered event
  -> RegisterClickListener
  -> AnalyticsService
  -> AnalyticsRepository
  -> analytics_* tables
```

Important files:

```text
app/Http/Middleware/Seo/TrackAnalyticsMiddleware.php
app/Events/Analytics/ClickRegistered.php
app/Listeners/Analytics/RegisterClickListener.php
app/Services/Site/Seo/AnalyticsService.php
app/Repositories/AnalyticsRepository.php
config/seo/analytics.php
resources/views/gopanel/pages/analytics
public/assets/gopanel/js/modules/analytics.js
```

## Dashboard Sections

- Summary cards
- Countries map
- Cities traffic
- Browser traffic
- Device traffic
- Languages
- Operating systems
- Ad platform performance
- UTM parameters
- Latest clicks
- Top links

Several dashboard widgets support fullscreen mode through the shared frontend helpers in `analytics.js`.

## User Agent Detection

Browsers, OS, and devices are detected from `config/seo/analytics.php`.

Order matters. More specific browser keywords should come before general keywords. For example, `Edge`, `Opera`, `SamsungBrowser`, and `Brave` must be checked before `Chrome`, because their user agents often contain the word `Chrome`.

## Ad Platforms

Ad platform detection uses query parameters from `config/seo/analytics.php`:

```php
'gclid' => 'Google Ads',
'fbclid' => 'Facebook Ads',
'msclkid' => 'Microsoft Bing Ads',
```

Detected platform data is stored in:

```text
analytics_ad_platforms
analytics_ad_platform_data
```

## Demo Analytics Data

Use the mock seeder:

```bash
php artisan mock:seed
```

Choose `Analitika`, or choose `0 Hamisi`.

The mock analytics seeder uses the real `AnalyticsService`, so it tests the same registration path used by production tracking.

## GeoIP

Country and city lookup depends on local MaxMind GeoLite2 database files. See [GeoIP](geoip.md).

