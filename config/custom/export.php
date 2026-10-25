<?php

/*
|--------------------------------------------------------------------------
| Export (Excel / CSV / PDF) tənzimləmələri
|--------------------------------------------------------------------------
|
| `App\Services\Export\*` və `App\Support\Export\*` bu fayldan oxuyur.
| Tam bələdçi: docs/export-import.md
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

    /*
     * Excel və PDF-də eyni işlədilən rənglər (HEX, `#` olmadan).
     * `ExportBranding::color()` oxuyur - iki format eyni rəngdə çıxsın deyə
     * rəng heç bir exporter-də və ya blade-də hardcode edilmir.
     */
    'colors' => [
        'accent'      => env('EXPORT_COLOR_ACCENT', '2A5C9A'),
        'header'      => env('EXPORT_COLOR_HEADER', '2A5C9A'),
        'header_text' => 'FFFFFF',
        'summary'     => 'F1F5F9',
        'border'      => 'CBD5E1',
        'muted'       => '64748B',
    ],

    /*
     * Bir sənəddə yazılacaq maksimum sətir (hər bölmə üçün).
     * NİYƏ: PhpSpreadsheet hər xananı yaddaşda obyekt kimi saxlayır, Dompdf
     * isə bütün cədvəli bir dəfəyə render edir - limitsiz export işçini
     * `memory_limit`-ə dirəyir. Daha böyük həcm `handlers` + queue ilə gedir.
     */
    'max_rows' => (int) env('EXPORT_MAX_ROWS', 20000),

    /*
     * PDF (barryvdh/laravel-dompdf) - İSTƏYƏ BAĞLIDIR.
     * Paket qurulmayıbsa PDF formatı sükutla yox, aydın Azərbaycan dilində
     * xəta ilə imtina edir; Excel həmişə işləyir.
     * Qoşmaq: `composer require barryvdh/laravel-dompdf`.
     */
    'pdf' => [
        'paper' => env('EXPORT_PDF_PAPER', 'a4'),
        // Bu saydan çox sütun olanda səhifə avtomatik albom (landscape) olur.
        'landscape_after_columns' => 7,
    ],

    /*
     * Sənəd başlığındakı «Süzgəclər» sətri üçün sorğu açarı → oxunaqlı ad.
     * `ExportFilterLabels`-in daxili ümumi siyahısının ÜSTÜNƏ yazılır -
     * törəmə layihə öz sahələrini (məs. `warehouse_id` => 'Anbar') bura əlavə edir.
     */
    'filter_labels' => [
        // 'warehouse_id' => 'Anbar',
    ],

    /*
     * Açar → ixrac tərifi reyestri (`App\Support\Export\ExportRegistry`).
     *
     * NİYƏ açar: brauzer sinif adı göndərmir, yalnız buradakı açarı. Endirmə və
     * mail növbəsi sinfi serverdə bu siyahıdan tapır - istifadəçidən gələn
     * sinif adını qurmaq panelə girişi olan hər kəsə istənilən sinfi
     * yaratdırmaq deməkdir.
     *
     * Bənd:
     *   label      - mailin mövzusunda və jurnalda görünən ad
     *   permission - endirmə/göndərmədən əvvəl yoxlanılan icazə
     *   export     - `App\Exports\Gopanel\ExportDefinition` törəməsi
     *   mail       - (istəyə bağlı, default true) mailə göndərmək olarmı
     *
     * Yalnız sətir dəyərləri - `config:cache` işləyir (closure saxlanılmır).
     */
    'definitions' => [
        'activity.activity-logs' => [
            'label'      => 'Əməliyyat logları',
            'permission' => 'gopanel.activity.activity-logs.export',
            'export'     => \App\Exports\Gopanel\Activity\ActivityLogExport::class,
        ],
    ],

    /*
     * Mailə göndərmə job-unun növbəsi. Boşdursa default növbə işlədilir.
     */
    'mail' => [
        'queue_connection' => env('EXPORT_MAIL_QUEUE_CONNECTION'),
        'queue'            => env('EXPORT_MAIL_QUEUE'),
    ],

];
