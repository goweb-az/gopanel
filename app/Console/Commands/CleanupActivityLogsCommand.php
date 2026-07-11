<?php

namespace App\Console\Commands;

use App\Helpers\Common\Timer;
use App\Models\Activity\Activity;
use App\Services\Activity\LogService;
use Illuminate\Console\Command;

class CleanupActivityLogsCommand extends Command
{
    protected $signature = 'activity-logs:cleanup {--days=30 : Son neçə gündən köhnə logları sil}';

    protected $description = 'Son N gündən köhnə activity_log qeydlərini sil';

    public function handle(): int
    {
        $log   = new LogService('commands', false);
        $timer = Timer::cronStart('activity-logs:cleanup');

        $days = (int) $this->option('days') ?? 30;
        $cutoff = now()->subDays($days);

        $log->info('activity-logs:cleanup başladı', ['days' => $days]);

        try {
            $count = Activity::where('created_at', '<', $cutoff)->count();

            if ($count === 0) {
                $log->info('activity-logs:cleanup: Silinəcək log tapılmadı', [
                    'days'       => $days,
                    'elapsed_ms' => $timer->elapsed(),
                ]);
                $this->info("Silinəcək log tapılmadı ({$days} gündən köhnə).");
                return self::SUCCESS;
            }

            $this->info("{$count} log qeydi tapıldı ({$days} gündən köhnə).");

            // Chunk ilə sil ki, böyük cədvəllərdə lock problemi olmasın
            $deleted = 0;
            Activity::where('created_at', '<', $cutoff)
                ->chunkById(1000, function ($logs) use (&$deleted) {
                    $ids = $logs->pluck('id')->toArray();
                    Activity::whereIn('id', $ids)->delete();
                    $deleted += count($ids);
                });

            $log->info('activity-logs:cleanup tamamlandı', [
                'deleted'    => $deleted,
                'days'       => $days,
                'elapsed_ms' => $timer->elapsed(),
            ]);
            $this->info("{$deleted} köhnə log qeydi uğurla silindi.");

            $timer->cronEnd();
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $log->error('activity-logs:cleanup xətası', [
                'error'      => $e->getMessage(),
                'exception'  => $e->getTraceAsString(),
                'elapsed_ms' => $timer->elapsed(),
            ]);
            $this->error("Xəta: {$e->getMessage()}");

            $timer->cronEnd('xəta');
            return self::FAILURE;
        }
    }
}
