<?php

declare(strict_types=1);

namespace App\Support\Export;

use App\Exports\Gopanel\ExportDefinition;
use InvalidArgumentException;

/**
 * Açar → ixrac tərifi reyestri (`config/custom/export.php` → `definitions`).
 *
 * NİYƏ lazımdır: ortaq endirmə və mail endpoint-ləri sinif adını
 * **istifadəçidən** ala bilməz - belə olsa panelə girişi olan hər kəs
 * istənilən sinfi qurdura bilərdi. Brauzer yalnız qısa açar göndərir, sinfi
 * və tələb olunan icazəni server bu reyestrdən tapır.
 *
 * Config-də yalnız sətirlər var - `config:cache` işləyir (closure saxlanılmır).
 *
 * Qurulma yolları:
 *   1. sinif `MailableExport`-dur - bir sətrə bağlı sənəd, `uid` ilə özünü tapır;
 *   2. qalanları - süzgəc massivi konstruktora verilir (siyahı ixracları).
 *
 * Layihəyə xas əlavə yol (məs. hesabat bölmələri) lazımdırsa, bu sinif
 * dəyişdirilmir - törəmə layihədə `MailableExport` implement edən adapter
 * tərif yazılır və reyestrə adi bənd kimi düşür.
 */
final class ExportRegistry
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        /** @var array<string, array<string, mixed>> $entries */
        $entries = (array) config('custom.export.definitions', []);

        return $entries;
    }

    /** @return array<string, mixed>|null */
    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    public static function has(string $key): bool
    {
        return self::find($key) !== null;
    }

    /** Açarın tələb etdiyi icazə - endirmə/göndərmədən əvvəl yoxlanılır. */
    public static function permission(string $key): ?string
    {
        $permission = self::find($key)['permission'] ?? null;

        return is_string($permission) && $permission !== '' ? $permission : null;
    }

    /** İnsan oxuyan ad - mailin mövzusunda və jurnalda görünür. */
    public static function label(string $key): string
    {
        return (string) (self::find($key)['label'] ?? $key);
    }

    /** Mailə göndərmək olarmı (bənddə `mail => false` yazılmayıbsa - bəli). */
    public static function mailable(string $key): bool
    {
        $entry = self::find($key);

        return $entry !== null && (bool) ($entry['mail'] ?? true);
    }

    /**
     * Mailə göndərilə bilən açarlar - FormRequest-in `Rule::in()` siyahısı.
     *
     * @return array<int, string>
     */
    public static function mailableKeys(): array
    {
        return array_values(array_filter(array_keys(self::all()), [self::class, 'mailable']));
    }

    /**
     * @param  array<string, mixed>  $params
     *
     * @throws InvalidArgumentException açar və ya sinif yanlışdırsa
     */
    public static function make(string $key, array $params = []): ExportDefinition
    {
        $entry = self::find($key);

        if ($entry === null) {
            throw new InvalidArgumentException('Tanınmayan ixrac açarı: ' . $key);
        }

        $class = (string) ($entry['export'] ?? '');

        if (!is_a($class, ExportDefinition::class, true)) {
            throw new InvalidArgumentException('İxrac sinfi ExportDefinition deyil: ' . $key);
        }

        if (is_a($class, MailableExport::class, true)) {
            /** @var class-string<ExportDefinition&MailableExport> $class */
            return $class::resolveExport($params);
        }

        return new $class($params);
    }
}
