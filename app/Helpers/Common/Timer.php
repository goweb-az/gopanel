<?php

namespace App\Helpers\Common;

use App\Services\Activity\LogService;

/**
 * Keçən vaxtın ölçülməsi + cron command-lərinin başlanğıc/son jurnalı.
 *
 * Niyə: scheduler-də işləyən command-in «işlədimi, nə qədər çəkdi?» sualı
 * hər layihədə yaranır. Hər command öz `microtime()` hesabını yazanda log
 * formatı dağınıq olur - burada `cron` kanalına EYNİ formada yazılır və
 * panelin fayl jurnalında bir filtrlə hamısı görünür.
 *
 *   $timer = Timer::cronStart('logs:cleanup');
 *   ...
 *   $timer->cronEnd("{$deleted} sətir silindi");
 */
class Timer
{
    protected float $start;
    protected ?string $cronCommand = null;

    public function __construct()
    {
        $this->start = microtime(true);
    }

    public static function start(): static
    {
        return new static();
    }

    /**
     * Timer başlat + cron kanalına başlanğıc logu yaz.
     */
    public static function cronStart(string $command): static
    {
        $timer = new static();
        $timer->cronCommand = $command;
        LogService::channel('cron')->info("{$command} başladı");
        return $timer;
    }

    /**
     * Cron kanalına bitirmə logu yaz (elapsed_ms ilə).
     */
    public function cronEnd(?string $status = null): void
    {
        $command = $this->cronCommand ?? 'unknown';
        $suffix  = $status ? " ({$status})" : '';
        LogService::channel('cron')->info("{$command} tamamlandı{$suffix}", [
            'elapsed_ms' => $this->elapsed(),
        ]);
    }

    /**
     * Başlanğıcdan keçən vaxtı millisaniyə ilə qaytarır.
     */
    public function elapsed(): float
    {
        return round((microtime(true) - $this->start) * 1000, 2);
    }
}
