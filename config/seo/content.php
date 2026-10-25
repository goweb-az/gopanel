<?php

/*
|--------------------------------------------------------------------------
| Saytın publik məzmun modelləri (detal səhifəsi olanlar)
|--------------------------------------------------------------------------
|
| Sitemap (`/{dil}/sitemap.xml`), RSS (`/{dil}/rss.xml`), Markdown indeksi
| (`/markdown-index.md`) və `/llms-full.txt` EYNİ siyahıdan qidalanır - yeni
| bölmə bir dəfə buraya yazılır və dörd yerdə birdən görünür.
|
| Model üçün tələblər (Blog nümunədir):
|   - `Translation` trait-i və `slug` tərcümə sahəsi;
|   - `public $controller = ...` - `DynamicContentController` detal səhifəsini
|     məhz bu controller-in `single()` metodu ilə açır (`/{dil}/{slug}`);
|   - `is_active` sütunu (yoxdursa qeyd həmişə publik sayılır).
|
| Açarlar:
|   changefreq / priority  sitemap dəyərləri
|   rss                    tarixli məzmundursa `true` (bloq, xəbər) - RSS-ə düşür
|   list_menu              siyahı səhifəsinin menyu `route_name`-i: həmin menyu
|                          səhifəsinin Markdown sənədi bu modelin yazılarını sadalayır
|   limit                  sitemap/indeks üçün maksimum qeyd sayı
*/

return [

    'models' => [
        \App\Models\Site\Blog::class => [
            'changefreq' => 'weekly',
            'priority'   => '0.80',
            'rss'        => true,
            'list_menu'  => 'blogs',
            'limit'      => 1000,
        ],
    ],

    /*
     * RSS feed-də maksimum yazı sayı (bütün mənbələr birlikdə).
     */
    'rss_limit' => 50,

    /*
     * Menyu səhifələrinin sitemap dəyərləri.
     */
    'menu' => [
        'changefreq' => 'weekly',
        'priority'   => '0.80',
    ],
];
