<?php

declare(strict_types=1);

namespace App\Support\Activity;

use App\Helpers\Common\ActivityLogHelper;
use App\Support\Date\MonthNames;
use Carbon\Carbon;

/**
 * Activity log dəyişikliklərinin insan-oxunaqlı formaya çevrilməsi - tək mənbə.
 *
 * İki istehlakçısı var və HƏR İKİSİ eyni etiket/dəyər qaydalarını işlətməlidir:
 *  - log sətrinin öz təsviri (`ActivityLogHelper` → `:changes` placeholder-i),
 *    «... giriş saatı: 09:00 → 10:00» kimi BİR cümlə;
 *  - paneldəki «Tarixçə» pəncərəsi, hər dəyişiklik AYRI sətir kimi.
 *
 * Etiketlər və dəyər xəritələri `config/custom/activity_messages.php`-dədir:
 *  - `<Model>.attribute_labels` → modelə xas adlar;
 *  - `attribute_labels`        → bütün modellər üçün ortaq adlar;
 *  - `<Model>.attribute_values`→ rəqəmin mənası (məs. is_workday: 1 → «iş günü»);
 *  - `date_only_attributes`    → saatı göstərilməyən tarix sahələri.
 *
 * ⚠️ DB-dəki xam dəyərlər Eloquent-in JSON serializasiyasından gəlir: tarixlər
 * UTC ISO sətri kimi (`2026-01-25T19:00:00.000000Z`) yazılır. Göstərməzdən
 * əvvəl tətbiqin saat qurşağına qaytarılır, əks halda gün bir gün sürüşür.
 */
final class ActivityChangeFormatter
{
    /**
     * Dəyişiklikləri sətir-sətir qaytarır (tarixçə pəncərəsi üçün).
     *
     * @param  array<string, mixed>  $new         `properties.attributes`
     * @param  array<string, mixed>  $old         `properties.old` (yaradılışda boş olur)
     * @param  array<string, array<int|string, string>>  $references
     *         `timesheet_id` kimi sahələrin id → ad qarşılığı (bax: ActivityReferences)
     * @param  array<int, string>  $hidden
     *         göstərilməyən sahələr - modelin `$logExcept` siyahısı (köhnə
     *         sətirlərdə həmin sahələr hələ də saxlanılır)
     * @return array<int, array{key: string, label: string, old: string|null, new: string, has_old: bool}>
     */
    public static function rows(string $modelName, array $new, array $old, array $references = [], array $hidden = []): array
    {
        $labels    = self::labels($modelName);
        $valueMaps = (array) config("custom.activity_messages.{$modelName}.attribute_values", []);

        $rows = [];

        foreach ($new as $key => $value) {
            if (self::isSensitive($key) || in_array((string) $key, $hidden, true)) {
                continue;
            }

            $hasOld = array_key_exists($key, $old);

            $rows[] = [
                'key'     => (string) $key,
                'label'   => $labels[$key] ?? (string) $key,
                'old'     => $hasOld ? self::display((string) $key, $old[$key], $valueMaps[$key] ?? null, $references) : null,
                'new'     => self::display((string) $key, $value, $valueMaps[$key] ?? null, $references),
                'has_old' => $hasOld,
            ];
        }

        return $rows;
    }

    /**
     * Eyni dəyişiklikləri BİR cümlə kimi qaytarır (log təsviri üçün).
     *
     * @param  array<string, mixed>  $new
     * @param  array<string, mixed>  $old
     */
    public static function sentence(string $modelName, array $new, array $old): string
    {
        $parts = [];

        foreach (self::rows($modelName, $new, $old) as $row) {
            $parts[] = $row['has_old']
                ? $row['label'] . ': ' . $row['old'] . ' → ' . $row['new']
                : $row['label'] . ': ' . $row['new'];
        }

        return $parts === [] ? '-' : implode(', ', $parts);
    }

    /**
     * Sahə adlarının siyahısı: modelə xas adlar ortaq siyahının üstündədir.
     *
     * @return array<string, string>
     */
    public static function labels(string $modelName): array
    {
        return array_merge(
            (array) config('custom.activity_messages.attribute_labels', []),
            (array) config("custom.activity_messages.{$modelName}.attribute_labels", []),
        );
    }

    /**
     * İstənilən dəyəri oxunaqlı sətrə çevirir (placeholder-lar üçün də işlənir).
     */
    public static function stringify(mixed $value): string
    {
        if (is_null($value) || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'bəli' : 'xeyr';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d.m.Y H:i');
        }

        if (is_array($value)) {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE);

            return $json === false ? '-' : mb_substr($json, 0, 200);
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            $json = json_encode($value, JSON_UNESCAPED_UNICODE);

            return $json === false ? '-' : mb_substr($json, 0, 200);
        }

        return (string) $value;
    }

    /* -----------------------------------------------------------------
     | Daxili
     | ----------------------------------------------------------------*/

    /**
     * Bir dəyərin son görünüşü: əvvəlcə istinad adı, sonra dəyər xəritəsi,
     * sonra tarix/saat formatı.
     *
     * @param  array<int|string, string>|null  $map
     * @param  array<string, array<int|string, string>>  $references
     */
    private static function display(string $key, mixed $value, ?array $map, array $references): string
    {
        // `timesheet_id: 12` əvəzinə qrafikin adı - id heç nə demir
        if (isset($references[$key]) && $value !== null && $value !== '') {
            $name = $references[$key][(string) $value] ?? null;

            if ($name !== null) {
                return $name;
            }
        }

        if ($map !== null) {
            $mapKey = is_bool($value) ? (int) $value : $value;

            if (is_scalar($mapKey) && isset($map[$mapKey])) {
                return (string) $map[$mapKey];
            }
        }

        /*
         * Həftənin günü rəqəm kimi saxlanılır («1»), amma mənası addır.
         * Adlar BURADA yazılmır - layihədə tək mənbə `MonthNames`-dir.
         */
        if (in_array($key, (array) config('custom.activity_messages.weekday_attributes', []), true)
            && is_numeric($value)) {
            return MonthNames::weekDayLong((int) $value);
        }

        return self::readable($key, $value);
    }

    /**
     * Saat və tarix dəyərlərinin qısa görünüşü.
     *
     * Bazada saat `H:i:s` saxlanılır - «09:00:00 → 10:00:00» əvəzinə
     * «09:00 → 10:00» daha oxunaqlıdır.
     */
    private static function readable(string $key, mixed $value): string
    {
        if (!is_string($value)) {
            return self::stringify($value);
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
            return substr($value, 0, 5);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return date('d.m.Y', strtotime($value));
        }

        // ISO tarix-saat (Eloquent JSON serializasiyası) - UTC-dən geri çevrilir
        if (preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}/', $value)) {
            try {
                $moment = Carbon::parse($value)->setTimezone(config('app.timezone'));
            } catch (\Throwable) {
                return self::stringify($value);
            }

            return self::isDateOnly($key)
                ? $moment->format('d.m.Y')
                : $moment->format('d.m.Y H:i');
        }

        return self::stringify($value);
    }

    /** Saatı mənasız olan sahələr (işə qəbul tarixi, məzuniyyət başlanğıcı...). */
    private static function isDateOnly(string $key): bool
    {
        $list = (array) config('custom.activity_messages.date_only_attributes', []);

        return in_array($key, $list, true) || str_ends_with($key, '_date');
    }

    private static function isSensitive(string|int $key): bool
    {
        return in_array(strtolower((string) $key), ActivityLogHelper::SENSITIVE_ATTRIBUTES, true);
    }
}
