<?php

declare(strict_types=1);

namespace App\Support\Activity;

/**
 * `TranslationHelper::fromInputWithChanges()`-in qaytardığı dəyişiklik
 * massivini log mesajı üçün oxunaqlı sətrə çevirir.
 *
 * Niyə ayrıca sinif: tərcümə sahələri `field_translations` cədvəlindədir və
 * activity log-un `logOnlyDirty()` mexanizmi onları görmür - «adını dəyişdirdi»
 * cümləsi yalnız bu massivdən qurula bilər. Panelin ümumi CRUD axını da, öz
 * `save()` metodu olan modullar da eyni formatı işlətməlidir.
 *
 * Sahə adları `ActivityChangeFormatter::labels()`-dən gəlir - adi sütun
 * dəyişiklikləri ilə tərcümə dəyişiklikləri eyni etiketlə görünsün.
 *
 *   TranslationChanges::describe($changes, 'Blog');
 *   // «Başlıq (az): Köhnə → Yeni, Təsvir (en): - → ...»
 */
final class TranslationChanges
{
    /** Mesajda göstərilən dəyərin maksimum uzunluğu - uzun təsvir logu doldurmasın. */
    private const MAX_VALUE_LENGTH = 80;

    /**
     * @param  array<string, array<string, array{old: ?string, new: ?string}>>  $changes
     * @param  string|null  $modelName  namespace-siz model adı (`Blog`) - modelə xas etiketlər üçün
     */
    public static function describe(array $changes, ?string $modelName = null): string
    {
        $labels = $modelName !== null && $modelName !== ''
            ? ActivityChangeFormatter::labels($modelName)
            : (array) config('custom.activity_messages.attribute_labels', []);

        $parts = [];

        foreach ($changes as $field => $locales) {
            foreach ((array) $locales as $locale => $values) {
                if (!is_array($values)) {
                    continue;
                }

                $parts[] = ($labels[$field] ?? $field) . " ({$locale}): "
                    . self::shorten($values['old'] ?? null)
                    . ' → '
                    . self::shorten($values['new'] ?? null);
            }
        }

        return $parts === [] ? '-' : implode(', ', $parts);
    }

    private static function shorten(?string $value): string
    {
        // Redaktor sahələri HTML saxlayır - log cümləsində teq görünməsin.
        $value = trim(strip_tags((string) $value));

        if ($value === '') {
            return '-';
        }

        return mb_strlen($value) > self::MAX_VALUE_LENGTH
            ? mb_substr($value, 0, self::MAX_VALUE_LENGTH) . '…'
            : $value;
    }
}
