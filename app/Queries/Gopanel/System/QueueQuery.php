<?php

declare(strict_types=1);

namespace App\Queries\Gopanel\System;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * «Sistem vəziyyəti» səhifəsinin baza sorğuları: növbə, uğursuz işlər, baza
 * ölçüsü.
 *
 * NİYƏ Query sinifi:
 * Səhifə bir neçə saniyədən bir yenilənir, ona görə sorğular DAR olmalıdır -
 * `SELECT *` yox, sayğac və limitli siyahı. Sorğular servisin içində
 * səpələnsəydi, bir gün kimsə oraya limitsiz `get()` əlavə edərdi və monitor
 * səhifəsi serveri yükləməyə başlayardı. Hamısı burada, bir yerdə görünür.
 *
 * Hər metod cədvəlin mövcudluğunu yoxlayır: `jobs` / `failed_jobs` yeni
 * quraşdırmada olmaya bilər və səhifə bundan sınmamalıdır.
 */
class QueueQuery
{
    public function hasJobsTable(): bool
    {
        return Schema::hasTable('jobs');
    }

    public function hasFailedJobsTable(): bool
    {
        return Schema::hasTable('failed_jobs');
    }

    public function pendingCount(): int
    {
        return $this->hasJobsTable()
            ? (int) DB::table('jobs')->whereNull('reserved_at')->count()
            : 0;
    }

    public function runningCount(): int
    {
        return $this->hasJobsTable()
            ? (int) DB::table('jobs')->whereNotNull('reserved_at')->count()
            : 0;
    }

    public function failedCount(): int
    {
        return $this->hasFailedJobsTable()
            ? (int) DB::table('failed_jobs')->count()
            : 0;
    }

    /** Növbədə ən çox gözləyən işin neçə saniyədir gözlədiyi. */
    public function oldestWaitingSeconds(): ?int
    {
        if (!$this->hasJobsTable()) {
            return null;
        }

        $oldest = DB::table('jobs')->whereNull('reserved_at')->min('available_at');

        return $oldest === null ? null : max(time() - (int) $oldest, 0);
    }

    /**
     * Növbə adlarına görə bölgü.
     *
     * @return list<array{queue: string, total: int, pending: int, running: int}>
     */
    public function byQueue(): array
    {
        if (!$this->hasJobsTable()) {
            return [];
        }

        return DB::table('jobs')
            ->selectRaw('queue, COUNT(*) AS total, SUM(reserved_at IS NULL) AS pending')
            ->groupBy('queue')
            ->orderByDesc('total')
            ->get()
            ->map(static fn ($row) => [
                'queue'   => (string) $row->queue,
                'total'   => (int) $row->total,
                'pending' => (int) $row->pending,
                'running' => (int) $row->total - (int) $row->pending,
            ])
            ->all();
    }

    /** Növbədəki son işlər - limitli siyahı. */
    public function latestJobs(int $limit): array
    {
        if (!$this->hasJobsTable()) {
            return [];
        }

        return DB::table('jobs')->orderByDesc('id')->limit($limit)->get()->all();
    }

    /** Son uğursuz işlər - limitli siyahı. */
    public function latestFailedJobs(int $limit): array
    {
        if (!$this->hasFailedJobsTable()) {
            return [];
        }

        return DB::table('failed_jobs')->orderByDesc('id')->limit($limit)->get()->all();
    }

    /**
     * Bazanın adı, versiyası, ölçüsü və cədvəl sayı.
     *
     * Baza əlçatmaz olsa belə səhifə açılmalıdır - ona görə istisna udulur
     * və boş dəyərlər qaytarılır.
     *
     * @return array{name: string|null, version: string|null, size: int|null, tables: int|null}
     */
    public function databaseStats(): array
    {
        try {
            $connection = DB::connection();
            $name       = (string) $connection->getDatabaseName();
            $version    = $connection->selectOne('select version() as version')->version ?? null;

            $stats = $connection->selectOne(
                'SELECT SUM(data_length + index_length) AS size, COUNT(*) AS tables
                 FROM information_schema.tables WHERE table_schema = ?',
                [$name]
            );

            return [
                'name'    => $name,
                'version' => $version === null ? null : (string) $version,
                'size'    => (int) ($stats->size ?? 0),
                'tables'  => (int) ($stats->tables ?? 0),
            ];
        } catch (Throwable) {
            return ['name' => null, 'version' => null, 'size' => null, 'tables' => null];
        }
    }
}
