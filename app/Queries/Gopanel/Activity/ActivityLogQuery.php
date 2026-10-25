<?php

declare(strict_types=1);

namespace App\Queries\Gopanel\Activity;

use App\Models\Activity\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;

/**
 * Fəaliyyət jurnalının süzgəcli SELECT-i - ixrac (`ActivityLogExport`) üçün.
 *
 * Süzgəc açarları `ActivityLogDatatable`-dakı ilə EYNİDİR (`log_name`, `event`,
 * `causer_id`, `date_from`, `date_to`, `search`) - ixrac linki cari cədvəl
 * süzgəclərini ötürür və fayl ekranda görünən sətirləri verir.
 *
 * Tarix süzgəci `whereDate()` yox, aralıqla yazılıb - `whereDate()` indeksi
 * işlətmir (bax `App\Support\Date\DayRange`).
 */
final class ActivityLogQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private readonly array $filters = [])
    {
    }

    public function builder(): Builder
    {
        $query = Activity::query()->with('causer');

        if ($logName = $this->filter('log_name')) {
            $query->where('log_name', $logName);
        }

        if ($event = $this->filter('event')) {
            $query->where('event', $event);
        }

        if ($causer = $this->filter('causer_id')) {
            $query->where('causer_id', $causer);
        }

        if ($from = $this->filter('date_from')) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }

        if ($to = $this->filter('date_to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        if ($search = $this->filter('search')) {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('description', 'LIKE', '%' . $search . '%')
                    ->orWhere('log_name', 'LIKE', '%' . $search . '%');
            });
        }

        // Sıralama burada qoyulmur: `lazyByIdDesc()` öz `ORDER BY id`-sini qurur,
        // əlavə sıralama onunla toqquşardı.
        return $query;
    }

    /**
     * Yaddaşa qənaətlə, yenidən köhnəyə oxuma - ixrac on minlərlə sətir ola bilər.
     * `offset` əvəzinə `id`-yə görə səhifələnir (dərin səhifədə yavaşlamır).
     *
     * @return LazyCollection<int, Activity>
     */
    public function lazy(int $chunk = 500): LazyCollection
    {
        return $this->builder()->lazyByIdDesc($chunk, 'id');
    }

    private function filter(string $key): ?string
    {
        $value = $this->filters[$key] ?? null;

        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }
}
