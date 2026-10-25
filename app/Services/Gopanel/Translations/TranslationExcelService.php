<?php

declare(strict_types=1);

namespace App\Services\Gopanel\Translations;

use App\Enums\Gopanel\TranslationGroups;
use App\Helpers\Gopanel\TranslationPageRegistry;
use App\Models\Geography\Language;
use App\Queries\Gopanel\Translations\TranslationMatrixQuery;
use App\Support\Import\ImportReport;
use App\Support\Translation\UiTranslationRow;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use RuntimeException;

/**
 * UI tərcümələrinin Excel ixracı və idxalı - EYNİ format hər iki istiqamətdə.
 *
 * Niyə: production-dakı sözləri lokala/dev-ə (və ya əksinə) köçürmək üçün
 * yüklənən fayl heç bir düzəliş etmədən geri yüklənə bilməlidir. Ona görə
 * sütunlar TƏK yerdə ({@see self::BASE_COLUMNS}) təyin olunub və həm yazan,
 * həm oxuyan onu işlədir.
 *
 *   platform | page | group | key | az | en | ru ...   (dil sütunları = aktiv dillər)
 *
 * İdxalda boş dil xanası həmin dili SİLMİR, sadəcə ötürülür.
 */
final class TranslationExcelService
{
    /** Sabit sütunlar - dillər bunlardan sonra gəlir. */
    public const BASE_COLUMNS = ['platform', 'page', 'group', 'key'];

    /** Bir fayldakı maksimum sətir - brauzer sorğusu vaxt limitinə düşməsin. */
    public const MAX_ROWS = 20000;

    public function __construct(
        private readonly TranslationWriteService $writer,
        private readonly TranslationPageRegistry $pages,
    ) {
    }

    /**
     * Bazadakı tərcümələrdən iş kitabı qurur.
     */
    public function export(?string $platform = null, ?string $page = null): Spreadsheet
    {
        return $this->build((new TranslationMatrixQuery($platform, $page))->rows(), $this->locales());
    }

    /**
     * Verilmiş sətirlərdən iş kitabı - ixrac və nümunə fayl eyni qəlibdən çıxsın.
     *
     * @param  array<int, array{platform: string, page: string, group: ?string, key: string, values: array<string, string>}>  $rows
     * @param  array<int, string>  $locales
     */
    public function build(array $rows, array $locales): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tərcümələr');

        $header = array_merge(self::BASE_COLUMNS, $locales);
        $sheet->fromArray([$header], null, 'A1');

        $line = 2;

        foreach ($rows as $row) {
            $cells = [$row['platform'], $row['page'], $row['group'] ?? '', $row['key']];

            foreach ($locales as $locale) {
                $cells[] = $row['values'][$locale] ?? '';
            }

            // Mətn kimi yazılır - "001" və ya "=..." dəyəri rəqəmə/düsturə çevrilməsin
            foreach ($cells as $col => $value) {
                $sheet->setCellValueExplicit([$col + 1, $line], (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }

            $line++;
        }

        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($header));
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E9EEF6');
        $sheet->freezePane('E2');

        foreach (range(1, count($header)) as $col) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($letter)->setWidth($col <= count(self::BASE_COLUMNS) ? 18 : 45);
        }

        $this->addGuideSheet($spreadsheet);
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Excel faylını oxuyub yazır.
     *
     * @param  bool  $overwrite  true → mövcud dəyərlər də fayldakı ilə əvəz olunur
     * @return array{total: int, created: int, updated: int, skipped: int, failed: int, errors: array<int, string>}
     */
    public function import(UploadedFile $file, bool $overwrite): array
    {
        $total   = 0;
        $failed  = 0;
        $errors  = [];
        $rows    = [];
        $seen    = [];
        $locales = $this->locales();

        $sheet = IOFactory::load($file->getRealPath())->getSheet(0);
        $data  = $sheet->toArray(null, false, false, false);

        $map = $this->headerMap(array_shift($data) ?? [], $locales);

        if (count($data) > self::MAX_ROWS) {
            throw new RuntimeException('Faylda ' . count($data) . ' sətir var - maksimum ' . self::MAX_ROWS . '. Faylı səhifələrə bölün.');
        }

        foreach ($data as $index => $cells) {
            $line = $index + 2;   // Excel sətir nömrəsi (başlıq 1-cidir)

            if ($this->isEmptyRow($cells)) {
                continue;
            }

            $total++;

            $row = UiTranslationRow::normalize([
                'platform' => $cells[$map['platform']] ?? '',
                'page'     => $cells[$map['page']] ?? '',
                'group'    => isset($map['group']) ? ($cells[$map['group']] ?? '') : '',
                'key'      => $cells[$map['key']] ?? '',
            ]);

            $rowErrors = UiTranslationRow::errors($row, $this->pages);
            $identity  = UiTranslationRow::langKey($row);

            if ($rowErrors === [] && isset($seen[$identity])) {
                $rowErrors[] = "{$seen[$identity]}-ci sətirdə eyni açar artıq var";
            }

            if ($rowErrors !== []) {
                $failed++;
                $errors[] = "Sətir {$line}: " . implode('; ', $rowErrors);
                continue;
            }

            $seen[$identity] = $line;
            $hasValue        = false;

            foreach ($map['locales'] as $locale => $col) {
                $value = trim((string) ($cells[$col] ?? ''));

                if ($value === '') {
                    continue;   // boş xana həmin dili silmir
                }

                $hasValue = true;
                $rows[]   = $row + ['locale' => $locale, 'value' => $value];
            }

            if (!$hasValue) {
                $failed++;
                $errors[] = "Sətir {$line} ({$identity}): heç bir dildə mətn yoxdur";
            }
        }

        $stats = $this->writer->write($rows, $overwrite);

        return [
            'total'   => $total,
            'created' => $stats['created'],
            'updated' => $stats['updated'],
            'skipped' => $stats['skipped'],
            'failed'  => $failed,
            'errors'  => array_slice($errors, 0, 50),
        ];
    }

    /** Aktiv dillər (standart birinci) - sütun sırası. @return array<int, string> */
    public function locales(): array
    {
        return Language::query()
            ->where('is_active', true)
            ->orderByDesc('default')
            ->orderBy('sort_order')
            ->pluck('code')
            ->all();
    }

    /**
     * Başlıq sətrindən sütun xəritəsi. Sütunların SIRASI vacib deyil - adı vacibdir.
     *
     * @param  array<int, mixed>  $header
     * @param  array<int, string>  $locales
     * @return array{platform: int, page: int, group?: int, key: int, locales: array<string, int>}
     */
    private function headerMap(array $header, array $locales): array
    {
        $normalized = array_map(fn ($cell) => strtolower(trim((string) $cell)), $header);
        $map        = ['locales' => []];

        foreach ($normalized as $col => $name) {
            if (in_array($name, self::BASE_COLUMNS, true)) {
                $map[$name] = $col;
            } elseif (in_array($name, $locales, true)) {
                $map['locales'][$name] = $col;
            }
        }

        $missing = array_diff(['platform', 'page', 'key'], array_keys($map));

        if ($missing !== []) {
            throw new RuntimeException('Başlıq sətrində sütun yoxdur: ' . implode(', ', $missing)
                . '. Nümunə faylı yükləyib onun formatından istifadə edin.');
        }

        if ($map['locales'] === []) {
            throw new RuntimeException('Başlıqda aktiv dil sütunu yoxdur (' . implode(', ', $locales) . ').');
        }

        return $map;
    }

    /** @param  array<int, mixed>  $cells */
    private function isEmptyRow(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * İkinci vərəq: icazəli səhifələr və qruplar - tərcüməçi faylı panelə baxmadan doldura bilsin.
     * İdxal yalnız birinci vərəqi oxuyur.
     */
    private function addGuideSheet(Spreadsheet $spreadsheet): void
    {
        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Bələdçi');

        $lines = [
            ['Sütun / dəyər', 'İzah'],
            ['platform', 'website (sayt), mobile, admin, panel'],
            ['group', 'title - qısa mətn (başlıq, düymə); content - uzun mətn (paraqraf)'],
            ['key', 'kiçik hərf, rəqəm, _ və - (nöqtə olmaz). Blade: @lang(\'website.<page>.<group>.<key>\')'],
            ['boş dil xanası', 'idxalda həmin dil dəyişmir'],
            ['', ''],
            ['platform', 'page (səhifə)'],
        ];

        foreach ($this->pages->all() as $platform => $pages) {
            foreach ($pages as $page => $label) {
                $lines[] = [$platform, "{$page} - {$label}"];
            }
        }

        $lines[] = ['', ''];
        foreach (TranslationGroups::cases() as $group) {
            $lines[] = ['group: ' . $group->value, $group->getLabel()];
        }

        $guide->fromArray($lines, null, 'A1');
        $guide->getStyle('A1:B1')->getFont()->setBold(true);
        $guide->getColumnDimension('A')->setWidth(22);
        $guide->getColumnDimension('B')->setWidth(90);
    }
}
