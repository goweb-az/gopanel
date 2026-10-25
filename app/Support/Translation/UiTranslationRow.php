<?php

declare(strict_types=1);

namespace App\Support\Translation;

use App\Enums\Gopanel\TranslationGroups;
use App\Enums\Gopanel\TranslationPlatfroms;
use App\Helpers\Gopanel\TranslationPageRegistry;

/**
 * Bir UI tərcümə sətrinin (platform / page / group / key) yoxlanması - TƏK qayda.
 *
 * `translations:seed-site` (JSON) və paneldəki Excel idxalı eyni yoxlamadan
 * keçir. Niyə vacibdir: açar Blade-də `@lang('website.<page>.<group>.<key>')`
 * kimi çağırılır - nöqtəli və ya boşluqlu açar, kataloqda olmayan səhifə
 * bazaya yazılsa da saytda heç vaxt tapılmır və səhv səssiz qalır.
 */
final class UiTranslationRow
{
    /** Açar: kiçik hərf, rəqəm, `_` və `-`. Nöqtə QADAĞANDIR - dot-notation-u parçalayır. */
    public const KEY_PATTERN = '/^[a-z0-9_\-]+$/';

    /**
     * Sətri normallaşdırır: boşluqları kəsir, açarı kiçik hərfə salır, boş səhifəni `general` edir.
     *
     * @param  array<string, mixed>  $row
     * @return array{platform: string, page: string, group: ?string, key: string}
     */
    public static function normalize(array $row): array
    {
        $group = trim((string) ($row['group'] ?? ''));

        return [
            'platform' => strtolower(trim((string) ($row['platform'] ?? TranslationPlatfroms::WEBSITE->value))) ?: TranslationPlatfroms::WEBSITE->value,
            'page'     => trim((string) ($row['page'] ?? '')) ?: 'general',
            'group'    => $group === '' ? null : strtolower($group),
            'key'      => strtolower(trim((string) ($row['key'] ?? ''))),
        ];
    }

    /**
     * Normallaşmış sətrin xətaları (Azərbaycan dilində). Boş massiv = sətir düzgündür.
     *
     * @param  array{platform: string, page: string, group: ?string, key: string}  $row
     * @return array<int, string>
     */
    public static function errors(array $row, TranslationPageRegistry $pages): array
    {
        $errors = [];

        if (TranslationPlatfroms::tryFrom($row['platform']) === null) {
            $errors[] = "platforma «{$row['platform']}» tanınmır";
        } elseif (!$pages->exists($row['platform'], $row['page'])) {
            $errors[] = "səhifə «{$row['page']}» config/gopanel/translation_pages.php-də yoxdur";
        }

        if ($row['group'] !== null && TranslationGroups::tryFrom($row['group']) === null) {
            $errors[] = "qrup «{$row['group']}» yalnız title və ya content ola bilər";
        }

        if ($row['key'] === '') {
            $errors[] = 'açar boşdur';
        } elseif (!preg_match(self::KEY_PATTERN, $row['key'])) {
            $errors[] = "açar «{$row['key']}» yalnız kiçik hərf, rəqəm, _ və - ola bilər";
        }

        return $errors;
    }

    /** Blade-də çağırış forması - xəta mesajlarında və sənəddə göstərmək üçün. */
    public static function langKey(array $row): string
    {
        return implode('.', array_filter([$row['platform'], $row['page'], $row['group'], $row['key']], fn ($p) => $p !== null && $p !== ''));
    }
}
