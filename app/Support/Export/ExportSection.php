<?php

declare(strict_types=1);

namespace App\Support\Export;

/**
 * İxrac sənədindəki bir cədvəl.
 *
 * NİYƏ ayrıca sinif: adi siyahı ixracı bir bölmədən ibarətdir, hesabat isə
 * bir neçə cədvəl qaytarır (günlük seriya, status bölgüsü, top-10...).
 * Excel və PDF exporter-ləri yalnız bölmə siyahısını tanıyır - beləliklə
 * tək və çoxcədvəlli sənəd eyni koddan keçir.
 */
final class ExportSection
{
    /**
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int|string, string|int|float|null>>  $rows  massiv və ya generator
     * @param  array<int, int>  $numericColumns  sağa düzülən sütunların indeksləri (0-dan)
     */
    public function __construct(
        public readonly string $title,
        public readonly array $headings,
        public readonly iterable $rows,
        public readonly array $numericColumns = [],
    ) {
    }
}
