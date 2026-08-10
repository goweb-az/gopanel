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

];
