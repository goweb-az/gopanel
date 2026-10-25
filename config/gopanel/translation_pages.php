<?php

/*
|--------------------------------------------------------------------------
| UI tərcümələrinin səhifə kataloqu
|--------------------------------------------------------------------------
|
| Açar `App\Enums\Gopanel\TranslationPlatfroms` dəyəridir. Kataloq həm panelin
| səhifə seçicisini, həm `translations:seed-site` / Excel idxalının yoxlamasını
| qidalandırır (`App\Helpers\Gopanel\TranslationPageRegistry`). Kataloqda
| olmayan səhifəyə yazılan sətir RƏDD edilir.
|
| Qaydalar (tam: docs/translations.md → «Səhifə məntiqi»):
|  - bir səhifə = saytın bir ekranı (dizayndakı bir freym); açar snake_case;
|  - eyni ekranın vəziyyətləri (modal, boş siyahı, uğur mesajı) ayrıca səhifə
|    DEYİL - həmin səhifənin açarlarıdır;
|  - hər səhifədə təkrarlanan komponentlər (header, footer, menyu, ümumi
|    düymələr, alert, 404) `general`-dadır;
|  - dizaynda olmayan səhifə uydurulmur - ekran gələndə əlavə olunur.
|
| Törəmə layihə frontend-i implement edərkən öz ekranlarını bura yazır.
|
*/

return [
    'website' => [
        'general' => 'Ümumi (header, footer, düymələr)',
        'home'    => 'Ana səhifə',
        'about'   => 'Haqqımızda',
        'blog'    => 'Bloq (siyahı və detal)',
        'contact' => 'Əlaqə',
    ],

    'mobile' => [
        'general' => 'Ümumi',
    ],

    'admin' => [
        'general' => 'Ümumi',
    ],

    'panel' => [
        'general' => 'Ümumi',
    ],
];
