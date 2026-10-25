<?php

declare(strict_types=1);

namespace App\Support\Export;

use App\Exports\Gopanel\ExportDefinition;
use Symfony\Component\HttpFoundation\Response;

/**
 * Formatı seçib düzgün exporter-ə yönləndirən tək giriş nöqtəsi.
 *
 * NİYƏ: endirmə, mail qoşması və diskə yazma (`ExportHandler`) eyni
 * exporter-lərdən keçir - mail ilə gələn fayl brauzerdən endirilənlə
 * **eynidir**, iki ayrı qurulma yolu yaranmır.
 */
final class ExportRenderer
{
    public const FORMATS = ['excel', 'pdf'];

    public function __construct(
        private readonly ExcelExporter $excel = new ExcelExporter(),
        private readonly PdfExporter $pdf = new PdfExporter(),
    ) {
    }

    /**
     * Yaddaşda fayl (mail qoşması üçün).
     *
     * @throws PdfUnavailableException PDF istənib, Dompdf qurulmayıbsa
     */
    public function render(ExportDefinition $export, string $format): ExportFile
    {
        if (self::normalizeFormat($format) === 'pdf') {
            $contents = $this->pdf->raw($export);

            return new ExportFile($this->pdf->fileName($export), PdfExporter::MIME, $contents, $this->pdf->lastRowCount());
        }

        $contents = $this->excel->raw($export);

        return new ExportFile($this->excel->fileName($export), ExcelExporter::MIME, $contents, $this->excel->lastRowCount());
    }

    /**
     * Brauzerə endirmə cavabı.
     *
     * @throws PdfUnavailableException
     */
    public function download(ExportDefinition $export, string $format): Response
    {
        return self::normalizeFormat($format) === 'pdf'
            ? $this->pdf->download($export)
            : $this->excel->download($export);
    }

    /**
     * Diskə yazır, yazılan sətir sayını qaytarır.
     *
     * @throws PdfUnavailableException
     */
    public function store(ExportDefinition $export, string $format, string $disk, string $path): int
    {
        return self::normalizeFormat($format) === 'pdf'
            ? $this->pdf->store($export, $disk, $path)
            : $this->excel->store($export, $disk, $path);
    }

    /**
     * Tanınmayan format Excel sayılır - Excel həmişə mövcuddur.
     * `xlsx` sinonimi `ExportContext::$extension` ilə uyğunluq üçündür.
     */
    public static function normalizeFormat(?string $format): string
    {
        $value = strtolower((string) $format);

        return $value === 'pdf' ? 'pdf' : 'excel';
    }

    /**
     * Bu serverdə həqiqətən işləyən formatlar - düymələr bununla qurulur ki,
     * Dompdf yoxdursa PDF bəndi heç göstərilməsin.
     *
     * @return array<int, string>
     */
    public static function availableFormats(): array
    {
        return PdfExporter::isAvailable() ? self::FORMATS : ['excel'];
    }

    public static function formatLabel(string $format): string
    {
        return self::normalizeFormat($format) === 'pdf' ? 'PDF' : 'Excel';
    }

    public static function extension(string $format): string
    {
        return self::normalizeFormat($format) === 'pdf' ? 'pdf' : 'xlsx';
    }
}
