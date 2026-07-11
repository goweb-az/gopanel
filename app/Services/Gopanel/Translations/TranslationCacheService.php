<?php

namespace App\Services\Gopanel\Translations;

use Illuminate\Support\Facades\Cache;

class TranslationCacheService
{
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
