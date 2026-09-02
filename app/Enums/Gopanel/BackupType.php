<?php

declare(strict_types=1);

namespace App\Enums\Gopanel;

/** Backup növü. */
enum BackupType: string
{
    case Database = 'database';
    case Files    = 'files';

    public function title(): string
    {
        return match ($this) {
            self::Database => 'Baza',
            self::Files    => 'Fayllar',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Database => 'fas fa-database',
            self::Files    => 'fas fa-images',
        };
    }

    /** Arxivin `storage/app/backups/` altındakı alt qovluğu. */
    public function folder(): string
    {
        return $this->value;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
