<?php

declare(strict_types=1);

namespace App\Support\Translation;

use Illuminate\Database\Eloquent\Model;

/**
 * Tərcüməni **yüklənmiş** `translations` əlaqəsindən oxuyur.
 *
 * `Translation::getTranslation()` hər çağırışda ayrıca `SELECT` atır. Bir
 * məhsulun 100 variantı olanda variant × sahə × dil sayı qədər sorğu yaranır.
 * Bu sinif yeni tərcümə mexanizmi deyil - eyni `field_translations` sətirlərini
 * artıq yaddaşdakı kolleksiyadan oxuyur, əlaqə yüklənməyibsə modelin öz
 * metoduna qayıdır.
 */
final class LoadedTranslation
{
    public static function get(?Model $model, string $key, ?string $locale = null): ?string
    {
        if ($model === null) {
            return null;
        }

        $locale ??= app()->getLocale();

        if (! $model->relationLoaded('translations')) {
            return method_exists($model, 'getTranslation')
                ? $model->getTranslation($key, $locale, true)
                : null;
        }

        $row = $model->getRelation('translations')
            ->first(static fn($translation): bool => $translation->key === $key
                && $translation->locale === $locale);

        return $row?->value;
    }

    /**
     * Modelin bütün dillərdəki dəyərləri - `['az' => '...', 'en' => '...']`.
     *
     * @return array<string, string|null>
     */
    public static function all(?Model $model, string $key): array
    {
        if ($model === null || ! $model->relationLoaded('translations')) {
            return [];
        }

        return $model->getRelation('translations')
            ->filter(static fn($translation): bool => $translation->key === $key)
            ->mapWithKeys(static fn($translation): array => [$translation->locale => $translation->value])
            ->all();
    }
}
