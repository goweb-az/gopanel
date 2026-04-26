<?php

/*
|--------------------------------------------------------------------------
| Analytics Konfiqurasiyası
|--------------------------------------------------------------------------
|
| Bu fayl saytın analitika sisteminin bütün konfiqurasiya parametrlərini
| təyin edir. Analytics sistemi TrackAnalyticsMiddleware tərəfindən
| trigger olunur və ClickRegistered eventi vasitəsilə məlumatları
| qeyd edir.
|
| İSTİFADƏ QAYDALARI:
|
| 1. AKTİVLƏŞDİRMƏ:
|    - Gopanel > Tənzimləmələr > Sayt Tənzimləmələri bölməsindən
|      "Analitika" toggle-ını aktiv edin.
|    - Kernel.php-də route middleware olaraq əlavə edin:
|      Route::middleware(['track.analytics'])->group(function () { ... });
|
| 2. GeoIP DATABASELƏR:
|    - MaxMind GeoLite2 .mmdb faylları tələb olunur.
|    - Quraşdırma: database/geo-data/ qovluğuna yerləşdirin.
|    - Fayllar: GeoLite2-City.mmdb, GeoLite2-Country.mmdb, GeoLite2-ASN.mmdb
|    - MaxMind hesabı: https://www.maxmind.com/en/geolite2/signup
|
| 3. REKLAM PLATFORMALARI:
|    - 'ad_platforms' array-i URL query parametrlərindən reklam mənbəyini
|      müəyyən edir (gclid → Google Ads, fbclid → Facebook Ads və s.)
|    - Yeni platform əlavə etmək: 'param_key' => 'Platform Adı'
|
| 4. CİHAZ/BRAUZER/OS TƏYİNİ:
|    - User-Agent header-indəki keyword-lərə görə təyin olunur.
|    - Yeni cihaz/brauzer əlavə etmək: array-ə keyword və logo əlavə edin.
|    - Sıralama vacibdir: daha spesifik keyword-lər yuxarıda olmalıdır.
|
| 5. MİGRASİYALAR:
|    - php artisan migrate əmri ilə cədvəllər yaradılır.
|    - Əsas cədvəllər: analytics_clicks, analytics_countries, analytics_cities,
|      analytics_devices, analytics_browsers, analytics_links və s.
|
*/

return [

    'geoip' => [
        'default' => [
            'country' => 'Azerbaijan',
            'code'    => 'AZ',
            'city'    => 'Baku',
        ],
        'paths' => [
            'city'    => database_path('geo-data/GeoLite2-City/GeoLite2-City.mmdb'),
            'country' => database_path('geo-data/GeoLite2-Country/GeoLite2-Country.mmdb'),
            'asn'     => database_path('geo-data/GeoLite2-ASN/GeoLite2-ASN.mmdb'),
        ],
    ],

    'devices' => [
        'Mobile' => [
            'keywords' => ['mobile', 'phone', 'android', 'iphone'],
            'icon'     => 'fa-solid fa-mobile-screen-button',
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/3/3c/Font_Awesome_5_solid_mobile-alt.svg',
        ],
        'Tablet' => [
            'keywords' => ['tablet', 'ipad'],
            'icon'     => 'fa-solid fa-tablet-screen-button',
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/3/3e/Font_Awesome_5_solid_tablet-alt.svg',
        ],
        'Laptop' => [
            'keywords' => ['laptop', 'notebook', 'macbook'],
            'icon'     => 'fa-solid fa-laptop',
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/9/92/Font_Awesome_5_solid_laptop.svg',
        ],
        'Desktop' => [
            'keywords' => ['desktop', 'windows nt', 'macintosh', 'x11'],
            'icon'     => 'fa-solid fa-desktop',
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/6/6c/Font_Awesome_5_solid_desktop.svg',
        ],
        'TV' => [
            'keywords' => ['smart-tv', 'hbbtv', 'appletv'],
            'icon'     => 'fa-solid fa-tv',
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/f/f9/Font_Awesome_5_solid_tv.svg',
        ],
        'Console' => [
            'keywords' => ['playstation', 'xbox', 'nintendo'],
            'icon'     => 'fa-solid fa-gamepad',
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/3/32/Font_Awesome_5_solid_gamepad.svg',
        ],
        'Watch' => [
            'keywords' => ['watch', 'smartwatch', 'wear os'],
            'icon'     => 'fa-solid fa-clock',
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/8/8d/Font_Awesome_5_solid_clock.svg',
        ],
    ],


    'ad_platforms'  => [
        'gclid'             => 'Google Ads',
        'wbraid'            => 'Google Ads',
        'gbraid'            => 'Google Ads',
        'fbclid'            => 'Facebook Ads',
        'li_fat_id'         => 'LinkedIn Ads',
        'msclkid'           => 'Microsoft Bing Ads',
        'ttclid'            => 'TikTok Ads',
        'twclid'            => 'Twitter Ads',
        'pbc'               => 'Pinterest Ads',
        'epik'              => 'Pinterest Ads',
        'scid'              => 'Snapchat Ads',
        'tbclid'            => 'Taboola Ads',
        'obclid'            => 'Outbrain Ads',
        'yclid'             => 'Yandex Ads',
        'vkclid'            => 'VK Ads',
        'adgroup_id'        => 'Apple Search Ads',
        'campaign_id'       => 'Apple Search Ads',
        'keyword_id'        => 'Apple Search Ads',
    ],

    'ad_logos' => [
        'Google Ads'        => 'https://cdn.simpleicons.org/googleads/4285F4',
        'Facebook Ads'      => 'https://cdn.simpleicons.org/facebook/1877F2',
        'LinkedIn Ads'      => 'https://cdn.simpleicons.org/linkedin/0A66C2',
        'Microsoft Bing Ads' => 'https://cdn.simpleicons.org/microsoftbing/258FFA',
        'TikTok Ads'        => 'https://cdn.simpleicons.org/tiktok/000000',
        'Twitter Ads'       => 'https://cdn.simpleicons.org/x/000000',
        'Pinterest Ads'     => 'https://cdn.simpleicons.org/pinterest/BD081C',
        'Snapchat Ads'      => 'https://cdn.simpleicons.org/snapchat/FFFC00',
        'Taboola Ads'       => 'https://cdn.simpleicons.org/taboola/005A9C',
        'Outbrain Ads'      => 'https://cdn.simpleicons.org/outbrain/FF6F00',
        'Yandex Ads'        => 'https://cdn.simpleicons.org/yandex/FC3F1D',
        'VK Ads'            => 'https://cdn.simpleicons.org/vk/0077FF',
        'Apple Search Ads'  => 'https://cdn.simpleicons.org/apple/000000',
    ],


    'oses' => [
        'iOS' => [
            'keywords' => ['iPhone', 'iPad', 'iPod', 'iOS'],
            'logo'     => 'https://cdn.simpleicons.org/apple/000000',
        ],
        'Android' => [
            'keywords' => ['Android'],
            'logo'     => 'https://cdn.simpleicons.org/android/3DDC84',
        ],
        'Windows' => [
            'keywords' => ['Windows NT', 'Windows '],
            'logo'     => 'https://cdn.simpleicons.org/windows/0078D4',
        ],
        'macOS' => [
            'keywords' => ['Macintosh', 'Mac OS X', 'Mac OS'],
            'logo'     => 'https://cdn.simpleicons.org/apple/000000',
        ],
        'Chrome OS' => [
            'keywords' => ['CrOS'],
            'logo'     => 'https://cdn.simpleicons.org/googlechrome/4285F4',
        ],
        'Linux' => [
            'keywords' => ['X11; Linux', 'Linux'],
            'logo'     => 'https://cdn.simpleicons.org/linux/FCC624',
        ],
        'tvOS' => [
            'keywords' => ['AppleTV', 'tvOS'],
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/f/fa/Apple_logo_black.svg',
        ],
        'webOS' => [
            'keywords' => ['webOS', 'Web0S', 'LG Browser'],
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/2/22/LG_logo_%282015%29.svg',
        ],
        'Tizen' => [
            'keywords' => ['Tizen'],
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/3/35/Tizen_logo.svg',
        ],
        'Roku OS' => [
            'keywords' => ['Roku'],
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/5/5f/Roku_logo.svg',
        ],
        'HarmonyOS' => [
            'keywords' => ['HarmonyOS'],
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/1/16/HarmonyOS_logo.svg',
        ],
        'Fire OS' => [
            'keywords' => ['Silk/', 'AFT'],
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg',
        ],
        'KaiOS' => [
            'keywords' => ['KaiOS'],
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/2/21/KaiOS_logo.svg',
        ],
        'BlackBerry OS' => [
            'keywords' => ['BB10', 'BlackBerry'],
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/2/2e/BlackBerry_Logo_2019.png',
        ],
        'FreeBSD' => [
            'keywords' => ['FreeBSD'],
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/4/4f/Freebsd_logo.svg',
        ],
        'OpenBSD' => [
            'keywords' => ['OpenBSD'],
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/0/09/OpenBSD_Logo_-_Cartoon_Tukui.png',
        ],
        'NetBSD' => [
            'keywords' => ['NetBSD'],
            'logo'     => 'https://upload.wikimedia.org/wikipedia/commons/2/28/NetBSD.svg',
        ],
    ],

    'browsers' => [
        'Edge' => [
            'keywords' => ['Edg', 'Edge'],
            'logo'     => 'https://cdn.simpleicons.org/microsoftedge/0078D7',
        ],
        'Opera' => [
            'keywords' => ['OPR/', 'Opera'],
            'logo'     => 'https://cdn.simpleicons.org/opera/FF1B2D',
        ],
        'Brave' => [
            'keywords' => ['Brave'],
            'logo'     => 'https://cdn.simpleicons.org/brave/FB542B',
        ],
        'Vivaldi' => [
            'keywords' => ['Vivaldi'],
            'logo'     => 'https://cdn.simpleicons.org/vivaldi/EF3939',
        ],
        'Samsung Internet' => [
            'keywords' => ['SamsungBrowser'],
            'logo'     => 'https://cdn.simpleicons.org/samsunginternet/000000',
        ],
        'Firefox' => [
            'keywords' => ['Firefox', 'FxiOS'],
            'logo'     => 'https://cdn.simpleicons.org/firefoxbrowser/FF7139',
        ],
        'Chrome' => [
            'keywords' => ['CriOS', 'Chrome'],
            'logo'     => 'https://cdn.simpleicons.org/googlechrome/4285F4',
        ],
        'Safari' => [
            'keywords' => ['Safari'],
            'logo'     => 'https://cdn.simpleicons.org/safari/006CFF',
        ],
        'Other' => [
            'keywords' => [],
            'logo'     => null,
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Ölkə Koordinatları (Leaflet xəritə üçün)
    |--------------------------------------------------------------------------
    | ISO Alpha-2 kodu → lat/lng. Əsas ölkələr daxil edilib.
    */
    'country_coords' => [
        'AZ' => ['lat' => 40.4093, 'lng' => 49.8671],
        'TR' => ['lat' => 39.9334, 'lng' => 32.8597],
        'RU' => ['lat' => 55.7558, 'lng' => 37.6173],
        'US' => ['lat' => 38.9072, 'lng' => -77.0369],
        'GB' => ['lat' => 51.5074, 'lng' => -0.1278],
        'DE' => ['lat' => 52.5200, 'lng' => 13.4050],
        'FR' => ['lat' => 48.8566, 'lng' => 2.3522],
        'IT' => ['lat' => 41.9028, 'lng' => 12.4964],
        'ES' => ['lat' => 40.4168, 'lng' => -3.7038],
        'NL' => ['lat' => 52.3676, 'lng' => 4.9041],
        'UA' => ['lat' => 50.4501, 'lng' => 30.5234],
        'GE' => ['lat' => 41.7151, 'lng' => 44.8271],
        'KZ' => ['lat' => 51.1694, 'lng' => 71.4491],
        'UZ' => ['lat' => 41.2995, 'lng' => 69.2401],
        'IN' => ['lat' => 28.6139, 'lng' => 77.2090],
        'CN' => ['lat' => 39.9042, 'lng' => 116.4074],
        'JP' => ['lat' => 35.6762, 'lng' => 139.6503],
        'KR' => ['lat' => 37.5665, 'lng' => 126.9780],
        'BR' => ['lat' => -15.7975, 'lng' => -47.8919],
        'CA' => ['lat' => 45.4215, 'lng' => -75.6972],
        'AU' => ['lat' => -35.2809, 'lng' => 149.1300],
        'SA' => ['lat' => 24.7136, 'lng' => 46.6753],
        'AE' => ['lat' => 25.2048, 'lng' => 55.2708],
        'EG' => ['lat' => 30.0444, 'lng' => 31.2357],
        'IL' => ['lat' => 31.7683, 'lng' => 35.2137],
        'PL' => ['lat' => 52.2297, 'lng' => 21.0122],
        'SE' => ['lat' => 59.3293, 'lng' => 18.0686],
        'NO' => ['lat' => 59.9139, 'lng' => 10.7522],
        'FI' => ['lat' => 60.1699, 'lng' => 24.9384],
        'PT' => ['lat' => 38.7223, 'lng' => -9.1393],
        'GR' => ['lat' => 37.9838, 'lng' => 23.7275],
        'IR' => ['lat' => 35.6892, 'lng' => 51.3890],
        'PK' => ['lat' => 33.6844, 'lng' => 73.0479],
        'BD' => ['lat' => 23.8103, 'lng' => 90.4125],
        'NG' => ['lat' => 9.0579, 'lng' => 7.4951],
        'ZA' => ['lat' => -33.9249, 'lng' => 18.4241],
        'MX' => ['lat' => 19.4326, 'lng' => -99.1332],
        'AR' => ['lat' => -34.6037, 'lng' => -58.3816],
        'CL' => ['lat' => -33.4489, 'lng' => -70.6693],
        'TH' => ['lat' => 13.7563, 'lng' => 100.5018],
        'VN' => ['lat' => 21.0285, 'lng' => 105.8542],
        'ID' => ['lat' => -6.2088, 'lng' => 106.8456],
        'MY' => ['lat' => 3.1390, 'lng' => 101.6869],
        'SG' => ['lat' => 1.3521, 'lng' => 103.8198],
        'PH' => ['lat' => 14.5995, 'lng' => 120.9842],
        'AT' => ['lat' => 48.2082, 'lng' => 16.3738],
        'CH' => ['lat' => 46.9480, 'lng' => 7.4474],
        'BE' => ['lat' => 50.8503, 'lng' => 4.3517],
        'CZ' => ['lat' => 50.0755, 'lng' => 14.4378],
        'RO' => ['lat' => 44.4268, 'lng' => 26.1025],
        'HU' => ['lat' => 47.4979, 'lng' => 19.0402],
        'BG' => ['lat' => 42.6977, 'lng' => 23.3219],
        'DK' => ['lat' => 55.6761, 'lng' => 12.5683],
        'IE' => ['lat' => 53.3498, 'lng' => -6.2603],
        'HR' => ['lat' => 45.8150, 'lng' => 15.9819],
    ],

];
