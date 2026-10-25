<?php

declare(strict_types=1);

namespace App\Exports\Gopanel;

use App\Support\Export\ExportFilterLabels;
use App\Support\Export\ExportSection;

/**
 * Bir GoPanel ixracının tərifi - Excel və PDF **eyni** sinifdən oxuyur.
 *
 * NİYƏ tərif ayrıdır: sinif yalnız «hansı data, hansı başlıq» sualına cavab
 * verir; faylı necə qurmaq `ExcelExporter` / `PdfExporter` işidir. Beləliklə
 * iki format arasında sütun fərqi yarana bilmir və brend/başlıq/süzgəc bloku
 * bütün ixraclarda eyni görünür.
 *
 * Datanı **hər zaman** uyğun `Queries/Gopanel/...` sinfindən götürün -
 * datatable və ixrac eyni süzgəci işlətməlidir, əks halda fayl ekranda
 * görünəndən fərqli sətirlər verir.
 *
 * Minimum:
 *
 * ```php
 * final class BlogExport extends ExportDefinition
 * {
 *     public function __construct(private readonly array $filters = []) {}
 *
 *     public function title(): string { return 'Bloqlar'; }
 *     public function headings(): array { return ['ID', 'Başlıq', 'Tarix']; }
 *     public function rows(): iterable
 *     {
 *         foreach ((new BlogQuery($this->filters))->cursor() as $blog) {
 *             yield [$blog->id, $blog->title, $blog->created_at?->format('d.m.Y')];
 *         }
 *     }
 *     public function appliedFilters(): array { return $this->describeFilters($this->filters); }
 * }
 * ```
 *
 * Çoxcədvəlli sənəd (hesabat) üçün `sections()` üstələnir.
 */
abstract class ExportDefinition
{
    /** Sənədin başlığı (Excel vərəqinin adı, PDF-in başlığı, mailin mövzusu). */
    abstract public function title(): string;

    /**
     * Sütun başlıqları - tək cədvəlli ixraclar üçün.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [];
    }

    /**
     * Sətirlər - `headings()` ilə eyni sırada.
     * Böyük siyahılar üçün generator qaytarın (`yield` + `cursor()`/`lazy()`),
     * massiv yox - yaddaş sətir sayı ilə böyüməsin.
     *
     * @return iterable<int, array<int|string, string|int|float|null>>
     */
    public function rows(): iterable
    {
        return [];
    }

    /**
     * Sənədin cədvəlləri. Standart olaraq `headings()`/`rows()`-dan bir bölmə.
     *
     * @return array<int, ExportSection>
     */
    public function sections(): array
    {
        return [new ExportSection('', $this->headings(), $this->rows(), $this->numericColumns())];
    }

    /** Fayl adı (uzantısız) - tarix möhürü avtomatik əlavə olunur. */
    public function fileName(): string
    {
        $slug = str($this->title())->slug()->value();

        return $slug !== '' ? $slug : 'export';
    }

    /**
     * Başlığın altındakı yekun sətri: `['Ümumi' => '128', 'Məbləğ' => '4 210 ₼']`.
     *
     * @return array<string, string>
     */
    public function summary(): array
    {
        return [];
    }

    /**
     * Tətbiq olunmuş süzgəclər (oxunaqlı) - sənədin başlığında göstərilir ki,
     * faylı açan adam hansı kəsimi gördüyünü bilsin.
     *
     * @return array<string, string>
     */
    public function appliedFilters(): array
    {
        return [];
    }

    /**
     * Sağa düzülməli sütunların indeksləri (0-dan) - rəqəm və məbləğlər.
     *
     * @return array<int, int>
     */
    public function numericColumns(): array
    {
        return [];
    }

    /**
     * PDF şablonu. Standart qəlib bütün siyahılar üçün eynidir; yalnız
     * xüsusi tərtibatlı sənəd (qaimə, kart) öz şablonunu verir - cədvəllər
     * yenə eyni `sections()`-dan gəlir.
     */
    public function pdfView(): string
    {
        return 'gopanel.exports.pdf';
    }

    /**
     * Öz şablonuna əlavə dəyişənlər. Standart qəlib bunu işlətmir.
     *
     * @return array<string, mixed>
     */
    public function pdfData(): array
    {
        return [];
    }

    /**
     * PDF-də səhifə istiqaməti: `portrait` və ya `landscape`.
     *
     * Hədd `config/custom/export.php` → `pdf.landscape_after_columns`; geniş
     * cədvəl portret A4-də sütunları oxunmaz dərəcədə sıxır.
     *
     * DİQQƏT: `sections()` generator qaytarırsa bu metod onu yaratmır -
     * yalnız başlıq sayına baxır, sətirlər oxunmur.
     */
    public function orientation(): string
    {
        $limit = (int) config('custom.export.pdf.landscape_after_columns', 7);
        $widest = 0;

        foreach ($this->sections() as $section) {
            $widest = max($widest, count($section->headings));
        }

        return $widest > $limit ? 'landscape' : 'portrait';
    }

    /**
     * Xam süzgəcləri oxunaqlı başlıq sətrinə çevirmək üçün qısayol.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string, string>  $labels
     * @param  array<string, array<int|string, string>>  $values
     * @return array<string, string>
     */
    protected function describeFilters(array $filters, array $labels = [], array $values = []): array
    {
        return ExportFilterLabels::describe($filters, $labels, $values);
    }
}
