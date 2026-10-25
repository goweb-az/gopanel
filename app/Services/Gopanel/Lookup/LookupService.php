<?php

declare(strict_types=1);

namespace App\Services\Gopanel\Lookup;

use App\DTOs\Gopanel\ContentPayload;
use App\Repositories\BaseRepository;
use App\Services\Gopanel\Content\ContentSaveService;
use App\Services\Gopanel\SortOrderService;
use App\Support\Cache\CacheKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Status/tip cədvəllərinin (lookup) ortaq invariantları və yazma axını.
 *
 * NİYƏ burada (texnotech `LookupService`-dən uyğunlaşdırılıb):
 * Bu qaydalar bütün lookup-larda eynidir və UI-a aid deyil. aquastores-da
 * controller-də idi və nəticədə: bir neçə sətir eyni anda «default» qalırdı,
 * default status deaktiv edilə bilirdi, istifadə olunan status silinirdi,
 * sıralama N sorğu + N keş təmizlənməsi ilə tranzaksiyasız gedirdi.
 *
 * Qaydalar:
 *   - `is_default` yalnız BİR sətirdə ola bilər; dəyişiklik tranzaksiyadadır;
 *   - default sətir deaktiv edilə, silinə bilməz və «default»-dan əl ilə
 *     çıxarıla bilməz - yalnız başqası default seçilərək əvəzlənir;
 *   - istifadə olunan sətir silinmir (say çağırandan gəlir - hook);
 *   - hər yazmadan sonra `lookup` keş qrupu BİR dəfə təmizlənir.
 *
 * Biznes qaydası xətası 422 (ValidationException) kimi qayıdır - JS onu
 * mövcud `showError()` ilə göstərir.
 */
final class LookupService
{
    public function __construct(
        private readonly ContentSaveService $content,
        private readonly BaseRepository $repository,
        private readonly SortOrderService $sorter,
    ) {
    }

    /**
     * Formadan gələn məlumatı yazır (sütunlar + tərcümələr).
     */
    public function save(Model $item, ContentPayload $payload, LookupDefinition $definition): Model
    {
        $payload = $this->normalizeBooleans($payload, $definition);
        $makeDefault = $definition->hasBoolean('is_default') && $payload->attribute('is_default') === true;

        if ($definition->hasBoolean('is_default')) {
            $this->guardDefaultRemoval($item, $makeDefault);
        }

        $saved = DB::transaction(function () use ($item, $payload, $makeDefault) {
            $saved = $this->content->save($item, $payload);

            if ($makeDefault) {
                $this->resetOtherDefaults($saved);
            }

            return $saved;
        });

        $this->flush();

        return $saved;
    }

    /**
     * Siyahıdakı keçid düyməsi ilə tək bayrağın dəyişdirilməsi.
     *
     * Eyni qaydalar formadakı kimi tətbiq olunur - əks halda admin formada
     * qadağan olunanı (default-u söndürmək) siyahıdan edə bilərdi.
     */
    public function toggle(Model $item, string $field, bool $value, LookupDefinition $definition): Model
    {
        if (!$definition->hasBoolean($field)) {
            throw ValidationException::withMessages(['row' => 'Bu sahə dəyişdirilə bilməz.']);
        }

        if ($field === 'is_active' && !$value && $item->getAttribute('is_default')) {
            throw ValidationException::withMessages([
                'is_active' => 'Default sətir deaktiv edilə bilməz - əvvəlcə başqasını default seçin.',
            ]);
        }

        if ($field === 'is_default') {
            $this->guardDefaultRemoval($item, $value);
        }

        DB::transaction(function () use ($item, $field, $value, $definition) {
            $attributes = [$field => $value];

            // Default həmişə aktivdir: söndürülmüş status yeni obyektə verilə bilməz.
            if ($field === 'is_default' && $value && $definition->hasBoolean('is_active')) {
                $attributes['is_active'] = true;
            }

            $item->forceFill($attributes)->save();

            if ($field === 'is_default' && $value) {
                $this->resetOtherDefaults($item);
            }
        });

        $this->flush();

        return $item->fresh() ?? $item;
    }

    /**
     * Silməyə mane olan səbəb (yoxdursa null).
     *
     * @param  int  $usageCount  sətrin başqa cədvəllərdə istifadə sayı
     *                           (controller-in `inUseCount()` hook-u verir)
     */
    public function deleteBlockReason(Model $item, int $usageCount): ?string
    {
        // Sistemin işləməsi üçün default sətir həmişə qalmalıdır.
        if ($item->getAttribute('is_default')) {
            return 'Default olaraq işarələnmiş sətir silinə bilməz - əvvəlcə başqasını default seçin.';
        }

        if ($usageCount > 0) {
            return "Bu sətir {$usageCount} yerdə istifadə olunur - silmək əvəzinə deaktiv edin.";
        }

        return null;
    }

    /**
     * `cədvəl => xarici açar` xəritəsi üzrə istifadə sayı.
     *
     * Soft-delete olunmuş istinadlar da sayılır: arxivdən bərpa olunan obyekt
     * silinmiş statusa istinad etməsin.
     *
     * @param  array<string, string>  $usedBy
     */
    public function usageCount(Model $item, array $usedBy): int
    {
        $total = 0;

        foreach ($usedBy as $table => $column) {
            $total += DB::table($table)->where($column, $item->getKey())->count();
        }

        return $total;
    }

    /**
     * @throws ValidationException silməyə mane olan səbəb varsa
     */
    public function delete(Model $item, int $usageCount): void
    {
        $reason = $this->deleteBlockReason($item, $usageCount);

        if ($reason !== null) {
            throw ValidationException::withMessages(['id' => $reason]);
        }

        $this->repository->delete($item);
        $this->flush();
    }

    /**
     * Sürüklə-burax nəticəsi - bir sorğu, bir tranzaksiya, bir keş təmizlənməsi.
     *
     * Tanınmayan və ya silinmiş açar varsa HEÇ NƏ yazılmır: köhnə tabda açıq
     * qalmış siyahı yarımçıq sıra göndərməsin.
     *
     * @param  class-string<Model>  $modelClass
     * @param  list<string>  $keys
     */
    public function sort(string $modelClass, array $keys): int
    {
        $model = new $modelClass();
        $column = $this->sorter->keyColumn($model);

        $existing = $modelClass::query()->whereIn($column, $keys)->toBase()->count();

        if ($existing !== count($keys)) {
            throw ValidationException::withMessages([
                'orders' => 'Siyahı köhnəlib - səhifəni yeniləyib yenidən cəhd edin.',
            ]);
        }

        $updated = $this->sorter->apply($modelClass, $keys);
        $this->flush();

        return $updated;
    }

    /**
     * Checkbox göndərilməyibsə bayraq `false`-dur. Default sətir həmişə aktivdir.
     */
    private function normalizeBooleans(ContentPayload $payload, LookupDefinition $definition): ContentPayload
    {
        foreach ($definition->booleans as $field) {
            $value = filter_var($payload->attribute($field, false), FILTER_VALIDATE_BOOLEAN);
            $payload = $payload->withAttribute($field, $value);
        }

        if ($payload->attribute('is_default') === true && $definition->hasBoolean('is_active')) {
            $payload = $payload->withAttribute('is_active', true);
        }

        return $payload;
    }

    /**
     * Default sətir «default»-dan yalnız başqası seçilərək çıxır.
     */
    private function guardDefaultRemoval(Model $item, bool $willBeDefault): void
    {
        if ($willBeDefault || !$item->exists || !$item->getAttribute('is_default')) {
            return;
        }

        throw ValidationException::withMessages([
            'is_default' => 'Ən azı bir default sətir qalmalıdır - başqasını default seçin, bu avtomatik çıxacaq.',
        ]);
    }

    /**
     * Qalan sətirlərin `is_default` bayrağını sıfırlayır.
     *
     * Query builder ilə (event-siz): bu, ayrıca admin əməliyyatı deyil, əsas
     * yazmanın nəticəsidir; keş sonda `flush()` ilə təmizlənir.
     */
    private function resetOtherDefaults(Model $item): void
    {
        $item->newQuery()
            ->whereKeyNot($item->getKey())
            ->where('is_default', true)
            ->toBase()
            ->update(['is_default' => false]);
    }

    /**
     * Lookup oxuyan bütün keşlər (`CacheKey::lookup(...)`) bir versiya artımı
     * ilə köhnəlir - `Cache::flush()` çağırılmır (03-site.md § 5).
     */
    private function flush(): void
    {
        try {
            CacheKey::flushGroup(CacheKey::GROUP_LOOKUP);
        } catch (\Throwable $e) {
            // Keş xətası yazılmış məlumatı geri qaytarmamalıdır.
            report($e);
        }
    }
}
