<?php

namespace App\Traits\Presentable\Navigation;

use App\Enums\Common\Menu\MenuPositionEnum;
use App\Enums\Common\Menu\MenuTypeEnum;
use Illuminate\Support\Facades\Route;

/**
 * `Menu` modelinin göstəriş accessor-ları: növ/mövqe adı və ictimai ünvan.
 *
 * Niyə: model dinamik route qeydiyyatının mənbəyidir (`getRoutes()`), orada
 * yalnız data/sorğu qalmalıdır. `menu_type`, `menu_position`, `route` adları
 * DƏYİŞMƏYİB - menyu ağacı, sitemap və sayt header-i işlədir.
 *
 * Starter-də ünvan Laravel-in öz host-undan qurulur (texnotech-dəki
 * `FrontendUrl` burada yoxdur - səhifələri Laravel özü verir).
 */
trait HasMenuPresentation
{
    /** Köhnə ad. Naməlum dəyərdə `from()` istisna atır - davranış saxlanılıb. */
    public function getMenuTypeAttribute()
    {
        return MenuTypeEnum::from($this->type)->label() ?? $this->type;
    }

    /** Köhnə ad. Naməlum dəyərdə `from()` istisna atır - davranış saxlanılıb. */
    public function getMenuPositionAttribute()
    {
        return MenuPositionEnum::from($this->position)->label() ?? $this->position;
    }

    /** Lüğətə uyğun ad; naməlum dəyərdə istisna atmır, xam dəyəri qaytarır. */
    public function getTypeLabelAttribute(): string
    {
        return MenuTypeEnum::tryFrom((string) $this->type)?->label() ?? (string) $this->type;
    }

    public function getTypeClassAttribute(): string
    {
        return MenuTypeEnum::tryFrom((string) $this->type)?->className() ?? 'secondary';
    }

    public function getPositionLabelAttribute(): string
    {
        return MenuPositionEnum::tryFrom((string) $this->position)?->label() ?? (string) $this->position;
    }

    public function getPositionClassAttribute(): string
    {
        return MenuPositionEnum::tryFrom((string) $this->position)?->className() ?? 'secondary';
    }

    /**
     * Menyu bəndinin ictimai ünvanı (cari dil üçün).
     *
     * Sıra: config-də qeydiyyatlı route → tərcümə slug-ı → `home` → kök.
     */
    public function getRouteAttribute()
    {
        $language = app()->currentLocale();

        // 1. Config-dən registered route tap
        $config = $this->config;
        if ($config) {
            $name = $config['name'] ?? null;
            if ($name) {
                $path = "site.{$language}.{$name}";
                if (Route::has($path)) {
                    return route($path);
                }
            }
        }

        // 2. Slug-dan URL genera et
        $slug = $this->getTranslation('slug', $language, true)
            ?? $this->route_slug  // getRoutes() join-ından gələn attribute
            ?? null;

        if ($slug) {
            return url("{$language}/{$slug}");
        }

        // 3. home route
        if ($this->route_name === 'home') {
            return url($language);
        }

        return url('/');
    }
}
