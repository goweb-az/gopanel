<?php

namespace App\Services\Gopanel\Menus;

use App\Models\Geography\Language;
use Illuminate\Support\Facades\Cache;

class NavigationCacheService
{
    /**
     * Invalidate the navigation caches populated by App\Models\Navigation\Menu.
     *
     * Only targeted keys are forgotten (no global Cache::flush). The per-locale
     * view/routes caches drive the rendered site navigation; the slug/route-name
     * keyed caches are not enumerable here and are left to expire naturally.
     */
    public function forget(): void
    {
        Cache::forget('site_menu_view');

        foreach ($this->locales() as $locale) {
            Cache::forget("site_menu_view_{$locale}");
            Cache::forget("site_menu_routes_newx_{$locale}");
        }
    }

    private function locales(): array
    {
        try {
            return Language::getCachedAll()->pluck('code')->all();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
