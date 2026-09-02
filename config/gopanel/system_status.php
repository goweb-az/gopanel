<?php

/**
 * «Sistem vəziyyəti» (monitor) bölməsinin tənzimləmələri.
 *
 * Bölmə serverin anlıq göstəricilərini oxuyur: CPU, yaddaş, disk, növbə,
 * planlaşdırıcı. Heç bir cədvəl saxlamır — hər sorğuda canlı dəyər oxunur.
 */

return [

    // Səhifənin özünü yeniləmə aralığı (millisaniyə).
    // Çox kiçik dəyər serveri boş yerə yükləyir — 5 saniyə balanslı seçimdir.
    'refresh_ms' => (int) env('SYSTEM_STATUS_REFRESH_MS', 5000),

    // Canlı qrafikdə saxlanılan nöqtə sayı (yalnız brauzerdə, bazada deyil).
    // 60 nöqtə × 5 saniyə ≈ son 5 dəqiqə.
    'history_points' => 60,

    // Növbə cədvəllərində göstərilən sətir sayı.
    'job_list_limit' => 10,

    // Növbədəki iş bu qədər saniyədən çox gözləyirsə xəbərdarlıq verilir —
    // adətən bu, queue worker-in dayandığını bildirir.
    'stale_job_seconds' => 300,

    // Planlaşdırıcı (cron) heartbeat faylı bu qədər saniyədən köhnədirsə
    // «cron işləmir» kimi göstərilir. Cron dəqiqədə bir işləyir, ona görə
    // 3 dəqiqə təhlükəsiz həddir.
    'scheduler_stale_seconds' => 180,

    // Heartbeat faylı — `App\Console\Kernel::schedule()` hər dəqiqə yazır.
    // Keşdə deyil, faylda saxlanılır ki, `cache:clear` onu silməsin.
    'heartbeat_file' => storage_path('app/system/scheduler-heartbeat.txt'),

    // Serverin crontab siyahısı səhifədə göstərilsinmi (`crontab -l`).
    // Yalnız səhifə ilk açılanda oxunur, yenilənmə sorğularında yox.
    'show_crontab' => (bool) env('SYSTEM_STATUS_SHOW_CRONTAB', true),

    // Faiz göstəricilərinin rəng həddi: bundan yuxarı sarı, sonra qırmızı.
    'thresholds' => [
        'warning' => 75,
        'danger'  => 90,
    ],
];
