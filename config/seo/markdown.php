<?php

/*
|--------------------------------------------------------------------------
| Səhifələrin maşın üçün Markdown variantı (`canonical + .md`)
|--------------------------------------------------------------------------
|
| `/az/bloqlar` → `/az/bloqlar.md`, tam siyahı → `/markdown-index.md`,
| bütün sənədlər bir faylda → `/llms-full.txt`.
|
| Mətn mövcud modellərdən oxunur (`App\Services\Seo\Markdown\MarkdownPageService`),
| yeni şablon yazılmır - Markdown variantı ilə HTML sayt həmişə eyni məzmunu verir.
|
| Bölmə başlıqları burada saxlanılır, `translations` cədvəlində yox: bunlar
| ziyarətçiyə görünən UI mətni deyil, maşın sənədinin bölmə adlarıdır və
| paneldən idarə olunmur. Yeni dil əlavə olunanda `default` işə düşür.
|
| Sənəd `docs/markdown-pages.md`-dədir.
*/

return [

    /*
     * Bu prefikslərlə başlayan yollar üçün Markdown variantı YOXDUR və HTML
     * səhifədə `<link rel="alternate" type="text/markdown">` çap olunmur.
     * Əməliyyat/texniki ünvanlardır. Törəmə layihə öz checkout/kabinet
     * prefikslərini bura əlavə edir.
     */
    'excluded_prefixes' => [
        'gopanel',
        'api',
        'test',
        '404',
    ],

    /*
     * Menyu `route_name` → daxili blok. Menyu səhifəsinin Markdown sənədinə
     * əlavə məlumat qoşur (məs. əlaqə səhifəsinə ünvan/telefon/e-poçt).
     * Dəstəklənən bloklar: `contact`.
     */
    'menu_blocks' => [
        'contact' => 'contact',
    ],

    /*
     * Hazır sənədlərin keş ömrü (saniyə). Açar həm `seo` qrupunun versiyasını,
     * həm də məzmunun barmaq izini (say + son `updated_at`) daşıyır - yəni
     * paneldə dəyişiklik olanda sənəd TTL-i gözləmədən yenilənir.
     */
    'cache_ttl' => (int) env('SEO_MARKDOWN_CACHE_TTL', 3600),

    /*
     * `/llms-full.txt` - bütün Markdown sənədlərinin bir faylda birləşməsi.
     * Böyük saytda fayl şişməsin deyə sənəd sayı yuxarıdan bağlanır.
     */
    'llms_full' => [
        'enabled'       => (bool) env('SEO_LLMS_FULL_ENABLED', true),
        'max_documents' => (int) env('SEO_LLMS_FULL_MAX_DOCUMENTS', 300),
    ],

    /*
     * Sənəd bölmələrinin adları. Açar → dil → mətn.
     */
    'labels' => [
        'default' => [
            'sections'   => 'Sections',
            'items'      => 'Items',
            'contact'    => 'Contact',
            'homepage'   => 'Homepage',
            'pages'      => 'Pages',
            'full_index' => 'Full index',
            'full_text'  => 'Full text',
            'canonical'  => 'Canonical',
            'markdown'   => 'Markdown',
            'language'   => 'Language',
            'date'       => 'Date',
            'phone'      => 'Phone',
            'mobile'     => 'Mobile',
            'email'      => 'Email',
            'address'    => 'Address',
        ],
        'az' => [
            'sections'   => 'Bölmələr',
            'items'      => 'Yazılar',
            'contact'    => 'Əlaqə',
            'homepage'   => 'Ana səhifə',
            'pages'      => 'Səhifələr',
            'full_index' => 'Tam siyahı',
            'full_text'  => 'Tam mətn',
            'canonical'  => 'Kanonik ünvan',
            'markdown'   => 'Markdown',
            'language'   => 'Dil',
            'date'       => 'Tarix',
            'phone'      => 'Telefon',
            'mobile'     => 'Mobil',
            'email'      => 'E-poçt',
            'address'    => 'Ünvan',
        ],
        'ru' => [
            'sections'   => 'Разделы',
            'items'      => 'Публикации',
            'contact'    => 'Контакты',
            'homepage'   => 'Главная страница',
            'pages'      => 'Страницы',
            'full_index' => 'Полный список',
            'full_text'  => 'Полный текст',
            'canonical'  => 'Канонический адрес',
            'markdown'   => 'Markdown',
            'language'   => 'Язык',
            'date'       => 'Дата',
            'phone'      => 'Телефон',
            'mobile'     => 'Мобильный',
            'email'      => 'Эл. почта',
            'address'    => 'Адрес',
        ],
    ],
];
