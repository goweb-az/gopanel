<?php

namespace App\Translations;

use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator as BaseTranslator;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Facades\Cache;

class CustomTranslator extends BaseTranslator
{
    public function __construct(Loader $loader, $locale)
    {
        parent::__construct($loader, $locale);
    }

    /**
     * Get the translation for a given key.
     *
     * @param  string  $key
     * @param  array  $replace
     * @param  string  $locale
     * @return string
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        // Try fetching from database first
        $translations = Cache::get('translations_' . $locale);

        if ($translations && isset($translations[$key])) {
            return $translations[$key];
        }

        // If not found in DB, use the default translation (file-based)
        return parent::get($key, $replace, $locale, $fallback);
    }
}
