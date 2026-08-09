<?php

declare(strict_types=1);

namespace App\Services\Export;

/**
 * Handler-in nəticəsi: hesabatın başlığı (mail/PDF üçün), sətir sayı və
 * ümumi göstəricilər (label => dəyər) - mail məzmununda xülasə üçün.
 */
class ExportResult
{
    public function __construct(
        public readonly string $title,
        public readonly int $rowCount,
        public readonly array $summary = [],
    ) {
    }
}
