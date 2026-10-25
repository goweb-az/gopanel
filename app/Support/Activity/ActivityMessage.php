<?php

declare(strict_types=1);

namespace App\Support\Activity;

use App\Helpers\Common\ActivityLogHelper;
use Illuminate\Database\Eloquent\Model;

/**
 * Domen hadisəsinin mesajını `config/custom/activity_messages.php` qəlibindən qurur.
 *
 * Niyə ayrıca sinif: `ActivityLogHelper::resolveDescription()` yalnız spatie
 * `Activity` obyekti ilə işləyir (model hadisəsi). `ActivityLogger` isə model
 * hadisəsindən KƏNAR yazır - «status dəyişdi», «qeyd üzərinə götürüldü» - və
 * onun əlində `Activity` yoxdur, əlavə kontekst (`:amount`, `:assignee`) var.
 *
 * Placeholder qaydaları `ActivityLogHelper` ilə eynidir:
 *  - `:causer`  → əməliyyatı edən (yoxdursa «Sistem»)
 *  - `:changes` → `ActivityChangeFormatter::sentence()` (tək mənbə)
 *  - `:x`       → əvvəlcə kontekst, sonra modelin atributu; yoxdursa «-»
 *  - həssas ad (`:password`) → «***»
 */
final class ActivityMessage
{
    /**
     * @param  array{attributes?: array<string, mixed>, old?: array<string, mixed>}|null  $changeSet
     * @param  array<string, mixed>  $extra  hadisənin öz dəyərləri (modeldən üstündür)
     */
    public static function render(
        ?Model $model,
        string $event,
        ?array $changeSet = null,
        ?string $group = null,
        array $extra = [],
    ): string {
        // Qrup açıq verilməyibsə modelin qısa sinif adıdır; açıq qrup model
        // olmayan hadisə ailələri üçündür (`Integration`, `Import`).
        $group ??= $model ? class_basename($model) : 'System';
        $template = config("custom.activity_messages.{$group}.{$event}");

        if (! is_string($template)) {
            return self::fallback($group, $event);
        }

        // Uzun adlar əvvəl - `:product_name` `:product`-dan əvvəl əvəzlənsin.
        preg_match_all('/:([a-z0-9_]+)/i', $template, $matches);
        $placeholders = array_unique($matches[1]);
        usort($placeholders, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($placeholders as $name) {
            $template = str_replace(":{$name}", self::value($model, $name, $group, $changeSet, $extra), $template);
        }

        // Dəyişiklik siyahısı boş olanda qalan «- -» quyruğunu at
        return trim((string) preg_replace('/\s*[-–—]\s*-\s*$/u', '', $template));
    }

    /** Bu hadisə üçün config-də hazır qəlib varmı. */
    public static function hasTemplate(string $group, string $event): bool
    {
        return is_string(config("custom.activity_messages.{$group}.{$event}"));
    }

    /**
     * @param  array{attributes?: array<string, mixed>, old?: array<string, mixed>}|null  $changeSet
     * @param  array<string, mixed>  $extra
     */
    private static function value(?Model $model, string $name, string $group, ?array $changeSet, array $extra): string
    {
        if ($name === 'causer') {
            return ActivityLogHelper::causerName();
        }

        if (in_array(strtolower($name), ActivityLogHelper::SENSITIVE_ATTRIBUTES, true)) {
            return '***';
        }

        if ($name === 'changes') {
            $new = (array) ($changeSet['attributes'] ?? []);

            return $new === []
                ? '-'
                : ActivityChangeFormatter::sentence($group, $new, (array) ($changeSet['old'] ?? []));
        }

        if (array_key_exists($name, $extra)) {
            return ActivityChangeFormatter::stringify($extra[$name]);
        }

        if ($model === null) {
            return '-';
        }

        try {
            return ActivityChangeFormatter::stringify($model->getAttribute($name));
        } catch (\Throwable) {
            return '-';
        }
    }

    /** Qəlib yoxdursa universal Azərbaycanca cümlə. */
    private static function fallback(string $group, string $event): string
    {
        $causer = ActivityLogHelper::causerName();

        $labels = [
            'created'    => 'yaratdı',
            'updated'    => 'yenilədi',
            'deleted'    => 'sildi',
            'restored'   => 'bərpa etdi',
            'assigned'   => 'öz üzərinə götürdü',
            'reassigned' => 'başqasının üzərindən götürdü',
            'released'   => 'buraxdı',
        ];

        if (isset($labels[$event])) {
            return "{$causer} {$group} qeydini {$labels[$event]}.";
        }

        return "{$causer}: {$group} - {$event}.";
    }
}
