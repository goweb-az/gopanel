<?php

namespace App\Actions\Gopanel\Activity;

use App\Models\Activity\Activity;
use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsAction;

class CleanupActivityLogsAction
{
    use AsAction;

    /**
     * Bulk-delete activity rows. One of the parameter sets must be supplied:
     *
     * - `days = N`            → delete rows older than N days (N = 0 deletes all rows)
     * - `dateFrom + dateTo`   → delete rows in [from 00:00, to 23:59]
     * - `dateFrom only`       → delete rows from $dateFrom up to now
     */
    public function handle(?int $days = null, ?string $dateFrom = null, ?string $dateTo = null): int
    {
        $query = Activity::query();

        if ($days !== null) {
            if ($days > 0) {
                $query->where('created_at', '<', now()->subDays($days));
            }
            // days === 0 → drop everything (no extra constraint)
        } elseif ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [
                CarbonImmutable::parse($dateFrom)->startOfDay(),
                CarbonImmutable::parse($dateTo)->endOfDay(),
            ]);
        } elseif ($dateFrom) {
            $query->where('created_at', '>=', CarbonImmutable::parse($dateFrom)->startOfDay());
        } else {
            return 0;
        }

        $deleted = 0;
        (clone $query)->chunkById(1000, function ($logs) use (&$deleted) {
            $ids = $logs->pluck('id')->toArray();
            Activity::whereIn('id', $ids)->delete();
            $deleted += count($ids);
        });

        return $deleted;
    }
}
