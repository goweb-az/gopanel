<?php

declare(strict_types=1);

namespace App\Traits\System;

/**
 * «X tapılmadı» mesajı - modelin adından.
 *
 * Niyə: controller/servis hər yerdə `'Qeyd tapılmadı'` yazanda istifadəçi
 * nəyin tapılmadığını bilmir, API isə hər endpoint-də fərqli mətn qaytarır.
 * Model `HasLabel` işlədirsə (`protected static string $label = 'Status'`)
 * həmin ad, yoxdursa sinif adı sözlərə bölünərək götürülür.
 *
 *   ListingStatus::notFoundMessage();   // "Status tapılmadı." / "Listing Status tapılmadı."
 */
trait HasNotFoundMessage
{
    public static function notFoundMessage(): string
    {
        $name = method_exists(static::class, 'label')
            ? (string) static::label()
            : (string) preg_replace('/(?<!^)[A-Z]/', ' $0', class_basename(static::class));

        return trim($name) . ' tapılmadı.';
    }
}
