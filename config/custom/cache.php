<?php

/*
|--------------------------------------------------------------------------
| Keş qrupları və model xəritəsi
|--------------------------------------------------------------------------
|
| `App\Support\Cache\CacheKey` bu fayla baxır. İdeya sadədir: hər qrupun bir
| VERSİYA nömrəsi var və açar bu nömrəni daxil edir ("menu.v3.web.header.az").
| Qrupu təmizləmək = versiyanı artırmaq; köhnə açarlar avtomatik yararsız olur.
|
| Nə üçün tag işlətmirik: `Cache::tags()` yalnız redis/memcached-də işləyir,
| file/database driver-lərində exception atır. Versiya üsulu HƏR driver-də işləyir.
|
| Yeni layihədə YALNIZ bu fayl doldurulur - CacheKey sinfinə toxunulmur.
|
*/

return [

    /*
     * Bütün mövcud qruplar. `CacheKey::flushAll()` bu siyahını gəzir.
     * Aşağıdakı `model_groups`-da işlənən hər qrup burada da olmalıdır.
     */
    'groups' => [
        'lookup',
        'menu',
        'category',
        'translation',
        'settings',
        'home',
        'page',
        'seo',
        'content',
        'language',
    ],

    /*
     * Model → qrup xəritəsi.
     *
     * Model `saved`/`deleted`/`restored` olanda hansı qrupun təmizlənəcəyini bu
     * təyin edir. Bağlantını `App\Providers\CacheInvalidationServiceProvider`
     * qurur - əl ilə event yazmaq lazım deyil. Tərcümə mətni dəyişəndə də
     * (FieldTranslation) sahib modelin qrupu təmizlənir.
     *
     * Yeni modul keşlənirsə: modeli bura bir sətir kimi yaz, oxuyan yerdə
     * `CacheKey::<qrup>(...)` açarını işlət. Mövcud olmayan sinif səssizcə
     * ötürülür (class_exists), ona görə törəmə layihədə silinmiş model sınmır.
     */
    'model_groups' => [
        \App\Models\Navigation\Menu::class            => 'menu',
        \App\Models\Navigation\Category::class        => 'category',
        \App\Models\Translations\Translation::class   => 'translation',
        \App\Models\Geography\Language::class         => 'language',
        \App\Models\Settings\SiteSetting::class       => 'settings',
        \App\Models\Contact\ContactInfo::class        => 'settings',
        \App\Models\Contact\Social::class             => 'settings',
        \App\Models\Site\Slider::class                => 'home',
        \App\Models\Site\AboutUs::class               => 'page',
        \App\Models\Site\Blog::class                  => 'page',
        \App\Models\Site\Service::class               => 'page',
        \App\Models\Site\Product::class               => 'page',
        \App\Models\Seo\SiteRedirect::class           => 'seo',
        \App\Models\Seo\PageMetaData::class           => 'seo',
        \App\Models\Seo\SeoAnalytics::class           => 'seo',
        \App\Models\Seo\LlmsTxt::class                => 'seo',
    ],

];
