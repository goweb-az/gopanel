<?php

/*
|--------------------------------------------------------------------------
| Export (Excel / CSV / PDF) tənzimləmələri
|--------------------------------------------------------------------------
|
| `App\Services\Export\*` və `App\Support\Export\ExportBranding` bu fayldan oxuyur.
|
*/

return [

    /*
     * Bu limitdən çox sətri olan export sinxron yüklənmir - queue-ya düşür,
     * hazır olanda istifadəçiyə mail/bildiriş gedir.
     *
     * NİYƏ: brauzer 30-60 saniyədən sonra sorğunu kəsir; 50 min sətirlik
     * Excel isə həmişə daha uzun çəkir. Limitsiz sinxron export = 504 xətası.
     */
    'sync_limit' => (int) env('EXPORT_SYNC_LIMIT', 300),

    /*
     * Export fayllarının saxlanıldığı disk. PRIVATE olmalıdır -
     * hesabat faylı birbaşa URL ilə açılan yerdə saxlanılmır.
     */
    'disk' => env('EXPORT_DISK', 'local'),

    /* Hazır faylın neçə gündən sonra silinəcəyi (təmizləmə command-i üçün). */
    'retention_days' => (int) env('EXPORT_RETENTION_DAYS', 7),

    /*
     * Bütün export header/footer-lərindəki statik brend məlumatları -
     * PDF, Excel və mail üçün TƏK MƏNBƏ.
     * PHP tərəfdə `App\Support\Export\ExportBranding` üzərindən oxunur.
     */
    'branding' => [
        'title'   => env('EXPORT_BRAND_TITLE', env('APP_NAME', 'Gopanel')),
        'phone'   => env('EXPORT_BRAND_PHONE', ''),
        'website' => env('EXPORT_BRAND_WEBSITE', ''),
        'logo'    => env('EXPORT_BRAND_LOGO', ''),
    ],

    /*
     * Bölmə açarı → `ExportHandler` sinfi.
     * Yeni export tipi əlavə edəndə handler yazılır və burada qeydiyyatdan keçir;
     * export job-una toxunmaq lazım deyil.
     */
    'handlers' => [
        // 'blogs' => \App\Services\Export\Handlers\BlogExportHandler::class,
    ],

];
