<?php

declare(strict_types=1);

namespace App\Exports\Gopanel\Activity;

use App\Exports\Gopanel\ExportDefinition;
use App\Helpers\Common\ActivityLogHelper;
use App\Queries\Gopanel\Activity\ActivityLogQuery;

/**
 * Fəaliyyət jurnalının ixracı - starter-dəki İŞLƏK NÜMUNƏ.
 *
 * NİYƏ bu model: `activity_log` hər törəmə layihədə var (spatie/activitylog),
 * ona görə nümunə layihəyə xas iş məntiqi daşımır. Yeni ixrac yazanda bu
 * sinfi köçürüb başlıq/sətirləri dəyişmək kifayətdir.
 *
 * Reyestr açarı: `activity.activity-logs` (`config/custom/export.php`).
 * Link: `route('gopanel.exports.download', ['key' => 'activity.activity-logs', 'format' => 'excel'])`.
 */
final class ActivityLogExport extends ExportDefinition
{
    /**
     * @param  array<string, mixed>  $filters  `ActivityLogDatatable` ilə eyni açarlar
     */
    public function __construct(private readonly array $filters = [])
    {
    }

    public function title(): string
    {
        return 'Əməliyyat logları';
    }

    public function headings(): array
    {
        return ['ID', 'Model', 'Əməliyyat', 'Mesaj', 'Kim tərəfindən', 'Tarix'];
    }

    public function numericColumns(): array
    {
        return [0];
    }

    public function rows(): iterable
    {
        foreach ((new ActivityLogQuery($this->filters))->lazy() as $item) {
            yield [
                (int) $item->id,
                $item->log_name_title,
                $item->event_label,
                (string) ($item->description ?? ''),
                $item->causer_name,
                $item->created_at?->format('d.m.Y H:i'),
            ];
        }
    }

    public function appliedFilters(): array
    {
        return $this->describeFilters($this->filters, [], [
            'event'    => $this->eventLabels(),
            'log_name' => $this->modelLabels(),
        ]);
    }

    /** @return array<string, string> */
    private function eventLabels(): array
    {
        return ['created' => 'Yaradıldı', 'updated' => 'Yeniləndi', 'deleted' => 'Silindi'];
    }

    /**
     * Model açarı → başlıq (`config/custom/activity_messages.php`).
     * Helper yoxdursa və ya xəta verirsə xam açar göstərilir - başlıq
     * bəzəkdir, onun üzündən ixrac sınmamalıdır.
     *
     * @return array<string, string>
     */
    private function modelLabels(): array
    {
        try {
            return collect(ActivityLogHelper::modelBlocks())
                ->map(fn ($config) => (string) ($config['title'] ?? ''))
                ->filter()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
