<?php

namespace App\Traits\System;

use DateInterval;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Modelə cache helper-ləri əlavə edir.
 *
 * `getCachedAll()`     — bütün uyğun rekordları cache-dən qaytarır
 * `getCachedForever()` — sürtünt müəyyən vaxta deyil, manual flush olunana qədər cache
 * `getCachedFirst()`   — id-yə görə tek rekord
 * `getCachedBy()`      — istənilən sütuna görə tek rekord
 * `remember()`         — sərbəst cache wrapper, custom suffix + callback
 * `flushCache()`       — modelin standart cache açarlarını sil
 *
 * `getCachedAll`/`getCachedForever` filtrləri:
 *   - `is_active = true` (fillable-da varsa)
 *   - `status = true`    (fillable-da varsa)
 *   - `slug` translatedAttributes-da varsa, slug dolu olan rekordlar
 * Sıralama:
 *   - `order` fillable-da varsa → ASC, yoxsa `id` DESC
 *
 * Auto-invalidation: model `saved` və ya `deleted` olanda standart açarlar avtomatik silinir.
 */
trait Cacheable
{
    /**
     * Cache açarı prefiksi (override edə bilərsən).
     */
    public static function cacheKeyPrefix(): string
    {
        return 'cache.' . (new static())->getTable();
    }

    /**
     * Default cache TTL — 5 gün. Override etmək üçün modeldə eyni metodu yenidən təyin et.
     */
    public static function cacheTtl(): DateTimeInterface|DateInterval|int
    {
        return now()->addDays(5);
    }

    /**
     * Filterli + sıralanmış bütün rekordlar (cached).
     */
    public static function getCachedAll(DateTimeInterface|DateInterval|int|null $ttl = null): Collection
    {
        return Cache::remember(
            static::cacheKey('all'),
            $ttl ?? static::cacheTtl(),
            fn () => static::buildCacheableQuery()->get()
        );
    }

    /**
     * Forever cache (manual flush olunana qədər) — eyni filtrlərlə.
     */
    public static function getCachedForever(): Collection
    {
        return Cache::rememberForever(
            static::cacheKey('forever'),
            fn () => static::buildCacheableQuery()->get()
        );
    }

    /**
     * Cached single record by primary key.
     */
    public static function getCachedFirst($id, DateTimeInterface|DateInterval|int|null $ttl = null): ?Model
    {
        return Cache::remember(
            static::cacheKey('first.' . $id),
            $ttl ?? static::cacheTtl(),
            fn () => static::find($id)
        );
    }

    /**
     * Cached single record by any column.
     */
    public static function getCachedBy(string $column, $value, DateTimeInterface|DateInterval|int|null $ttl = null): ?Model
    {
        return Cache::remember(
            static::cacheKey('by.' . $column . '.' . $value),
            $ttl ?? static::cacheTtl(),
            fn () => static::where($column, $value)->first()
        );
    }

    /**
     * Sərbəst remember wrapper — custom suffix + callback.
     */
    public static function remember(string $suffix, callable $callback, DateTimeInterface|DateInterval|int|null $ttl = null)
    {
        return Cache::remember(
            static::cacheKey($suffix),
            $ttl ?? static::cacheTtl(),
            $callback
        );
    }

    /**
     * Standart cache açarlarını sil. Per-id və per-column açarları manual silinməlidir
     * (cache driver tag-ları dəstəkləmirsə) — adətən saved/deleted hook bəs edir.
     */
    public static function flushCache(): void
    {
        Cache::forget(static::cacheKey('all'));
        Cache::forget(static::cacheKey('forever'));
        Cache::forget("site_" . (new static())->getTable()); // backward compat
    }

    /**
     * Auto-flush on save/delete. Model `bootCacheable()`-i avtomatik çağırır.
     */
    protected static function bootCacheable(): void
    {
        static::saved(fn ($model) => static::flushCache());
        static::deleted(fn ($model) => static::flushCache());
    }

    /**
     * Cache açarını yığ.
     */
    protected static function cacheKey(string $suffix): string
    {
        return static::cacheKeyPrefix() . '.' . $suffix;
    }

    /**
     * Müştərək filtrli sorğu — `getCachedAll` və `getCachedForever` istifadə edir.
     */
    protected static function buildCacheableQuery(): Builder
    {
        $instance = new static();
        $query = $instance->newQuery();

        if (in_array('is_active', $instance->getFillable())) {
            $query->where('is_active', true);
        }

        if (in_array('status', $instance->getFillable())) {
            $query->where('status', true);
        }

        if (in_array('slug', $instance->translatedAttributes ?? [])) {
            $query->whereHas('translations', function ($subQuery) {
                $subQuery->where('key', 'slug')->whereNotNull('value');
            });
        }

        if (in_array('order', $instance->getFillable())) {
            $query->orderBy('order', 'ASC');
        } else {
            $query->orderBy('id', 'DESC');
        }

        return $query;
    }
}
