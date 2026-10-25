<?php

declare(strict_types=1);

namespace App\Support\Export;

use App\Exports\Gopanel\ExportDefinition;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * `ExportDefinition`-i markalı `.xlsx` faylına çevirir (phpoffice/phpspreadsheet).
 *
 * Quruluş bütün ixraclarda eynidir: brend → sənəd başlığı → yaradılma tarixi →
 * süzgəclər → yekun → cədvəl(lər). NİYƏ: fayllar bir-birinə oxşasın və brend
 * (`ExportBranding`) bir yerdən dəyişsin - hər modul öz Excel-ini «əl ilə»
 * qurmasın.
 *
 * Üç çıxış: `download()` (brauzerə axın), `raw()` (mail qoşması üçün baytlar),
 * `store()` (diskə - queue-lu `ExportHandler`-lər üçün).
 */
final class ExcelExporter
{
    public const MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /** Son qurulmanın yazdığı məlumat sətirlərinin sayı (bütün bölmələr üzrə). */
    private int $lastRowCount = 0;

    public function download(ExportDefinition $export): StreamedResponse
    {
        $spreadsheet = $this->build($export);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $this->fileName($export), [
            'Content-Type'  => self::MIME,
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Faylın baytları - mail qoşması və diskə yazma üçün.
     *
     * NİYƏ müvəqqəti fayl: PhpSpreadsheet yalnız yola yaza bilir. `finally`
     * ilə həmişə silinir - növbə işçisi uzun yaşayır, qalıq yığılmamalıdır.
     */
    public function raw(ExportDefinition $export): string
    {
        $spreadsheet = $this->build($export);
        $path = tempnam(sys_get_temp_dir(), 'gp-export-');

        if ($path === false) {
            throw new RuntimeException('Müvəqqəti fayl yaradıla bilmədi.');
        }

        try {
            (new Xlsx($spreadsheet))->save($path);

            return (string) file_get_contents($path);
        } finally {
            $spreadsheet->disconnectWorksheets();
            @unlink($path);
        }
    }

    /**
     * Faylı diskə yazır və yazılan sətir sayını qaytarır.
     *
     * Disk PRIVATE olmalıdır (`config/custom/export.php` → `disk`) - hesabat
     * birbaşa URL ilə açılan yerdə saxlanılmır.
     */
    public function store(ExportDefinition $export, string $disk, string $path): int
    {
        Storage::disk($disk)->put($path, $this->raw($export));

        return $this->lastRowCount;
    }

    public function fileName(ExportDefinition $export): string
    {
        return $export->fileName() . '-' . now()->format('Y-m-d-His') . '.xlsx';
    }

    /** Son `download()`/`raw()`/`store()` çağırışında yazılan sətir sayı. */
    public function lastRowCount(): int
    {
        return $this->lastRowCount;
    }

    private function build(ExportDefinition $export): Spreadsheet
    {
        $this->lastRowCount = 0;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(self::safeSheetTitle($export->title()));

        $sections = $export->sections();

        $width = 1;
        foreach ($sections as $section) {
            $width = max($width, count($section->headings));
        }
        $lastColumn = Coordinate::stringFromColumnIndex($width);

        $row = $this->writeHeader($sheet, $export, $lastColumn);
        $limit = (int) config('custom.export.max_rows', 20000);

        foreach ($sections as $section) {
            if ($section->title !== '') {
                $sheet->setCellValue('A' . $row, $section->title);
                $sheet->mergeCells('A' . $row . ':' . $lastColumn . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11);
                $row++;
            }

            $headerRow = $row;
            $sectionLast = Coordinate::stringFromColumnIndex(max(1, count($section->headings)));

            $sheet->fromArray($section->headings, null, 'A' . $headerRow);
            $this->styleHeadingRow($sheet, $headerRow, $sectionLast);
            $row++;

            $written = 0;
            foreach ($section->rows as $line) {
                if ($written >= $limit) {
                    break;
                }

                // `strictNullComparison` - 0 dəyəri boş xana kimi itməsin.
                $sheet->fromArray(array_values((array) $line), null, 'A' . $row, true);
                $row++;
                $written++;
            }

            $this->lastRowCount += $written;

            if ($written === 0) {
                $sheet->setCellValue('A' . $row, 'Seçilmiş süzgəclərə uyğun məlumat yoxdur.');
                $sheet->mergeCells('A' . $row . ':' . $sectionLast . $row);
                $sheet->getStyle('A' . $row)->getFont()->setItalic(true);
                $row++;
            } else {
                $this->styleBody($sheet, $section->numericColumns, $headerRow + 1, $row - 1, $sectionLast);
            }

            $row++;
        }

        // `range('A', 'AB')` iki hərfli sütunları tanımır - indekslə gəzilir.
        for ($index = 1; $index <= $width; $index++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /** Brend, başlıq, süzgəc və yekun bloklarını yazır; ilk boş sətri qaytarır. */
    private function writeHeader(Worksheet $sheet, ExportDefinition $export, string $lastColumn): int
    {
        $row = 1;

        $this->wideText($sheet, $row, $lastColumn, ExportBranding::title(), 16, true, ExportBranding::color('accent'));
        $row++;

        $contact = ExportBranding::contactLine();
        if ($contact !== '') {
            $this->wideText($sheet, $row, $lastColumn, $contact, 10, false, ExportBranding::color('muted'));
            $row++;
        }
        $row++;

        $this->wideText($sheet, $row, $lastColumn, $export->title(), 13, true);
        $row++;

        $this->wideText($sheet, $row, $lastColumn, 'Yaradılma: ' . now()->format('d.m.Y H:i'), 10, false, ExportBranding::color('muted'));
        $row++;

        $filters = $export->appliedFilters();
        if ($filters !== []) {
            $text = collect($filters)->map(fn ($value, $label) => $label . ': ' . $value)->implode('   ·   ');
            $this->wideText($sheet, $row, $lastColumn, 'Süzgəclər - ' . $text, 10, false, ExportBranding::color('muted'));
            $row++;
        }

        $summary = $export->summary();
        if ($summary !== []) {
            $row++;
            $column = 1;
            foreach ($summary as $label => $value) {
                $sheet->setCellValue([$column, $row], $label);
                $sheet->setCellValue([$column + 1, $row], $value);
                $column += 2;
            }

            $summaryLast = Coordinate::stringFromColumnIndex(max(1, $column - 1));
            $sheet->getStyle('A' . $row . ':' . $summaryLast . $row)->applyFromArray([
                'font'    => ['bold' => true],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => ExportBranding::color('summary')]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => ExportBranding::color('border')]]],
            ]);
            $row++;
        }

        return $row + 1;
    }

    private function wideText(Worksheet $sheet, int $row, string $lastColumn, string $text, int $size, bool $bold, ?string $color = null): void
    {
        $sheet->setCellValue('A' . $row, $text);

        if ($lastColumn !== 'A') {
            $sheet->mergeCells('A' . $row . ':' . $lastColumn . $row);
        }

        $font = $sheet->getStyle('A' . $row)->getFont();
        $font->setSize($size)->setBold($bold);

        if ($color !== null) {
            $font->getColor()->setRGB($color);
        }
    }

    private function styleHeadingRow(Worksheet $sheet, int $row, string $lastColumn): void
    {
        $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => ExportBranding::color('header_text', 'FFFFFF')]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => ExportBranding::color('header')]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => ExportBranding::color('border')]]],
        ]);

        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    /** @param  array<int, int>  $numericColumns */
    private function styleBody(Worksheet $sheet, array $numericColumns, int $firstRow, int $lastRow, string $lastColumn): void
    {
        if ($lastRow < $firstRow) {
            return;
        }

        $sheet->getStyle('A' . $firstRow . ':' . $lastColumn . $lastRow)->applyFromArray([
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => ExportBranding::color('border')]]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        foreach ($numericColumns as $index) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->getStyle($letter . $firstRow . ':' . $letter . $lastRow)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
    }

    /** Excel vərəq adında `\ / ? * [ ] :` qadağandır, limit 31 simvoldur. */
    public static function safeSheetTitle(string $title): string
    {
        $clean = preg_replace('/[\\\\\/\?\*\[\]:]/u', '-', $title) ?? $title;
        $clean = mb_substr(trim($clean), 0, 31);

        return $clean !== '' ? $clean : 'Export';
    }
}
