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
| Əlavə (məcburi deyil):
|  - 'attribute_labels' → bu modelə xas sahə adları (ortaq siyahının üstündədir)
|  - 'attribute_values' → rəqəmin mənası, məs. ['status' => [0 => 'qaralama', 1 => 'dərc']]
|
| PLACEHOLDER-lar:
|  - `:attribute_name` → modelin İSTƏNİLƏN atributu (`:name`, `:email`, `:slug`)
|  - `:causer`         → əməliyyatı edən istifadəçinin adı (cron/queue-da «Sistem»)
|  - `:changes`        → nə dəyişdi: «ad: Köhnə → Yeni, aktivlik: xeyr → bəli»
|                        (yalnız `updated`-də mənalıdır; bax ActivityChangeFormatter)
|
| Faylın əvvəlindəki ORTAQ açarlar (attribute_labels, date_only_attributes,
| weekday_attributes, technical_attributes, context_labels) model deyil.
| Qayda: model açarı BÖYÜK hərflə (Blog), ortaq açar kiçik hərflə (snake_case)
| yazılır - `ActivityLogHelper::modelBlocks()` onları belə ayırır.
|
| Boş atribut mesajda boş yer buraxır - ona görə həmişə DOLU olan sahələr
| seçilir (id, name, key), nullable sahələr yox.
|
| İstifadə: `App\Helpers\Common\ActivityLogHelper::resolveDescription()`
|
*/

return [

    /*
     * Bütün modellər üçün ORTAQ sahə adları.
     *
     * Modelin öz `attribute_labels` siyahısı bunun üstündədir. Siyahıda olmayan
     * sahə öz texniki adı ilə görünür - yeni sahə əlavə edəndə bura bir sətir
     * yazmaq kifayətdir. Həm `:changes` mətni, həm paneldəki «Nə dəyişdi»
     * cədvəli bundan qidalanır (App\Support\Activity\ActivityChangeFormatter).
     */
    'attribute_labels' => [
        'name'        => 'ad',
        'surname'     => 'soyad',
        'full_name'   => 'ad soyad',
        'email'       => 'e-poçt',
        'phone'       => 'telefon',
        'title'       => 'başlıq',
        'description' => 'təsvir',
        'slug'        => 'slug',
        'image'       => 'şəkil',
        'icon'        => 'ikon',
        'url'         => 'link',
        'status'      => 'status',
        'is_active'   => 'aktivlik',
        'is_default'  => 'standart',
        'is_super'    => 'super admin',
        'sort_order'  => 'sıra',
        'order'       => 'sıra',
        'parent_id'   => 'valideyn',
        'role'        => 'rol',
        'key'         => 'açar',
        'value'       => 'dəyər',
        'color'       => 'rəng',
        'start_date'  => 'başlanğıc tarixi',
        'end_date'    => 'son tarix',
        'published_at'=> 'dərc tarixi',
        'note'        => 'qeyd',
    ],

    /*
     * Saatı mənasız olan tarix sahələri - «25.01.2026 23:00» əvəzinə
     * «25.01.2026» göstərilir. `_date` ilə bitən sahələr avtomatik buradadır.
     */
    'date_only_attributes' => [
        'birth_date',
        'published_at',
    ],

    /*
     * Həftənin günü kimi saxlanılan sahələr - rəqəm («1») ad kimi göstərilir.
     * Adların özü burada yazılmır: tək mənbə `App\Support\Date\MonthNames`.
     */
    'weekday_attributes' => [
        'day_of_week',
    ],

    /*
     * «Nə dəyişdi» cədvəlində GÖSTƏRİLMƏYƏN texniki sahələr. Daxili
     * identifikatorlar oxucuya heç nə demir. Yalnız görünüşə aiddir - log
     * sətri olduğu kimi yazılır və xam JSON detal pəncərəsində qalır.
     */
    'technical_attributes' => [
        'id',
        'uid',
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
    ],

    /*
     * Modelin `activityContext(): array` metodunun qaytardığı açarların adları
     * (detal pəncərəsindəki «kontekst» bloku). `attribute_labels`-in üstündədir.
     */
    'context_labels' => [
        'causer_name' => 'Kim etdi',
        'causer_id'   => 'Kim etdi (ID)',
        'causer_type' => 'Kim etdi (tip)',
    ],

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
