<?php

/*
|--------------------------------------------------------------------------
| Activity Log mesajları
|--------------------------------------------------------------------------
|
| Bu fayl fəaliyyət jurnalının (spatie/laravel-activitylog) mətnlərini saxlayır.
|
| ƏSAS QAYDA - bu fayl həm də AÇAR/SÖNDÜR düyməsidir:
| `App\Traits\Activity\LogsAdminActivity` modeli jurnala yazmazdan əvvəl burada
| onun adını axtarır. ADI BURADA OLMAYAN MODEL ÜMUMİYYƏTLƏ JURNALLANMIR.
| Yəni yeni CRUD modulu yazanda modelə trait əlavə etmək KİFAYƏT DEYİL -
| aşağıya bir blok da yazılmalıdır.
|
| Açar = `class_basename($model)`, yəni namespace-siz sinif adı ("Blog", "Menu").
|
| Hər model üçün:
|  - 'title'   → Azərbaycan dilində model adı (filtr/UI üçün)
|  - 'created' → Yaradılma mesajı
|  - 'updated' → Yenilənmə mesajı
|  - 'deleted' → Silinmə mesajı
|
| PLACEHOLDER-lar:
|  - `:attribute_name` → modelin İSTƏNİLƏN atributu (`:name`, `:email`, `:slug`)
|  - `:causer`         → əməliyyatı edən istifadəçinin adı
|
| Boş atribut mesajda boş yer buraxır - ona görə həmişə DOLU olan sahələr
| seçilir (id, name, key), nullable sahələr yox.
|
| İstifadə: `App\Helpers\Common\ActivityLogHelper::resolveDescription()`
|
*/

return [

    'User' => [
        'title'   => 'İstifadəçi',
        'created' => ':causer yeni istifadəçi yaratdı — :name (:email)',
        'updated' => ':causer istifadəçi məlumatlarını yenilədi — :name (:email)',
        'deleted' => ':causer istifadəçini sildi — :name (:email)',
    ],

    'Admin' => [
        'title'   => 'Admin',
        'created' => ':causer yeni admin əlavə etdi — :full_name (:email)',
        'updated' => ':causer admin məlumatlarını yenilədi — :full_name (:email)',
        'deleted' => ':causer admini sildi — :full_name (:email)',
    ],

    'CustomRole' => [
        'title'   => 'Vəzifə (Rol)',
        'created' => ':causer yeni vəzifə yaratdı — :name (guard: :guard_name)',
        'updated' => ':causer vəzifəni yenilədi — :name',
        'deleted' => ':causer vəzifəni sildi — :name',
    ],

    'CustomPermission' => [
        'title'   => 'İcazə',
        'created' => ':causer yeni icazə əlavə etdi — :name (:title, qrup: :group)',
        'updated' => ':causer icazəni yenilədi — :name (:title)',
        'deleted' => ':causer icazəni sildi — :name',
    ],

    'SiteSetting' => [
        'title'   => 'Sayt Tənzimləməsi',
        'created' => ':causer sayt tənzimləməsi yaratdı',
        'updated' => ':causer sayt tənzimləmələrini yenilədi (sayt: :site_status, giriş: :login_status, qeydiyyat: :register_status, ödəniş: :payment_status)',
        'deleted' => ':causer sayt tənzimləməsini sildi',
    ],

    'Translation' => [
        'title'   => 'Tərcümə',
        'created' => ':causer yeni tərcümə əlavə etdi — :key (:locale, fayl: :filename)',
        'updated' => ':causer tərcüməni yenilədi — :key (:locale)',
        'deleted' => ':causer tərcüməni sildi — :key (:locale, fayl: :filename)',
    ],

    'Language' => [
        'title'   => 'Dil',
        'created' => ':causer yeni dil əlavə etdi — :name (:code)',
        'updated' => ':causer dili yenilədi — :name (:code)',
        'deleted' => ':causer dili sildi — :name (:code)',
    ],

    'Menu' => [
        'title'   => 'Menyu',
        'created' => ':causer yeni menyu elementi yaratdı — :title',
        'updated' => ':causer menyu elementini yenilədi — :title',
        'deleted' => ':causer menyu elementini sildi — :title',
    ],

    'Blog' => [
        'title'   => 'Bloq',
        'created' => ':causer yeni bloq yazısı yaratdı — :title',
        'updated' => ':causer bloq yazısını yenilədi — :title',
        'deleted' => ':causer bloq yazısını sildi — :title',
    ],

    'SiteRedirect' => [
        'title'   => 'Yönləndirmə (Redirect)',
        'created' => ':causer yeni yönləndirmə yaratdı — :source → :target (:http_code)',
        'updated' => ':causer yönləndirməni yenilədi — :source → :target',
        'deleted' => ':causer yönləndirməni sildi — :source',
    ],

    'PageMetaData' => [
        'title'   => 'Səhifə Meta Məlumatı',
        'created' => ':causer səhifə meta məlumatı əlavə etdi — :title (:locale)',
        'updated' => ':causer səhifə meta məlumatını yenilədi — :title (:locale)',
        'deleted' => ':causer səhifə meta məlumatını sildi — :title (:locale)',
    ],

    /*
     * QEYD: `Slider` modelində `$logEnabled = false` təyin olunub - siyahıya
     * yazılsa da jurnallanmır. Modelin özündəki bayraq bu fayldan ÜSTÜNDÜR.
     */

];
