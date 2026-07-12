<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Translation\Translator;
use App\Models\Translations\Translation;
use App\Models\Geography\Language;

class TranslationServiceProvider extends ServiceProvider
{
    public function register()
    {
        // 
    }

    public function boot()
    {
        try {
            if (!Schema::hasTable('languages') || !Schema::hasTable('translations')) {
                return;
            }

            // Aynı istek içinde tekrar çağrılmasını önlemek için flag kullan
            if (app()->has('site.translations.loaded')) {
                return;
            }

            app()->instance('site.translations.loaded', true);

            $languages = Language::getCachedAll();

            foreach ($languages as $language) {
                $locale = $language->code;

                if (config('app.debug')) {
                    Cache::forget("site_translations_{$locale}");
                }

                $this->siteTranslations($locale, app('translator'));
            }
        } catch (\Exception $e) {
            Log::warning('TranslationServiceProvider: ' . $e->getMessage());
        }
    }




    public function siteTranslations($locale, $translator)
    {
        // Cache-lə və ya DB-dən götür
        $translations = Cache::remember("site_translations_{$locale}", now()->addDay(), function () use ($locale) {
            return Translation::where('locale', $locale)
                ->whereNotNull('key')
                ->whereNotNull('value')
                ->where('platform', 'website') // bu platformanı istəyə görə dəyiş
                ->get()
                ->mapWithKeys(function ($item) {
                    // Page-aware runtime key so identical group/key values on
                    // different pages don't collide:
                    //   group varsa: filename.page.group.key
                    //   group yoxdursa: filename.page.key
                    $page = $item->page ?: 'general';

                    $key = $item->group
                        ? "{$item->filename}.{$page}.{$item->group}.{$item->key}"
                        : "{$item->filename}.{$page}.{$item->key}";

                    return [$key => $item->value];
                })
                ->toArray();
        });

        // Əlavə et translator-a
        if (!empty($translations)) {
            $translator->addLines($translations, $locale);
            // Log::info("Translations added for locale: {$locale}"); // ['keys' => array_keys($translations)]
        } else {
            Log::warning("No translations found for locale: {$locale}");
        }
        return $translator;
    }
}
