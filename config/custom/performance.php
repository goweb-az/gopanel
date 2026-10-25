<?php

/*
|--------------------------------------------------------------------------
| Performans diaqnostikası
|--------------------------------------------------------------------------
|
| `App\Http\Middleware\ServerTiming` bu fayldan oxuyur.
|
*/

return [

    /*
     * Hər cavaba standart `Server-Timing` başlığı (brauzerin Network →
     * Timing panelində boot / app / db vaxtı və sorğu sayı görünür).
     *
     * İKİ şərt birlikdə lazımdır: `SERVER_TIMING_ENABLED=true` VƏ
     * `APP_DEBUG=true`. Niyə: başlıq daxili quruluşu (sorğu sayı, DB vaxtı)
     * açır - canlıda təsadüfən açıq qalan env dəyəri bunu kənara sızdırmasın.
     */
    'server_timing' => [
        'enabled' => (bool) env('SERVER_TIMING_ENABLED', false),

        /*
         * Bu həddən (ms) yavaş sorğu `single` jurnalına ən yavaş SQL-lərlə
         * birlikdə yazılır. 0 → jurnal sönük, yalnız başlıq.
         */
        'slow_ms' => (int) env('SERVER_TIMING_SLOW_MS', 0),
    ],

];
