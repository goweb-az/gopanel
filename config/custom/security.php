<?php

/*
|--------------------------------------------------------------------------
| Təhlükəsizlik siyasəti
|--------------------------------------------------------------------------
|
| Panelə giriş məhdudiyyətləri. Dəyərlər vergüllə ayrılmış siyahı kimi
| `.env`-dən gəlir - kodda hardcode edilmir.
|
*/

return [

    /*
     * IP məhdudiyyətinin ÜMUMİ açarı.
     * false → siyahılardan asılı olmayaraq yoxlama TAMAMİLƏ sönür.
     *
     * Niyə ayrıca açar: lokal/dev mühitdə siyahını boşaltmaq əvəzinə bir
     * dəyişənlə söndürmək daha təhlükəsizdir - canlıdakı siyahı toxunulmaz qalır.
     */
    'ip_restriction_enabled' => (bool) env('IP_RESTRICTION_ENABLED', false),

    /* GoPanel-ə girişə icazə verilən IP-lər: "1.2.3.4,5.6.7.8" */
    'allowed_ips' => env('ALLOWED_IPS', ''),

    /* Bloklanan domenlər/host-lar: "example.com,test.local" */
    'restricted_domains' => env('RESTRICTED_DOMAINS', ''),

    /*
     * Uğursuz giriş cəhdləri: neçə cəhddən sonra neçə dəqiqə bloklanır.
     * Laravel-in `RateLimiter`-i ilə işlədilir.
     */
    'login_throttle' => [
        'max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'decay_minutes' => (int) env('LOGIN_DECAY_MINUTES', 10),
    ],

    /*
     * Fayl yükləmə: icazə verilən uzantılar və maksimal ölçü (KB).
     * `svg` DİQQƏTLƏ - içində script daşıya bilir; lazım olmasa açılmır.
     */
    'uploads' => [
        'max_size_kb' => (int) env('UPLOAD_MAX_SIZE_KB', 5120),
        'images'      => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'documents'   => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv'],
    ],

    /*
     * `ip_restriction_enabled` / `allowed_ips` üçün real ziyarətçi IP-si.
     *
     * false (default) → `$request->ip()` (TrustProxies-dən keçmiş dəyər).
     * true → əvvəlcə `CF-Connecting-IP` oxunur. YALNIZ server Cloudflare
     * arxasındadırsa açılır: əks halda başlığı istənilən kəs saxtalaşdırıb
     * allowlist-i yan keçə bilər.
     */
    'trust_cf_header' => (bool) env('SECURITY_TRUST_CF_HEADER', false),

    /*
    |--------------------------------------------------------------------------
    | Admin PIN-i (həssas əməliyyatların ikinci təsdiqi)
    |--------------------------------------------------------------------------
    |
    | PIN şifrənin əvəzi deyil - giriş vermir, artıq daxil olmuş adminin
    | konkret addımını (backup endirmə, silmə) təsdiqləyir. Sessiya oğurlansa
    | da arxivi endirmək üçün PIN lazım olur.
    |
    | Default SÖNÜKDÜR. Açıq olanda da PIN təyin etməmiş admindən heç nə
    | soruşulmur - mexanizm mövcud axınları sındırmır, tədricən açılır.
    | Qorunan route-lara `gopanel.pin` middleware-i bağlanır (bax routes/gopanel.php).
    |
    */
    'admin_pin' => [
        'enabled' => (bool) env('ADMIN_PIN_ENABLED', false),

        'length' => [
            'min' => (int) env('ADMIN_PIN_MIN', 4),
            'max' => (int) env('ADMIN_PIN_MAX', 6),
        ],

        /* Bir dəfə təsdiqləndikdən sonra PIN neçə dəqiqə soruşulmur. */
        'ttl_minutes' => (int) env('ADMIN_PIN_TTL', 15),

        /* Ardıcıl neçə səhv cəhddən sonra neçə dəqiqəlik kilid. */
        'throttle' => [
            'attempts'      => (int) env('ADMIN_PIN_ATTEMPTS', 5),
            'decay_minutes' => (int) env('ADMIN_PIN_DECAY', 5),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Birdəfəlik e-poçt kodu (OTP) - `App\Services\Auth\OtpService`
    |--------------------------------------------------------------------------
    */
    'otp' => [
        /* Kodun ömrü (saniyə). */
        'ttl' => (int) env('OTP_TTL', 300),

        /* Təkrar göndərmə arasında minimum interval (saniyə) - spam/xərc qoruması. */
        'resend_cooldown' => (int) env('OTP_RESEND_COOLDOWN', 60),

        /* Səhv cəhd limiti - limitdən sonra kod ləğv edilir (brute-force). */
        'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),

        /* Rəqəm sayı (4-8). */
        'length' => (int) env('OTP_LENGTH', 6),

        /* OTP hadisələrinin fayl jurnalı kanalı (config/logging.php). */
        'log_channel' => env('OTP_LOG_CHANNEL', 'gopanel-auth'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sayt statusu (site_settings.site_status = 0) - HTTP səviyyəsində
    |--------------------------------------------------------------------------
    |
    | false (default) → köhnə davranış: layout «Sayt təmir edilir» blokunu
    | 200 statusu ilə göstərir.
    | true → `SiteStatusMiddleware` ziyarətçiyə 503 + Retry-After qaytarır
    | (axtarış sistemi təmir səhifəsini indeksləmir), Markdown/RSS kimi
    | layout-suz cavablar da bağlanır, panelə daxil olmuş admin saytı görür.
    |
    */
    'site_status' => [
        'enforce'     => (bool) env('SITE_STATUS_ENFORCE', false),
        'retry_after' => (int) env('SITE_STATUS_RETRY_AFTER', 3600),

        /* Sayt bağlı olanda da açıq qalan yollar (`$request->is()` sintaksisi). */
        'except' => array_values(array_filter(array_map('trim', explode(
            ',',
            (string) env('SITE_STATUS_EXCEPT', 'robots.txt,llms.txt,ads.txt')
        )))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dev domen qoruyucusu - `RestrictDevDomain` (qlobal middleware)
    |--------------------------------------------------------------------------
    |
    | Test/dev domenini (dev.example.az) yalnız icazəli IP-lərə və ya gizli
    | açarla girənlərə açır, qalanlarını canlı sayta yönləndirir. Qorunan
    | domenin HƏR cavabı `X-Robots-Tag: noindex` alır, robots.txt isə
    | «Disallow: /» qaytarır - dev nüsxə axtarışda canlı ilə rəqabət aparmır.
    |
    | Default SÖNÜKDÜR - `DEV_GUARD_DOMAINS` boşdursa da heç nə etmir.
    |
    */
    'dev_guard' => [
        'enabled'         => (bool) env('DEV_GUARD_ENABLED', false),
        'domains'         => env('DEV_GUARD_DOMAINS', ''),
        'allowed_ips'     => env('DEV_GUARD_ALLOWED_IPS', ''),

        /* `?dev_key=<açar>` → 30 günlük cookie; `?dev_key=off` → çıxış. Boş → açar sönük. */
        'unlock_key'      => env('DEV_GUARD_UNLOCK_KEY', ''),

        /* Boşdursa yönləndirmə əvəzinə 403 qaytarılır. */
        'redirect_to'     => env('DEV_GUARD_REDIRECT_TO', ''),
        'redirect_status' => (int) env('DEV_GUARD_REDIRECT_STATUS', 302),
        'keep_path'       => (bool) env('DEV_GUARD_KEEP_PATH', true),
    ],

];
