<?php

namespace App\Facades;

use App\Services\Locale\LocaleManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string current()
 * @method static \Illuminate\Support\Collection all()
 * @method static \App\Models\Geography\Language|null default()
 * @method static string defaultCode(string $fallback = 'az')
 * @method static void clearCache()
 *
 * @see \App\Services\Locale\LocaleManager
 */
class Locale extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LocaleManager::class;
    }
}
