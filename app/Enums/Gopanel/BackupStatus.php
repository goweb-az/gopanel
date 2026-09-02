<?php

declare(strict_types=1);

namespace App\Enums\Gopanel;

/** Backup-ın növbədəki vəziyyəti. */
enum BackupStatus: string
{
    case Pending   = 'pending';
    case Running   = 'running';
    case Completed = 'completed';
    case Failed    = 'failed';

    public function title(): string
    {
        return match ($this) {
            self::Pending   => 'Növbədə',
            self::Running   => 'İşləyir',
            self::Completed => 'Hazır',
            self::Failed    => 'Xəta',
        };
    }

    /** Cədvəl nişanının rəngi (`RendersRichCells::badge`). */
    public function tone(): string
    {
        return match ($this) {
            self::Pending   => 'secondary',
            self::Running   => 'primary',
            self::Completed => 'success',
            self::Failed    => 'danger',
        };
    }

    /** Hələ bitməyib - səhifə avtomatik yenilənməlidir. */
    public function inProgress(): bool
    {
        return $this === self::Pending || $this === self::Running;
    }
}
