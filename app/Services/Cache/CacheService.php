<?php

namespace App\Services\Cache;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel Cache uzerinde tek noqtedan idare olunan wrapper.
 *
 * Butun keshin default omru config('cache.default_ttl') uzerinden,
 * driver/store ise config('cache.default') uzerinden gelir. Sabah
 * birseyi deyishmek lazim olsa yalniz config/bu serviste deyishirik.
 *
 * Metodlar static-dir, yeni hem static, hem de DI ile ishleyir:
 *   1. Static:  CacheService::remember(...)
 *   2. DI ile:  $this->cache->remember(...)   // static metod instance-dan da cagrila biler
 *
 * TTL gonderilmeyen butun yazma metodlarinda default TTL avtomatik tetbiq olunur.
 *
 * ACAR QURMAQ ucun `App\Support\Cache\CacheKey` istifade olunur - acar metni
 * bu sinife string kimi otururlur, burada qurulmur.
 *
 *   CacheService::remember(CacheKey::menu('web', 'header'), fn () => $query->get());
 *
 * @see \App\Support\Cache\CacheKey
 * @see config/cache.php  → default_ttl
 */
class CacheService
{
    /**
     * TTL gonderilmediyi hallarda istifade olunan default omur (saniye).
     */
    public static function defaultTtl(): int
    {
        return (int) config('cache.default_ttl', 3600);
    }

    /**
     * Altdaki Laravel cache repository-sini qaytarir.
     * $store null olduqda config-deki default store istifade olunur.
     */
    public static function store(?string $store = null): Repository
    {
        return Cache::store($store);
    }

    /**
     * Keshden deyer goturur, yoxdursa default qaytarir.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * Birden cox aciri bir defeye goturur.
     *
     * @param  array<int|string, mixed>  $keys
     * @return array<string, mixed>
     */
    public static function many(array $keys): array
    {
        return Cache::many($keys);
    }

    /**
     * Deyeri keshe yazir. TTL null olduqda default TTL istifade olunur.
     */
    public static function put(string $key, mixed $value, int|DateTimeInterface|DateInterval|null $ttl = null): bool
    {
        return Cache::put($key, $value, self::ttl($ttl));
    }

    /**
     * Birden cox deyeri eyni TTL ile yazir.
     *
     * @param  array<string, mixed>  $values
     */
    public static function putMany(array $values, int|DateTimeInterface|DateInterval|null $ttl = null): bool
    {
        return Cache::putMany($values, self::ttl($ttl));
    }

    /**
     * Yalniz acar movcud deyilse yazir.
     */
    public static function add(string $key, mixed $value, int|DateTimeInterface|DateInterval|null $ttl = null): bool
    {
        return Cache::add($key, $value, self::ttl($ttl));
    }

    /**
     * Atomik lock qaytarir. Queue/job-larda paralel ishlemeyi bloklamaq ucun.
     */
    public static function lock(string $key, int $seconds = 0, ?string $owner = null): Lock
    {
        return Cache::lock($key, $seconds, $owner);
    }

    /**
     * Deyeri sonsuz muddete (omurluk) yazir.
     */
    public static function forever(string $key, mixed $value): bool
    {
        return Cache::forever($key, $value);
    }

    /**
     * Acar varsa qaytarir, yoxdursa callback-i ishledib neticeni keshleyir.
     */
    public static function remember(string $key, Closure $callback, int|DateTimeInterface|DateInterval|null $ttl = null): mixed
    {
        return Cache::remember($key, self::ttl($ttl), $callback);
    }

    /**
     * remember-in omurluk versiyasi.
     */
    public static function rememberForever(string $key, Closure $callback): mixed
    {
        return Cache::rememberForever($key, $callback);
    }

    /**
     * Deyeri goturub eyni anda keshden silir.
     */
    public static function pull(string $key, mixed $default = null): mixed
    {
        return Cache::pull($key, $default);
    }

    /**
     * Acar keshde varmi?
     */
    public static function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Acar keshde yoxdurmu?
     */
    public static function missing(string $key): bool
    {
        return ! Cache::has($key);
    }

    /**
     * Reqemli deyeri artirir.
     */
    public static function increment(string $key, int $value = 1): int|bool
    {
        return Cache::increment($key, $value);
    }

    /**
     * Reqemli deyeri azaldir.
     */
    public static function decrement(string $key, int $value = 1): int|bool
    {
        return Cache::decrement($key, $value);
    }

    /**
     * Tek acari silir.
     */
    public static function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Birden cox acari silir.
     *
     * @param  array<int, string>  $keys
     */
    public static function forgetMany(array $keys): bool
    {
        $result = true;

        foreach ($keys as $key) {
            $result = Cache::forget($key) && $result;
        }

        return $result;
    }

    /**
     * Butun keshi temizleyir.
     */
    public static function flush(): bool
    {
        return Cache::flush();
    }

    /**
     * Default store-u temizleyir.
     */
    public static function clear(): bool
    {
        return Cache::clear();
    }

    /**
     * Teg destekleyen store-larda (redis/memcached) teglerle ishlemek ucun.
     *
     * @param  array<int, string>|string  $names
     */
    public static function tags(array|string $names): TaggedCache
    {
        return Cache::tags($names);
    }

    /**
     * TTL deyerini normallashdirir: null gelse default TTL-i qaytarir.
     */
    protected static function ttl(int|DateTimeInterface|DateInterval|null $ttl): int|DateTimeInterface|DateInterval
    {
        return $ttl ?? self::defaultTtl();
    }
}
