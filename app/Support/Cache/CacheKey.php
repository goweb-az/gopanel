<?php

declare(strict_types=1);

namespace App\Support\Cache;

use App\Services\Cache\CacheService;

/**
 * Bütün lookup/konfiqurasiya keşlərinin açarları burada qurulur.
 *
 * Məqsəd: açarlar TƏK yerdən idarə olunsun - həm oxuma (`remember`), həm də
 * təmizləmə (`flush`) eyni mənbədən istifadə etsin.
 *
 * INVALIDASIYA VERSİYA ƏSASLIDIR:
 *   - Hər qrupun (lookup, menu, translation, ...) bir versiya nömrəsi var.
 *   - Açar bu versiyanı daxil edir: "menu.v3.web.header.az"
 *   - Qrupu təmizləmək = versiyanı artırmaq. Köhnə açarlar avtomatik yararsız
 *     olur və TTL bitəndə özləri silinir.
 *
 * Bu üsul file/redis/memcached - BÜTÜN driver-lərdə işləyir (tag tələb etmir)
 * və dinamik açarları (filtr, səhifə, dil) bir anda təmizləyir.
 *
 * Dilə bağlı bütün açarlara `locale` əlavə olunur.
 *
 * KONFİQURASİYA: qruplar və model → qrup xəritəsi `config/custom/cache.php`
 * faylındadır. Yeni layihədə YALNIZ həmin fayl doldurulur - bu sinif toxunulmur.
 *
 * @see \App\Services\Cache\CacheService  saxlama qatı
 * @see config/custom/cache.php           qrup/model tərifi
 */
final class CacheKey
{
    public const GROUP_LOOKUP      = 'lookup';
    public const GROUP_MENU        = 'menu';
    public const GROUP_CATEGORY    = 'category';
    public const GROUP_TRANSLATION = 'translation';
    public const GROUP_SETTINGS    = 'settings';
    public const GROUP_HOME        = 'home';
    public const GROUP_PAGE        = 'page';
    public const GROUP_SEO         = 'seo';

    /**
     * Eyni request içində versiyaları təkrar-təkrar oxumamaq üçün memo.
     *
     * @var array<string, int>
     */
    private static array $versionMemo = [];

    /**
     * model_type (sinif adı) → qrup xəritəsi (config-dən).
     *
     * @var array<class-string, string>|null
     */
    private static ?array $modelGroupMap = null;

    /* ------------------------------------------------------------------ */
    /* Qrup idarəetməsi                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * Event bağlanmalı olan bütün "sahib" modelləri qaytarır.
     * Observer/EventServiceProvider bu siyahını gəzir.
     *
     * @return array<int, class-string>
     */
    public static function trackedModels(): array
    {
        return array_keys(self::modelGroupMap());
    }

    /**
     * Verilən model sinfinə uyğun qrupu qaytarır (yoxdursa null).
     */
    public static function groupForModelType(?string $modelType): ?string
    {
        if (!$modelType) {
            return null;
        }

        return self::modelGroupMap()[ltrim($modelType, '\\')] ?? null;
    }

    /**
     * Qrupu təmizləyir (versiyasını artırır).
     */
    public static function flushGroup(string $group): void
    {
        $version = self::version($group) + 1;

        CacheService::forever(self::versionKey($group), $version);
        self::$versionMemo[$group] = $version;
    }

    /**
     * model_type-a uyğun qrupu təmizləyir. İzlənməyən modellər üçün heç nə etmir.
     */
    public static function flushForModelType(?string $modelType): void
    {
        $group = self::groupForModelType($modelType);

        if ($group !== null) {
            self::flushGroup($group);
        }
    }

    /**
     * Bütün qrupları təmizləyir (`Cache::flush()` deyil - yalnız versiyalar artır).
     */
    public static function flushAll(): void
    {
        $groups = array_unique(array_merge(
            array_values(self::modelGroupMap()),
            (array) config('custom.cache.groups', []),
        ));

        foreach ($groups as $group) {
            self::flushGroup((string) $group);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Açar qurucuları                                                     */
    /* ------------------------------------------------------------------ */

    /** Lookup cədvəlləri: `lookup.v{ver}.{name}.{locale}` */
    public static function lookup(string $name): string
    {
        return self::prefix(self::GROUP_LOOKUP) . ".{$name}." . self::locale();
    }

    /** Menyular: `menu.v{ver}.{platform}.{segment}.{locale}` */
    public static function menu(string $platform, string $segment): string
    {
        return self::prefix(self::GROUP_MENU) . ".{$platform}.{$segment}." . self::locale();
    }

    /** Kateqoriya ağacları: `category.v{ver}.{platform}.{filterHash}.{locale}` */
    public static function category(string $platform, array $filters = []): string
    {
        return self::prefix(self::GROUP_CATEGORY) . ".{$platform}." . self::hash($filters) . '.' . self::locale();
    }

    /** UI tərcümə lüğəti: `translation.v{ver}.{platform}.{page}.{locale}` */
    public static function translation(string $platform, ?string $page = null): string
    {
        return self::prefix(self::GROUP_TRANSLATION) . ".{$platform}." . ($page ?: 'all') . '.' . self::locale();
    }

    /** Sayt/panel tənzimləmələri: `settings.v{ver}.{name}` (dildən asılı deyil) */
    public static function settings(string $name): string
    {
        return self::prefix(self::GROUP_SETTINGS) . ".{$name}";
    }

    /** Ana səhifə blokları: `home.v{ver}.{name}.{locale}` */
    public static function home(string $name): string
    {
        return self::prefix(self::GROUP_HOME) . ".{$name}." . self::locale();
    }

    /** Statik səhifələr: `page.v{ver}.{name}[.{suffix}].{locale}` */
    public static function page(string $name, int|string|null $suffix = null): string
    {
        $key = self::prefix(self::GROUP_PAGE) . ".{$name}";

        if ($suffix !== null && $suffix !== '') {
            $key .= ".{$suffix}";
        }

        return $key . '.' . self::locale();
    }

    /** SEO meta/redirect: `seo.v{ver}.{name}[.{filterHash}].{locale}` */
    public static function seo(string $name, array $filters = []): string
    {
        $key = self::prefix(self::GROUP_SEO) . ".{$name}";

        if ($filters !== []) {
            $key .= '.' . self::hash($filters);
        }

        return $key . '.' . self::locale();
    }

    /**
     * Sərbəst açar - yuxarıdakı qəliblərdən heç biri uyğun gəlməyəndə.
     * Qrup adı config-dəki `groups` siyahısında olmalıdır ki, flush işləsin.
     */
    public static function custom(string $group, string $name, array $filters = [], bool $localized = true): string
    {
        $key = self::prefix($group) . ".{$name}";

        if ($filters !== []) {
            $key .= '.' . self::hash($filters);
        }

        return $localized ? $key . '.' . self::locale() : $key;
    }

    /* ------------------------------------------------------------------ */
    /* Daxili                                                              */
    /* ------------------------------------------------------------------ */

    /** Qrup üçün versiyalı prefiks: `{group}.v{version}` */
    private static function prefix(string $group): string
    {
        return "{$group}.v" . self::version($group);
    }

    /** Qrupun hazırkı versiyası (default 1). */
    private static function version(string $group): int
    {
        return self::$versionMemo[$group] ??= (int) CacheService::rememberForever(
            self::versionKey($group),
            static fn (): int => 1,
        );
    }

    /** Versiya saxlanan açar - dildən asılı deyil, bütün dilləri əhatə edir. */
    private static function versionKey(string $group): string
    {
        return "cachekey:ver:{$group}";
    }

    /** Filtr massivini sabit hash-ə çevirir (sıra fərqi açarı dəyişməsin). */
    private static function hash(array $filters): string
    {
        if ($filters === []) {
            return 'default';
        }

        ksort($filters);

        return md5((string) json_encode($filters));
    }

    /** Hazırkı aktiv dil kodu. */
    private static function locale(): string
    {
        return app()->getLocale();
    }

    /**
     * @return array<class-string, string>
     */
    private static function modelGroupMap(): array
    {
        if (self::$modelGroupMap !== null) {
            return self::$modelGroupMap;
        }

        $map = [];

        foreach ((array) config('custom.cache.model_groups', []) as $class => $group) {
            if (is_string($class) && is_string($group) && class_exists($class)) {
                $map[ltrim($class, '\\')] = $group;
            }
        }

        return self::$modelGroupMap = $map;
    }

    /** Test/konsol üçün: memo-nu sıfırlayır. */
    public static function resetMemo(): void
    {
        self::$versionMemo   = [];
        self::$modelGroupMap = null;
    }
}
