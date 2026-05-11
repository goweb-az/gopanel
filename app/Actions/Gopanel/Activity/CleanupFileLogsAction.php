<?php

namespace App\Actions\Gopanel\Activity;

use App\Models\Activity\FileLog;
use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsAction;

class CleanupFileLogsAction
{
    use AsAction;

    public function handle(?int $days = null, ?string $dateFrom = null, ?string $dateTo = null): int
    {
        $query = FileLog::query();

        if (! is_null($days)) {
            if ($days > 0) {
                $query->where('created_at', '<', now()->subDays($days));
            }
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
            FileLog::whereIn('id', $ids)->delete();
            $deleted += count($ids);
        });

        return $deleted;
    }
}
