<?php

declare(strict_types=1);

namespace App\Support\Activity;

use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * `activity_log.subject_type` sütununun İKİ formasını əhatə edir.
 *
 * ⚠️ Real halda (törəmə layihənin produksiya bazası) eyni model üçün sütunda
 * iki dəyər yığılmışdı: `App\Models\Site\Blog` və `blog` kimi.
 *
 * Səbəb: `Relation::morphMap()` layihənin ortasında əlavə olunanda ondan
 * sonrakı sətirlər qısa alias-la, əvvəlkilər tam sinif adı ilə yazılır. Köhnə
 * sətirlər saxlanılır, ona görə sorğu HƏR İKİ formanı tutmalıdır; yoxsa
 * tarixçənin yarısı «itir».
 *
 * Spatie-nin `Activity::forSubject()` metodu yalnız `getMorphClass()` dəyərinə
 * baxır - məhz buna görə burada işlədilmir.
 */
final class ActivitySubject
{
    /** @var array<string, array<int, string>> subject_type => səs-küy sahələri */
    private static array $noiseCache = [];

    /**
     * Verilmiş modelin bazada ola biləcək bütün `subject_type` dəyərləri.
     *
     * @param  class-string  $class
     * @return array<int, string>
     */
    public static function types(string $class): array
    {
        $types = [$class];

        foreach (Relation::morphMap() as $alias => $mapped) {
            if ($mapped === $class) {
                $types[] = (string) $alias;
            }
        }

        return array_values(array_unique($types));
    }

    /** `subject_type` dəyərindən model sinfi (alias da, tam ad da qəbul edilir). */
    public static function modelClass(string $subjectType): ?string
    {
        $class = Relation::getMorphedModel($subjectType) ?? $subjectType;

        return class_exists($class) ? $class : null;
    }

    /**
     * Tarixçədə GİZLƏDİLƏN sahələr - modelin öz `$logExcept` siyahısı.
     *
     * ⚠️ Niyə oxu tərəfində də lazımdır: `$logExcept` sonradan əlavə olunub,
     * ondan ƏVVƏLKİ sətirlər həmin sahələri özündə saxlayır. Məsələn `at_work`
     * hər giriş/çıxış skanında dəyişirdi - bazada yüz minlərlə belə sətir var
     * və süzülməsə tarixçə tamamilə onlardan ibarət olur.
     *
     * @return array<int, string>
     */
    public static function noiseAttributes(string $subjectType): array
    {
        if (array_key_exists($subjectType, self::$noiseCache)) {
            return self::$noiseCache[$subjectType];
        }

        $class = self::modelClass($subjectType);
        $noise = [];

        if ($class !== null) {
            try {
                $model = new $class();

                if (method_exists($model, 'activityNoiseAttributes')) {
                    $noise = $model->activityNoiseAttributes();
                }
            } catch (\Throwable) {
                // Model qurula bilmirsə tarixçə dayanmamalıdır - süzgəc boş qalır
                $noise = [];
            }
        }

        return self::$noiseCache[$subjectType] = $noise;
    }
}
