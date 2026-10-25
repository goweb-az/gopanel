<?php

namespace App\Services\Gopanel\Translations;

use App\Support\Cache\CacheKey;
use Illuminate\Support\Facades\Cache;

/**
 * Tərcümə keşinin invalidasiyası - iki keş bir yerdən təmizlənir:
 *
 *  1. `CacheKey::translation($platform, $page)` lüğətləri (API, JS) - qrup
 *     versiyası artırılır, bütün platforma/səhifə/dil açarları bir anda yararsız olur;
 *  2. Blade saytının runtime lüğəti `site_translations_{locale}`
 *     (TranslationServiceProvider doldurur, özü heç vaxt təmizləmir).
 */
class TranslationCacheService
{
    /**
     * Hər iki keşi təmizləyir. Yazma əməliyyatından sonra çağırılır.
     *
     * @param  iterable<int, string>  $locales
     */
    public function flush(iterable $locales = []): void
    {
        CacheKey::flushGroup(CacheKey::GROUP_TRANSLATION);

        $this->forgetLocales($locales);
    }

    /**
     * Forget the runtime translation cache for the given locales.
     *
     * Reuses the existing "site_translations_{locale}" cache key convention
     * from App\Providers\TranslationServiceProvider - this is the cache the
     * provider populates via Cache::remember(), and it will not self-invalidate
     * without an explicit forget() call after a DB write.
     */
    public function forgetLocales(iterable $locales): void
    {
        $unique = collect($locales)->filter()->unique()->values();

        foreach ($unique as $locale) {
            Cache::forget("site_translations_{$locale}");
        }
    }
}
