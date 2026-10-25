<?php

declare(strict_types=1);

namespace App\Support\Export;

use RuntimeException;

/**
 * PDF formatı istənilib, amma `barryvdh/laravel-dompdf` qurulmayıb.
 *
 * NİYƏ ayrıca istisna: PDF starter-də İSTƏYƏ BAĞLIDIR. Paket yoxdursa
 * «Class not found» fatal xətası əvəzinə controller bunu tutub admini
 * Azərbaycan dilində aydın mesajla qarşılayır, Excel isə işləməyə davam edir.
 */
final class PdfUnavailableException extends RuntimeException
{
    public static function missingPackage(): self
    {
        return new self(
            'PDF ixracı aktiv deyil: serverdə "barryvdh/laravel-dompdf" paketi qurulmayıb. '
            . 'Administrator "composer require barryvdh/laravel-dompdf" icra etməlidir. '
            . 'Hələlik Excel formatından istifadə edin.'
        );
    }
}
