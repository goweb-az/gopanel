<?php

declare(strict_types=1);

namespace App\Support\Import;

/**
 * İmport/backfill gedişinin sayğacları və meta məlumatı.
 *
 * NİYƏ: hər import command-i öz `$created++`, `$skipped++` dəyişənlərini
 * saxlayanda yekun cədvəl, `report.json` və jurnal hər dəfə fərqli formatda
 * çıxır. Bu sinif onları vahid formaya salır - konsol cədvəli
 * (`counters()`), JSON hesabat (`toArray()`) eyni mənbədən qurulur.
 *
 * `FailureBucket` ilə birlikdə işlədilir: sayğac «neçə», bucket «hansılar və niyə».
 *
 * ```php
 * $report = new ImportReport();
 * $report->increment('created');
 * $report->set('source', 'products.xlsx');
 * $this->table(['Açar', 'Say'], collect($report->counters())->map(fn ($v, $k) => [$k, $v]));
 * ```
 */
final class ImportReport
{
    /** @var array<string, int> */
    private array $counters = [];

    /** @var array<string, mixed> */
    private array $meta = [];

    private float $startedAt;

    private string $startedAtIso;

    public function __construct()
    {
        $this->startedAt = microtime(true);
        $this->startedAtIso = now()->toIso8601String();
    }

    public function startedAtIso(): string
    {
        return $this->startedAtIso;
    }

    public function increment(string $key, int $by = 1): void
    {
        $this->counters[$key] = ($this->counters[$key] ?? 0) + $by;
    }

    public function set(string $key, mixed $value): void
    {
        $this->meta[$key] = $value;
    }

    public function get(string $key): int
    {
        return $this->counters[$key] ?? 0;
    }

    public function meta(string $key, mixed $default = null): mixed
    {
        return $this->meta[$key] ?? $default;
    }

    /** Bütün sayğacların cəmi - gedişat faizi üçün. */
    public function total(): int
    {
        return array_sum($this->counters);
    }

    public function duration(): float
    {
        return round(microtime(true) - $this->startedAt, 2);
    }

    /**
     * @return array<string, int>
     */
    public function counters(): array
    {
        return $this->counters;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $completedAt = now()->toIso8601String();

        return [
            'counters'         => $this->counters,
            'meta'             => $this->meta,
            'duration_seconds' => $this->duration(),
            'started_at'       => $this->startedAtIso,
            'completed_at'     => $completedAt,
            // Mənbə layihədəki köhnə açar - mövcud report.json oxuyanlar sınmasın.
            'finished_at'      => $completedAt,
        ];
    }
}
