<?php

namespace App\Services\Gopanel\Translations;

use App\Models\Translations\Translation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class TranslationBulkImportService
{
    public function __construct(
        private TranslationCacheService $cache
    ) {
    }

    /**
     * Import a flat key/value file (JSON or XLSX) into the translations table.
     *
     * $data must contain: import_type, locale, platform, page, group (nullable), mode (update|skip).
     * Parsing happens fully before any DB write; the write itself is a single
     * chunked upsert (bypassing per-row Eloquent save()/model events), and the
     * generated lang/json files are regenerated once at the end.
     *
     * @return array{total_rows:int,created:int,updated:int,skipped:int,failed:int,errors:array<int,string>}
     */
    public function import(array $data, UploadedFile $file): array
    {
        $result = [
            'total_rows' => 0,
            'created'    => 0,
            'updated'    => 0,
            'skipped'    => 0,
            'failed'     => 0,
            'errors'     => [],
        ];

        $locale   = $data['locale'];
        $platform = $data['platform'];
        $page     = $data['page'] ?: 'general';
        $group    = $data['group'] ?? null;
        $filename = $data['filename'] ?? $platform;
        $mode     = $data['mode'] ?? 'update';

        // 1) Parse the file into normalized [key => value] pairs BEFORE any DB write.
        try {
            $rows = $data['import_type'] === 'xlsx'
                ? $this->parseXlsx($file, $result)
                : $this->parseJson($file, $result);
        } catch (Throwable $e) {
            $result['errors'][] = 'Fayl oxunarkən xəta baş verdi: ' . $e->getMessage();
            return $result;
        }

        $result['total_rows'] = count($rows) + $result['failed'];

        if (empty($rows)) {
            return $result;
        }

        // 2) Determine which identities already exist so we can count / respect skip mode.
        $keys = array_keys($rows);

        $existing = Translation::query()
            ->where('locale', $locale)
            ->where('platform', $platform)
            ->where('page', $page)
            ->where('filename', $filename)
            ->when($group !== null, fn ($q) => $q->where('group', $group))
            ->when($group === null, fn ($q) => $q->whereNull('group'))
            ->whereIn('key', $keys)
            ->pluck('key')
            ->all();

        $existingKeys = array_flip($existing);

        $now  = now();
        $upsertRows = [];

        foreach ($rows as $key => $value) {
            $alreadyExists = isset($existingKeys[$key]);

            if ($alreadyExists && $mode === 'skip') {
                $result['skipped']++;
                continue;
            }

            if ($alreadyExists) {
                $result['updated']++;
            } else {
                $result['created']++;
            }

            $upsertRows[] = [
                'key'        => $key,
                'locale'     => $locale,
                'value'      => $value,
                'group'      => $group,
                'platform'   => $platform,
                'page'       => $page,
                'filename'   => $filename,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($upsertRows)) {
            return $result;
        }

        // 3) Persist in one transaction with chunked upserts (no per-row model events).
        try {
            DB::transaction(function () use ($upsertRows, $mode) {
                foreach (array_chunk($upsertRows, 500) as $chunk) {
                    Translation::upsert(
                        $chunk,
                        ['key', 'locale', 'platform', 'page', 'group', 'filename'],
                        // In skip mode we already filtered existing rows out, so update
                        // only matters for update mode; either way updating value/updated_at
                        // is safe because skip-mode rows are never in this set.
                        ['value', 'updated_at']
                    );
                }
            });
        } catch (Throwable $e) {
            // Roll back happened automatically; report the whole batch as failed.
            $result['failed'] += $result['created'] + $result['updated'];
            $result['created'] = 0;
            $result['updated'] = 0;
            $result['errors'][] = 'Yazma zamanı xəta baş verdi, dəyişikliklər geri qaytarıldı: ' . $e->getMessage();
            return $result;
        }

        // 4) Regenerate the generated lang/json files ONCE, and invalidate runtime cache.
        Translation::regenerateFiles($locale, $filename, $platform);
        $this->cache->forgetLocales([$locale]);

        return $result;
    }

    /**
     * Parse a flat JSON object into normalized key/value pairs.
     * Nested objects/arrays are rejected per-row.
     */
    private function parseJson(UploadedFile $file, array &$result): array
    {
        $contents = file_get_contents($file->getRealPath());
        $decoded  = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new \RuntimeException('JSON düz (flat) açar-dəyər obyekti olmalıdır.');
        }

        return $this->normalizeRows($decoded, $result);
    }

    /**
     * Parse an XLSX whose first row is the "key | value" header.
     */
    private function parseXlsx(UploadedFile $file, array &$result): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();

        $raw = [];
        $header = null;

        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            $cells = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                $cells[] = $cell->getValue();
            }

            if ($rowIndex === 1) {
                $header = array_map(fn ($h) => strtolower(trim((string) $h)), array_slice($cells, 0, 2));
                if (($header[0] ?? null) !== 'key' || ($header[1] ?? null) !== 'value') {
                    throw new \RuntimeException('XLSX faylının ilk sətri "key | value" başlığı olmalıdır.');
                }
                continue;
            }

            $key   = $cells[0] ?? null;
            $value = $cells[1] ?? null;

            if ($key === null && $value === null) {
                continue; // fully empty row
            }

            $raw[(string) $key] = $value;
        }

        return $this->normalizeRows($raw, $result);
    }

    /**
     * Trim outer whitespace, reject empty keys, reject nested values, and
     * report duplicate keys. Returns [key => value] keeping the first
     * occurrence of any duplicate.
     */
    private function normalizeRows(array $raw, array &$result): array
    {
        $normalized = [];
        $seen = [];
        $rowNumber = 0;

        foreach ($raw as $key => $value) {
            $rowNumber++;
            $key = trim((string) $key);

            if ($key === '') {
                $result['failed']++;
                $result['errors'][] = "Sətir {$rowNumber}: açar boşdur.";
                continue;
            }

            if (is_array($value)) {
                $result['failed']++;
                $result['errors'][] = "Sətir {$rowNumber} ({$key}): dəyər iç-içə obyekt/massiv ola bilməz.";
                continue;
            }

            if (isset($seen[$key])) {
                $result['failed']++;
                $result['errors'][] = "Sətir {$rowNumber}: təkrarlanan açar \"{$key}\".";
                continue;
            }

            $seen[$key] = true;
            $normalized[$key] = $value === null ? null : trim((string) $value);
        }

        return $normalized;
    }
}
