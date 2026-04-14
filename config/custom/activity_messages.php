<?php

/**
 * Activity Log mesajları.
 *
 * Placeholder-lar: modelin istənilən attribute-u `:attribute_name` formatında istifadə oluna bilər.
 * Xüsusi: `:causer` — əməliyyatı edən istifadəçinin adı.
 *
 * Hər model üçün:
 *  - 'title'   → Azərbaycan dilində model adı (filter/UI üçün)
 *  - 'created' → Yaradılma mesajı
 *  - 'updated' → Yenilənmə mesajı
 *  - 'deleted' → Silinmə mesajı
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

];

