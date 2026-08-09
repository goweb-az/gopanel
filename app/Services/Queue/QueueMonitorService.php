<?php

declare(strict_types=1);

namespace App\Services\Queue;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Throwable;

/**
 * Növbələrin (queue) canlı vəziyyətini toplayır - GoPanel «Job Monitor» paneli üçün.
 *
 * Serverdə işlədilən `watch -n 2 'php artisan queue:monitor redis:{queue}'`
 * skriptinin panel qarşılığıdır. Console command çağırıb mətn output-u parse
 * etmək əvəzinə birbaşa driver-dən oxuyur - bu, `size()`-in verdiyi CƏMİ ədədi
 * üç ayrı komponentə (gözləyən / gecikmiş / işlənən) ayırmağa imkan verir.
 *
 * REDIS: `RedisQueue::getQueue()/getConnection()` ilə `llen` + `zcard` oxunur.
 * DATABASE: `jobs` cədvəli sayılır (`reserved_at`/`available_at` sütunlarına görə).
 *
 * İzlənən növbələr `config/custom/queue_monitor.php` faylındadır - yeni növbə
 * əlavə olunanda YALNIZ orada bir sətir yazılır.
 */
class QueueMonitorService
{
    /** Bütün növbələrin + `failed_jobs` cədvəlinin anlıq görüntüsü. */
    public function snapshot(): array
    {
        return [
            'queues' => $this->queueBreakdown(),
            'failed' => $this->failedSummary(),
            'time'   => now()->format('H:i:s'),
        ];
    }

    /**
     * Hər növbə üçün gözləyən / gecikmiş / işlənən say.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function queueBreakdown(): array
    {
        $queues = [];

        foreach ((array) config('custom.queue_monitor.queues', []) as $name => $meta) {
            $connection = (string) ($meta['connection'] ?? config('queue.default'));
            $driver     = (string) config("queue.connections.{$connection}.driver", 'sync');

            try {
                $counts = match ($driver) {
                    'redis'    => $this->redisCounts($connection, (string) $name),
                    'database' => $this->databaseCounts($connection, (string) $name),
                    default    => ['waiting' => 0, 'delayed' => 0, 'reserved' => 0],
                };
                $error = null;
            } catch (Throwable $e) {
                $counts = ['waiting' => 0, 'delayed' => 0, 'reserved' => 0];
                $error  = $e->getMessage();
            }

            $queues[] = array_merge([
                'connection' => $connection,
                'driver'     => $driver,
                'queue'      => (string) $name,
                'label'      => (string) ($meta['label'] ?? $name),
                'channel'    => $meta['channel'] ?? null,
                'total'      => array_sum($counts),
                'error'      => $error,
            ], $counts);
        }

        return $queues;
    }

    /**
     * Redis: `{queue}` siyahısı + `:delayed` və `:reserved` sorted set-ləri.
     *
     * @return array{waiting: int, delayed: int, reserved: int}
     */
    protected function redisCounts(string $connection, string $queue): array
    {
        /** @var \Illuminate\Queue\RedisQueue $redisQueue */
        $redisQueue = Queue::connection($connection);
        $redis      = $redisQueue->getConnection();
        $key        = $redisQueue->getQueue($queue);

        return [
            'waiting'  => (int) $redis->llen($key),
            'delayed'  => (int) $redis->zcard($key . ':delayed'),
            'reserved' => (int) $redis->zcard($key . ':reserved'),
        ];
    }

    /**
     * Database: `jobs` cədvəli. `reserved_at` dolu = işlənir,
     * `available_at` gələcəkdədir = gecikdirilmiş.
     *
     * @return array{waiting: int, delayed: int, reserved: int}
     */
    protected function databaseCounts(string $connection, string $queue): array
    {
        $table = (string) config("queue.connections.{$connection}.table", 'jobs');
        $now   = now()->getTimestamp();

        $base = fn () => DB::table($table)->where('queue', $queue);

        return [
            'waiting'  => (int) $base()->whereNull('reserved_at')->where('available_at', '<=', $now)->count(),
            'delayed'  => (int) $base()->whereNull('reserved_at')->where('available_at', '>', $now)->count(),
            'reserved' => (int) $base()->whereNotNull('reserved_at')->count(),
        ];
    }

    /**
     * `failed_jobs` cədvəlindən növbə üzrə say + son N uğursuz iş.
     */
    protected function failedSummary(int $recentLimit = 15): array
    {
        $byQueue = DB::table('failed_jobs')
            ->selectRaw('queue, COUNT(*) as total')
            ->groupBy('queue')
            ->pluck('total', 'queue');

        $recent = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit($recentLimit)
            ->get()
            ->map(static function ($row) {
                $payload  = json_decode((string) $row->payload, true);
                $jobClass = $payload['displayName'] ?? ($payload['job'] ?? 'Naməlum job');

                return [
                    'id'             => $row->id,
                    'uuid'           => $row->uuid,
                    'connection'     => $row->connection,
                    'queue'          => $row->queue,
                    'job'            => class_basename($jobClass),
                    'exception'      => Str::limit((string) (strtok((string) $row->exception, "\n") ?: ''), 160),
                    'exception_full' => (string) $row->exception,
                    'failed_at'      => $row->failed_at,
                ];
            });

        return [
            'total'    => (int) $byQueue->sum(),
            'by_queue' => $byQueue,
            'recent'   => $recent,
        ];
    }

    /**
     * Bütün uğursuz işləri silir (`queue:flush`). GERİ QAYTARILMIR.
     *
     * @return int Silinmədən əvvəlki say
     */
    public function clearFailedJobs(): int
    {
        $count = (int) DB::table('failed_jobs')->count();

        if ($count > 0) {
            Artisan::call('queue:flush');
        }

        return $count;
    }

    /**
     * Bütün uğursuz işləri yenidən növbəyə qoyur (`queue:retry all`).
     *
     * @return int Yenidən növbəyə qoyulan say
     */
    public function retryAllFailedJobs(): int
    {
        $count = (int) DB::table('failed_jobs')->count();

        if ($count > 0) {
            Artisan::call('queue:retry', ['id' => ['all']]);
        }

        return $count;
    }
}
