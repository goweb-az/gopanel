<?php

/*
|--------------------------------------------------------------------------
| Mail brendi və növbə (queue) siyasəti
|--------------------------------------------------------------------------
|
| `App\Services\Mail\MailService` bu fayldan oxuyur. Bütün email şablonlarına
| ötürülən ORTAQ data (loqo, altlıq, əlaqə) burada TƏK yerdə saxlanılır -
| şablonların içində yazılmır.
|
| Layihədə tənzimləmə cədvəli varsa (`mail_settings`), dəyərləri kod tərəfindən
| `MailService::addData()` ilə üstələmək olar - config default rolunu oynayır.
|
*/

return [

    'branding' => [
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'title'        => env('MAIL_BRAND_TITLE', env('APP_NAME', 'Gopanel')),
        'footer_title' => env('MAIL_BRAND_FOOTER_TITLE', ''),
        'description'  => env('MAIL_BRAND_DESCRIPTION', ''),

        /*
         * Loqolar TAM URL olmalıdır - email klientləri nisbi yolu aça bilmir.
         * `App\Support\Url\CdnUrl::asset('assets/images/logo.png')` uyğundur.
         */
        'logo_header'  => env('MAIL_BRAND_LOGO_HEADER', ''),
        'logo_footer'  => env('MAIL_BRAND_LOGO_FOOTER', ''),

        'info_email'   => env('MAIL_BRAND_INFO_EMAIL', ''),
        'phone'        => env('MAIL_BRAND_PHONE', ''),
        'address'      => env('MAIL_BRAND_ADDRESS', ''),
    ],

    /*
     * `MailService::enableQueue()` aktiv olanda mail bu növbəyə düşür.
     *
     * NİYƏ AYRICA NÖVBƏ: mail göndərişi SMTP-yə görə yavaşdır. Default növbədə
     * qalsa, arxadakı bütün işlər (bildiriş, hesabat) mail-in arxasında gözləyir.
     * Ayrı worker: `php artisan queue:work --queue=email_queue`
     */
    'queue' => [
        'connection' => env('MAIL_QUEUE_CONNECTION', null),
        'name'       => env('MAIL_QUEUE', 'email_queue'),
    ],

];
