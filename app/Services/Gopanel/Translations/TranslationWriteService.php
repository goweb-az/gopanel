<?php

declare(strict_types=1);

namespace App\Services\Gopanel\Translations;

use App\Models\Translations\Translation;
use Illuminate\Support\Facades\DB;

/**
 * UI tərcümələrinin TOPLU yazılışı - `translations:seed-site` command-i və
 * paneldəki Excel idxalı eyni yoldan keçir.
 *
 * Niyə ortaq: iki yer ayrı yazsaydı biri soft-delete olunmuş sətri bərpa
 * edərdi, digəri etməzdi; biri lang faylını yeniləyər, digəri unudardı -
 * nəticədə «idxal uğurlu, saytda isə görünmür» halları yaranırdı.
 *
 * Yazılış query builder ilədir: `Translation` modelinin `saved` hadisəsi HƏR
 * sətirdə bütün lang faylını yenidən yazır (500 sətir = 500 fayl yazısı).
 * Burada fayllar sonda hər (dil, platforma) üçün BİR dəfə yenilənir.
 *
 * Kimlik: platform + page + group + key + locale (filename = platform).
 */
final class TranslationWriteService
{
    public function __construct(
        private readonly TranslationCacheService $cache,
    ) {
    }

    /**
     * @param  iterable<int, array{platform: string, page: string, group: ?string, key: string, locale: string, value: string}>  $rows
     * @param  bool  $overwrite  false → yalnız ÇATIŞMAYAN sətirlər yazılır (paneldə dəyişdirilmiş mətn əzilmir)
     * @return array{created: int, updated: int, skipped: int}
     */
    public function write(iterable $rows, bool $overwrite = false, bool $dryRun = false): array
    {
        $rows = is_array($rows) ? $rows : iterator_to_array($rows, false);
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        if ($rows === []) {
            return $stats;
        }

        $existing = $this->existingRows(array_unique(array_column($rows, 'platform')));
        $now      = now();
        $insert   = [];
        $updates  = [];
        $touched  = [];

        foreach ($rows as $row) {
            $found = $existing[self::identity($row)] ?? null;

            // Soft-delete olunmuş sətir unikal indeksdə yer tutur - insert yox, bərpa edilir.
            if ($found && !$overwrite && $found->deleted_at === null) {
                $stats['skipped']++;
                continue;
            }

            if ($found) {
                $stats['updated']++;
                $updates[$found->id] = $row['value'];
            } else {
                $stats['created']++;
                $insert[] = [
                    'key'        => $row['key'],
                    'locale'     => $row['locale'],
                    'value'      => $row['value'],
                    'group'      => $row['group'],
                    'platform'   => $row['platform'],
                    'page'       => $row['page'],
                    'filename'   => $row['platform'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $touched["{$row['locale']}|{$row['platform']}"] = [$row['locale'], $row['platform']];
        }

        if ($dryRun || ($insert === [] && $updates === [])) {
            return $stats;
        }

        DB::transaction(function () use ($insert, $updates, $now) {
            foreach (array_chunk($insert, 500) as $chunk) {
                DB::table('translations')->insert($chunk);
            }

            foreach ($updates as $id => $value) {
                DB::table('translations')->where('id', $id)->update([
                    'value'      => $value,
                    'deleted_at' => null,
                    'updated_at' => $now,
                ]);
            }
        });

        $locales = [];

        foreach ($touched as [$locale, $platform]) {
            Translation::regenerateFiles($locale, $platform, $platform);
            $locales[] = $locale;
        }

        $this->cache->flush(array_unique($locales));

        return $stats;
    }

    /**
     * Bazadakı sətirlər kimlik açarı ilə (silinmişlər daxil - bərpa üçün).
     *
     * @param  array<int, string>  $platforms
     * @return array<string, object>
     */
    private function existingRows(array $platforms): array
    {
        $map = [];

        DB::table('translations')
            ->whereIn('platform', $platforms)
            ->orderBy('id')
            ->get(['id', 'platform', 'page', 'group', 'key', 'locale', 'deleted_at'])
            ->each(function ($row) use (&$map) {
                // Eyni kimlikdə bir neçə sətir varsa (köhnə filename fərqi) ilki işlədilir
                $map[self::identity((array) $row)] ??= $row;
            });

        return $map;
    }

    /** @param  array<string, mixed>  $row */
    private static function identity(array $row): string
    {
        return implode('|', [
            $row['platform'],
            $row['page'] ?: 'general',
            $row['group'] ?? '',
            $row['key'],
            $row['locale'],
        ]);
    }
}
