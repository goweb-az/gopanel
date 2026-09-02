<?php

declare(strict_types=1);

namespace App\Support\Files;

/**
 * Bayt dəyərinin oxunaqlı mətnə çevrilməsi: 5.4 GB, 12 MB, 340 KB.
 *
 * NİYƏ ayrıca sinif:
 * Format panelin bir neçə yerində lazımdır - backup siyahısı, disk göstəricisi,
 * fayl jurnalı, export nəticəsi. Hər yerdə ayrıca `round(... / 1024)` yazılanda
 * eyni ölçü bir səhifədə «5.4 GB», digərində «5487 MB» görünürdü. Format tək
 * yerdədir və blade-də hesablama qalmır (bax: 01-umumi.md § 3).
 */
final class ByteSize
{
    private const UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

    /**
     * @param  int|null  $bytes  mənfi və ya null dəyər `0 B` sayılır
     */
    public static function human(?int $bytes): string
    {
        $bytes = (int) $bytes;

        if ($bytes <= 0) {
            return '0 B';
        }

        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count(self::UNITS) - 1);

        // KB-a qədər onluq göstərmək mənasızdır - «340.0 KB» heç nə əlavə etmir
        $decimals = $power > 1 ? 1 : 0;

        return round($bytes / (1024 ** $power), $decimals) . ' ' . self::UNITS[$power];
    }

    /** Boş dəyəri tire ilə göstərir - cədvəldə «0 B» yanlış təsəvvür yaradır. */
    public static function humanOrDash(?int $bytes): string
    {
        return ((int) $bytes) <= 0 ? '—' : self::human($bytes);
    }
}
