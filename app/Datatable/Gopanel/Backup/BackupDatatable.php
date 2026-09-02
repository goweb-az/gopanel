<?php

declare(strict_types=1);

namespace App\Datatable\Gopanel\Backup;

use App\Datatable\Gopanel\Concerns\RendersRichCells;
use App\Datatable\Gopanel\GopanelDatatable;
use App\Models\Backup\Backup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Çıxarılmış backup-ların siyahısı.
 *
 * «Növ», «Vəziyyət», «Ölçü» və «Əməliyyatlar» sütunları bazada olduğu kimi
 * deyil, burada hazırlanır - ona görə `orderable => false` verilir
 * (bax: 02-gopanel.md § 2: kənardan gələn sütun adı birbaşa `orderBy`-a
 * verilmir).
 */
class BackupDatatable extends GopanelDatatable
{
    use RendersRichCells;

    public function __construct()
    {
        parent::__construct(Backup::class, [
            'id'         => 'ID',
            'created_at' => 'Başladılıb',
        ], [
            'type' => [
                'title'     => 'Növ',
                'type'      => 'callable',
                'orderable' => false,
                'view'      => fn ($item) => $this->typeCell($item),
            ],
            'status' => [
                'title'     => 'Vəziyyət',
                'type'      => 'callable',
                'orderable' => false,
                'view'      => fn ($item) => $this->statusCell($item),
            ],
            'size' => [
                'title'     => 'Ölçü',
                'type'      => 'callable',
                'orderable' => false,
                'view'      => fn ($item) => $this->sizeCell($item),
            ],
            'author' => [
                'title'     => 'Kim çıxarıb',
                'type'      => 'callable',
                'orderable' => false,
                'view'      => fn ($item) => $this->authorCell($item),
            ],
            'actions' => [
                'title'     => 'Əməliyyatlar',
                'type'      => 'callable',
                'orderable' => false,
                'view'      => fn ($item) => $this->itemActions($item),
            ],
        ]);
    }

    protected function query(): Builder
    {
        return $this->baseQueryScope()->with('admin')->orderByDesc('id');
    }

    /**
     * Tarix sütunu iki sətirli hüceyrəyə çevrilir.
     *
     * Valideyn `formatPredefinedColumns()` yalnız mətn qaytarır - burada
     * `Carbon` obyektinə ehtiyac var, ona görə sətirlər sonradan yenilənir.
     */
    protected function processRecords(Collection $records): array
    {
        $rows = parent::processRecords($records);

        foreach ($records->values() as $index => $item) {
            $rows[$index]['created_at'] = $this->dateCell($item->created_at);
        }

        return $rows;
    }

    private function typeCell(Backup $item): string
    {
        $sub = $item->mode === 'full'
            ? 'Tam arxiv'
            : ($item->mode === 'incremental' ? 'Yalnız yeni fayllar' : null);

        if ($item->file_count !== null) {
            $sub = trim(($sub ? $sub . ' · ' : '') . $item->file_count . ' fayl');
        }

        return $this->titleCell($item->type->title(), $sub);
    }

    private function statusCell(Backup $item): string
    {
        $badge = $this->badge($item->status->title(), $item->status->tone());

        if ($item->status->value === 'failed' && !empty($item->error)) {
            // Xəta mətnində adətən tam yol olur və o, kəsiləndə itir -
            // ona görə tam mətn `title` atributunda saxlanılır.
            return $badge
                . '<div class="gp-cell-sub gp-cell-clamp" title="' . e($item->error) . '">'
                . e(mb_substr($item->error, 0, 160))
                . '</div>';
        }

        if ($duration = $item->duration()) {
            return $badge . '<div class="gp-cell-sub">' . e($duration) . '</div>';
        }

        return $badge;
    }

    private function sizeCell(Backup $item): string
    {
        if (!$item->archiveExists() && $item->status->value === 'completed') {
            // Arxiv «hazır» yazılıb, amma fayl yerində yoxdur. Ən çox rast
            // gəlinən səbəb: işi başqa quraşdırmanın işçisi götürüb və arxiv
            // ora düşüb. `ran_on` məhz bunun üçün saxlanılır.
            $ranOn = $item->meta['ran_on']['base_path'] ?? null;

            $hint = 'Gözlənilən yer: ' . $item->absolutePath();

            if ($ranOn && $ranOn !== base_path()) {
                $hint .= ' · Arxiv başqa qovluqda yaradılıb: ' . $ranOn
                    . ' (' . ($item->meta['ran_on']['host'] ?? '—') . ')';
            }

            return '<span title="' . e($hint) . '">' . $this->badge('Fayl yoxdur', 'danger') . '</span>';
        }

        return $this->mutedCell($item->readableSize());
    }

    private function authorCell(Backup $item): string
    {
        return $this->mutedCell($item->admin?->full_name ?? 'Sistem');
    }

    private function itemActions(Backup $item): string
    {
        $admin   = auth('gopanel')->user();
        $buttons = [];

        if ($item->isDownloadable() && $admin?->can('gopanel.backup.index')) {
            $buttons[] = $this->linkBtn(
                route('gopanel.backup.download', $item),
                'fas fa-download',
                'Arxivi endir',
                'primary'
            );
        }

        if ($admin?->can('gopanel.backup.delete')) {
            // Ümumi silmə endpoint-i modul icazəsini yoxlamır - backup arxivi
            // bütün bazanı ehtiva etdiyi üçün öz route-u işlədilir.
            $buttons[] = $this->deleteRowBtn(
                $item,
                'Backup-ı sil',
                route('gopanel.backup.delete', $item)
            );
        }

        return $this->actionsCell($buttons);
    }
}
