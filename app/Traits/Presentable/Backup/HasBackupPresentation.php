<?php

namespace App\Traits\Presentable\Backup;

use App\Support\Date\LocalizedDateFormatter;
use App\Support\Files\ByteSize;

/**
 * `Backup` modelinin göstəriş qatı: ölçü, müddət, növ/vəziyyət adları.
 *
 * Niyə: `readableSize()` və `duration()` həm `BackupDatatable`, həm də
 * `BackupService` (səhifə başlığı kartları) tərəfindən çağırılır - metod adları
 * və imzaları DƏYİŞMƏYİB, sadəcə modeldən bura köçüb. Yeni `*_label`/`*_class`
 * accessor-ları enum-un öz `title()`/`tone()`-unu çağırır - mətn ikinci dəfə
 * yazılmır.
 *
 * HTML yoxdur: nişanı datatable (`RendersRichCells::badge()`) çəkir.
 */
trait HasBackupPresentation
{
    /** Oxunaqlı ölçü: 5.4 GB, 12 MB... Format `ByteSize`-dədir. */
    public function readableSize(): string
    {
        return ByteSize::humanOrDash((int) $this->size);
    }

    /** Nə qədər çəkib. */
    public function duration(): ?string
    {
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }

        $seconds = (int) $this->finished_at->diffInSeconds($this->started_at);

        if ($seconds < 60) {
            return $seconds . ' san.';
        }

        return intdiv($seconds, 60) . ' dəq. ' . ($seconds % 60) . ' san.';
    }

    public function getSizeFormattedAttribute(): string
    {
        return $this->readableSize();
    }

    public function getDurationFormattedAttribute(): ?string
    {
        return $this->duration();
    }

    public function getTypeLabelAttribute(): ?string
    {
        return $this->type?->title();
    }

    /** Font Awesome 5 sinfi (`fas fa-database`). */
    public function getTypeIconAttribute(): ?string
    {
        return $this->type?->icon();
    }

    public function getStatusLabelAttribute(): ?string
    {
        return $this->status?->title();
    }

    public function getStatusClassAttribute(): string
    {
        return $this->status?->tone() ?? 'secondary';
    }

    /** `mode` sütunu yalnız fayl backup-ında doludur (full / incremental). */
    public function getModeLabelAttribute(): ?string
    {
        return match ($this->mode) {
            'full'        => 'Tam arxiv',
            'incremental' => 'Yalnız yeni fayllar',
            default       => null,
        };
    }

    /** Scheduler-dən gələn backup-ın müəllifi yoxdur - «Sistem». */
    public function getAuthorNameAttribute(): string
    {
        return $this->admin?->full_name ?? 'Sistem';
    }

    public function getStartedAtFormattedAttribute(): ?string
    {
        return LocalizedDateFormatter::dateTime($this->started_at);
    }

    public function getFinishedAtFormattedAttribute(): ?string
    {
        return LocalizedDateFormatter::dateTime($this->finished_at);
    }
}
