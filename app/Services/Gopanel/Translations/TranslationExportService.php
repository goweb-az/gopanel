<?php

namespace App\Services\Gopanel\Translations;

use App\Models\Translations\Translation;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TranslationExportService
{
    /**
     * Export translation rows to deterministic JSON seeder files, grouped by
     * platform + page. Optional platform/page filters restrict the scope.
     *
     * Files are written to database/seeders/json-data/translations/ only, using
     * a temp-file + atomic rename. Null values are excluded.
     *
     * @return array{file_count:int,row_count:int,files:array<int,string>}
     */
    public function export(array $filters): array
    {
        $platform = $filters['platform'] ?? null;
        $page     = $filters['page'] ?? null;

        $rows = Translation::query()
            ->whereNotNull('key')
            ->whereNotNull('value')
            ->when($platform, fn ($q) => $q->where('platform', $platform))
            ->when($page, fn ($q) => $q->where('page', $page))
            ->orderBy('platform')
            ->orderBy('page')
            ->orderBy('locale')
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        // Group rows by platform + page.
        $groups = [];
        foreach ($rows as $row) {
            $rowPlatform = $row->platform ?: 'website';
            $rowPage     = $row->page ?: 'general';
            $groups[$rowPlatform][$rowPage][] = [
                'key'      => $row->key,
                'locale'   => $row->locale,
                'value'    => $row->value,
                'platform' => $rowPlatform,
                'page'     => $rowPage,
                'group'    => $row->group,
                'filename' => $row->filename,
            ];
        }

        $directory = database_path('seeders/json-data/translations');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        // Resolve the real base path once so we can guard against traversal.
        $baseReal = realpath($directory);

        $files    = [];
        $rowCount = 0;

        foreach ($groups as $groupPlatform => $pages) {
            foreach ($pages as $groupPage => $entries) {
                $safePlatform = $this->sanitizeSegment($groupPlatform);
                $safePage     = $this->sanitizeSegment($groupPage);

                $fileName = "translations-{$safePlatform}-{$safePage}.json";
                $fullPath = $directory . DIRECTORY_SEPARATOR . $fileName;

                // Guard: the resolved parent directory must stay inside the base.
                // Normalize both sides via realpath so OS path-separator/casing
                // differences don't produce false mismatches (e.g. on Windows).
                $parentReal = realpath(dirname($fullPath));
                if ($baseReal === false || $parentReal === false || $parentReal !== $baseReal) {
                    throw new RuntimeException('İxrac yolu icazə verilən qovluqdan kənara çıxır.');
                }

                $json = json_encode(
                    $entries,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                );

                // Atomic write: temp file in the same dir, then rename.
                $tmp = tempnam($directory, 'tr_');
                file_put_contents($tmp, $json);
                @chmod($tmp, 0644);
                rename($tmp, $fullPath);

                $files[]  = "database/seeders/json-data/translations/{$fileName}";
                $rowCount += count($entries);
            }
        }

        Log::info('Translations exported to JSON seeder files.', [
            'admin_id'   => auth('gopanel')->id(),
            'filters'    => ['platform' => $platform, 'page' => $page],
            'file_count' => count($files),
            'row_count'  => $rowCount,
        ]);

        return [
            'file_count' => count($files),
            'row_count'  => $rowCount,
            'files'      => $files,
        ];
    }

    /**
     * Allow only alphanumerics, underscore and hyphen in filename segments.
     */
    private function sanitizeSegment(string $segment): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_-]/', '', $segment);

        if ($clean === '' || $clean === null) {
            throw new RuntimeException('Platforma/səhifə adı fayl adı üçün yararsızdır.');
        }

        return $clean;
    }
}
