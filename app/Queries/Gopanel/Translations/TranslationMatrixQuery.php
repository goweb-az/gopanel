<?php

declare(strict_types=1);

namespace App\Queries\Gopanel\Translations;

use Illuminate\Support\Facades\DB;

/**
 * UI tərcümələri «bir açar = bir sətir, hər dil = bir sütun» formasında.
 *
 * Bazada hər dil ayrı sətirdir; Excel-də isə tərcüməçi açarın bütün dillərini
 * yan-yana görməlidir. Matris burada qurulur - export servisi yalnız yazır.
 */
final class TranslationMatrixQuery
{
    public function __construct(
        private readonly ?string $platform = null,
        private readonly ?string $page = null,
    ) {
    }

    /**
     * @return array<int, array{platform: string, page: string, group: ?string, key: string, values: array<string, string>}>
     */
    public function rows(): array
    {
        $matrix = [];

        DB::table('translations')
            ->whereNull('deleted_at')
            ->whereNotNull('key')
            ->when($this->platform, fn ($q) => $q->where('platform', $this->platform))
            ->when($this->page, fn ($q) => $q->where('page', $this->page))
            ->orderBy('platform')
            ->orderBy('page')
            ->orderBy('group')
            ->orderBy('key')
            ->orderBy('id')
            ->select(['platform', 'page', 'group', 'key', 'locale', 'value'])
            ->cursor()
            ->each(function ($row) use (&$matrix) {
                $page = $row->page ?: 'general';
                $id   = "{$row->platform}|{$page}|{$row->group}|{$row->key}";

                $matrix[$id] ??= [
                    'platform' => (string) $row->platform,
                    'page'     => $page,
                    'group'    => $row->group,
                    'key'      => (string) $row->key,
                    'values'   => [],
                ];

                // Eyni kimlikdə təkrar sətir varsa (köhnə filename fərqi) ilk dəyər qalır
                $matrix[$id]['values'][$row->locale] ??= (string) $row->value;
            });

        return array_values($matrix);
    }
}
