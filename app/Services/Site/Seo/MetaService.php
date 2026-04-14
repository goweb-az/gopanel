<?php

namespace App\Services\Site\Seo;

use App\Models\Navigation\Menu;
use App\Models\Settings\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class MetaService
{
    /**
     * Menülerden aktif sayfanın meta verilerini derler.
     * Prioritet: Menu meta → SiteSetting meta → defaults
     */
    public function compose($menus, Request $request): array
    {
        $site_settings  = SiteSetting::getCached();
        $defaultMeta    = $site_settings?->meta()?->first();

        // URL segmentindən menu slug-ını tap
        $segment = $request->segment(2) ?? $request->segment(3);

        $meta = null;

        if (!is_null($segment)) {
            // Slug ilə menu tap, onun meta-sını al
            $menu = Menu::getBySlug($segment);
            if ($menu) {
                $meta = $menu->meta()?->first();
            }
        }

        // Meta tapılmadısa → SiteSetting default meta
        if (!$meta) {
            $meta = $defaultMeta;
        }

        return [
            'site_title'  => $meta->title        ?? $defaultMeta->title        ?? config('seo.defaults.site_title', 'Proweb Creative Agency'),
            'title'       => $meta->title        ?? $defaultMeta->title        ?? config('seo.defaults.title', 'Proweb.az'),
            'description' => $meta->description  ?? $defaultMeta->description  ?? config('seo.defaults.description', 'Proweb - Veb saytların yaradılması'),
            'keywords'    => $meta->keywords     ?? $defaultMeta->keywords     ?? config('seo.defaults.keywords', 'veb-sayt,mobil-tətbiq,CRM,ERP'),
            'image'       => $this->resolveImage($meta, $menu ?? null, $defaultMeta, $site_settings),
        ];
    }


    /**
     * Meta şəkili həll edir
     */
    private function resolveImage($meta, $menu, $defaultMeta, $siteSettings): ?string
    {
        // 1. Menu meta image
        if (isset($menu) && $menu->getMetaImage()) {
            return $menu->getMetaImage();
        }

        // 2. Default meta image
        if ($defaultMeta && !empty($defaultMeta->image)) {
            return $defaultMeta->image_url ?? null;
        }

        // 3. Site logo fallback
        return $siteSettings->logo_light_url ?? null;
    }


    /**
     * Derlenen meta'yı Blade için paylaşır.
     */
    public static function share(array $meta): void
    {
        view()->share('site_title',       Arr::get($meta, 'site_title'));
        view()->share('meta_title',       Arr::get($meta, 'title'));
        view()->share('meta_description', Arr::get($meta, 'description'));
        view()->share('meta_keywords',    Arr::get($meta, 'keywords'));
        view()->share('meta_image',       Arr::get($meta, 'image'));
    }


    /**
     * Controller-dən single item meta paylaşmaq üçün
     * Blog single, service single və s.
     */
    public static function sharePageMeta(array $meta): void
    {
        view()->share('site_title',       Arr::get($meta, 'title'));
        view()->share('meta_title',       Arr::get($meta, 'title'));
        view()->share('meta_description', Arr::get($meta, 'description'));
        view()->share('meta_keywords',    Arr::get($meta, 'keywords'));
        view()->share('meta_image',       Arr::get($meta, 'image'));
    }


    /**
     * Kısa yol: menülerden meta üret + paylaş.
     */
    public function composeAndShare($menus, Request $request): array
    {
        $meta = $this->compose($menus, $request);
        $this->share($meta);
        return $meta;
    }
}
