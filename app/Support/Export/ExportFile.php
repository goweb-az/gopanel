<?php

declare(strict_types=1);

namespace App\Support\Export;

/**
 * Yaddaşda hazır ixrac faylı - ad, MIME, baytlar və yazılan sətir sayı.
 *
 * NİYƏ: endirmə cavabı `Response` qaytarır, mail qoşması və diskə yazma isə
 * faylın məzmununu istəyir. Bu DTO ikinci halı təmsil edir; `rowCount`
 * `ExportResult`-a (mail xülasəsi, jurnal) ötürülür.
 */
final class ExportFile
{
    public function __construct(
        public readonly string $name,
        public readonly string $mime,
        public readonly string $contents,
        public readonly int $rowCount = 0,
    ) {
    }

    public function sizeKb(): int
    {
        return (int) ceil(strlen($this->contents) / 1024);
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }
}
