<?php

/*
|--------------------------------------------------------------------------
| Meta məlumatların son ehtiyat dəyərləri
|--------------------------------------------------------------------------
|
| Sıra (`App\Services\Site\Seo\MetaService`):
|   səhifənin öz `page_meta_data` sətri → menyunun sətri →
|   `SiteSetting`-in sətri → BU FAYL.
|
| Yəni bura yalnız baza tamamilə boş olanda düşülür. Real mətnlər paneldən
| (Tənzimləmələr → meta) idarə olunur. Starter olduğu üçün dəyərlər neytraldır -
| törəmə layihə öz brendini yazır.
*/

return [

    /*
     * Dil kodu → dəyərlər. Dil burada yoxdursa `app.fallback_locale`, o da
     * yoxdursa `config('app.name')` işlədilir.
     */
    'defaults' => [
        'az' => [
            'title'       => env('APP_NAME', 'Gopanel'),
            'description' => '',
            'keywords'    => '',
        ],
    ],

    /*
     * Bazada şəkil yoxdursa `og:image` / `twitter:image` üçün (1200×630 PNG
     * tövsiyə olunur - Facebook/WhatsApp SVG qəbul etmir). `public/` altındakı
     * nisbi yol və ya tam URL. `null` olsa sayt loqosu işlədilir.
     */
    'default_image' => env('SEO_DEFAULT_IMAGE'),

    /*
     * JSON-LD `Organization.logo` üçün ehtiyat (paneldə loqo yüklənməyibsə).
     */
    'logo' => env('SEO_DEFAULT_LOGO'),

    /*
     * Model mətnindən avtomatik qurulan meta təsvirinin maksimum uzunluğu.
     */
    'description_limit' => 160,

    /*
     * Default `<meta name="robots">` dəyəri. Texniki səhifə controller-də
     * `$this->setPageRobots('noindex, follow')` çağırır.
     */
    'robots' => 'index, follow',

    /*
     * `og:locale` dil kodunu deyil, `dil_ÖLKƏ` formatını gözləyir. Yeni dil
     * əlavə olunanda buraya da yazılır - yoxdursa dil kodunun özü çap olunur.
     */
    'og_locales' => [
        'az' => 'az_AZ',
        'en' => 'en_US',
        'ru' => 'ru_RU',
        'tr' => 'tr_TR',
        'de' => 'de_DE',
        'fr' => 'fr_FR',
    ],
];
