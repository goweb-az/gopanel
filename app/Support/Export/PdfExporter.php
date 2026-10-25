<?php

declare(strict_types=1);

namespace App\Support\Export;

use App\Exports\Gopanel\ExportDefinition;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * `ExportDefinition`-i PDF-ə çevirir - sütunlar Excel ilə eynidir.
 *
 * İSTƏYƏ BAĞLI ASILILIQ: `barryvdh/laravel-dompdf`. Starter onu tələb
 * etmir (hər layihəyə PDF lazım deyil, Dompdf isə ağır paketdir). Paket
 * yoxdursa `isAvailable()` false qaytarır və hər çıxış metodu
 * `PdfUnavailableException` atır - fatal «class not found» olmur.
 * Qoşmaq: `composer require barryvdh/laravel-dompdf`.
 *
 * Standart şablon `resources/views/gopanel/exports/pdf.blade.php`-dədir; bir
 * yerdə dəyişiklik bütün ixracların PDF-ini yeniləyir. Xüsusi sənəd (qaimə)
 * `ExportDefinition::pdfView()` ilə öz qəlibini verir.
 */
final class PdfExporter
{
    public const MIME = 'application/pdf';

    /** Dompdf sarğısının sinfi - sətir kimi, paket yoxdursa autoload xətası olmasın. */
    private const WRAPPER = 'Barryvdh\\DomPDF\\PDF';

    private int $lastRowCount = 0;

    public static function isAvailable(): bool
    {
        return class_exists(self::WRAPPER);
    }

    public function download(ExportDefinition $export): Response
    {
        return $this->make($export)->download($this->fileName($export));
    }

    /** Faylın baytları - mail qoşması üçün. */
    public function raw(ExportDefinition $export): string
    {
        return (string) $this->make($export)->output();
    }

    /** Diskə yazır, yazılan sətir sayını qaytarır. */
    public function store(ExportDefinition $export, string $disk, string $path): int
    {
        Storage::disk($disk)->put($path, $this->raw($export));

        return $this->lastRowCount;
    }

    public function fileName(ExportDefinition $export): string
    {
        return $export->fileName() . '-' . now()->format('Y-m-d-His') . '.pdf';
    }

    public function lastRowCount(): int
    {
        return $this->lastRowCount;
    }

    /**
     * Şablona ötürülən data - Dompdf olmadan da qurula bilir (test və
     * önizləmə üçün ayrıca açıqdır).
     *
     * @return array<string, mixed>
     */
    public function viewData(ExportDefinition $export): array
    {
        return [
            'branding'    => ExportBranding::toArray(),
            'accent'      => ExportBranding::cssColor('accent'),
            'headerColor' => ExportBranding::cssColor('header'),
            'headerText'  => ExportBranding::cssColor('header_text', 'FFFFFF'),
            'borderColor' => ExportBranding::cssColor('border'),
            'mutedColor'  => ExportBranding::cssColor('muted'),
            'summaryBg'   => ExportBranding::cssColor('summary'),
            'title'       => $this->safe($export->title()),
            'sections'    => $this->sections($export),
            'summary'     => array_map([$this, 'safe'], $export->summary()),
            'filters'     => array_map([$this, 'safe'], $export->appliedFilters()),
            'generatedAt' => now()->format('d.m.Y H:i'),
        ] + $export->pdfData();
    }

    /**
     * @return \Barryvdh\DomPDF\PDF
     */
    private function make(ExportDefinition $export): object
    {
        if (!self::isAvailable()) {
            throw PdfUnavailableException::missingPackage();
        }

        // Paketin service provider-i `dompdf.wrapper` bağlayır (config-i oxuyur);
        // provider söndürülübsə sinfin özü konteynerdən qurulur.
        /** @var \Barryvdh\DomPDF\PDF $pdf */
        $pdf = app()->bound('dompdf.wrapper') ? app('dompdf.wrapper') : app(self::WRAPPER);

        return $pdf->loadView($export->pdfView(), $this->viewData($export))
            ->setPaper((string) config('custom.export.pdf.paper', 'a4'), $export->orientation());
    }

    /**
     * Dompdf axın oxumur - sətirlər əvvəlcədən massivə yığılır (limitlə).
     *
     * @return array<int, array<string, mixed>>
     */
    private function sections(ExportDefinition $export): array
    {
        $this->lastRowCount = 0;
        $limit = (int) config('custom.export.max_rows', 20000);
        $sections = [];

        foreach ($export->sections() as $section) {
            $rows = [];

            foreach ($section->rows as $line) {
                if (count($rows) >= $limit) {
                    break;
                }

                $rows[] = array_map([$this, 'safe'], array_values((array) $line));
            }

            $this->lastRowCount += count($rows);

            $sections[] = [
                'title'    => $this->safe($section->title),
                'headings' => array_map([$this, 'safe'], $section->headings),
                'rows'     => $rows,
                'numeric'  => $section->numericColumns,
            ];
        }

        return $sections;
    }

    /**
     * PDF şriftinin tanımadığı işarələri əvəz edir.
     *
     * NİYƏ: Dompdf DejaVu Sans işlədir; orada manat işarəsi (U+20BC) YOXDUR
     * və sənəddə boş qutu kimi çıxır. Excel-də simvol düzgün görünür, ona
     * görə əvəzləmə yalnız PDF-dədir.
     */
    private function safe(mixed $value): mixed
    {
        return is_string($value) ? str_replace('₼', 'AZN', $value) : $value;
    }
}
