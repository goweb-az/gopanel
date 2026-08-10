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
    ],

    /*
     * Model → qrup xəritəsi.
     *
     * Model `saved`/`deleted` olanda hansı qrupun təmizlənəcəyini bu təyin edir.
     * Observer/EventServiceProvider `CacheKey::trackedModels()` siyahısını gəzib
     * hər modelə event bağlayır:
     *
     *   foreach (CacheKey::trackedModels() as $model) {
     *       $model::saved(fn ($m) => CacheKey::flushForModelType($m::class));
     *       $model::deleted(fn ($m) => CacheKey::flushForModelType($m::class));
     *   }
     *
     * Çoxdilli layihədə tərcümə cədvəlini də bağlamaq lazımdır - mətn dəyişəndə
     * modelin özü `saved` olmur (məs. FieldTranslation).
     */
    'model_groups' => [
        // \App\Models\Common\Menu\Menu::class          => 'menu',
        // \App\Models\Gopanel\Translation::class       => 'translation',
        // \App\Models\Site\SiteSetting::class          => 'settings',
        // \App\Models\Site\Slider::class               => 'home',
        // \App\Models\Site\Page::class                 => 'page',
        // \App\Models\Seo\SiteRedirect::class          => 'seo',
    ],

];
