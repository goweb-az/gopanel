<?php

namespace App\Services\Site\Seo;

use App\Models\Navigation\Menu;
use App\Models\Settings\SiteSetting;
use App\Support\Url\CdnUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

/**
 * Səhifənin `<title>` və `<meta>` dəyərlərini toplayır.
 *
 * Mənbə sırası (birinci dolu olan qazanır):
 *
 *   1. Səhifənin ÖZ qeydi - bloq yazısı və s. Controller
 *      `SiteController::setPageMeta($item)` çağırır → `composeForModel()`.
 *      Meta sətri yoxdursa qeydin başlıq/təsvirindən qurulur - əks halda bütün
 *      yazılar siyahı menyusunun meta-sını göstərir və eyni başlıqla indekslənir.
 *   2. Səhifəyə uyğun MENYU sətri (`composeForRequest()`): URL-in dil
 *      prefiksindən sonrakı seqmenti menyunun cari dildəki slug-ı ilə tutuşdurulur.
 *   3. `SiteSetting`-in öz meta sətri - saytın ümumi təsviri.
 *   4. `config/seo/meta.php` - baza tamamilə boş olanda.
 *
 * Köhnə `compose()` / `share()` / `sharePageMeta()` / `composeAndShare()`
 * imzaları törəmə layihələr üçün olduğu kimi saxlanılıb. Əvvəl son ehtiyat
 * mövcud olmayan `config('seo.defaults')`-dan oxunurdu və «Proweb» mətnləri
 * hardcode idi - indi `config/seo/meta.php`-dən gəlir.
 */
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
        $defaults       = $this->configDefaults();

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
            'site_title'  => $meta->title        ?? $defaultMeta->title        ?? $defaults['title'],
            'title'       => $meta->title        ?? $defaultMeta->title        ?? $defaults['title'],
            'description' => $meta->description  ?? $defaultMeta->description  ?? $defaults['description'],
            'keywords'    => $meta->keywords     ?? $defaultMeta->keywords     ?? $defaults['keywords'],
            'image'       => $this->resolveImage($meta, $menu ?? null, $defaultMeta, $site_settings),
        ];
    }

    /**
     * Cari sorğuya uyğun meta (menyu → SiteSetting → config).
     *
     * @return array{site_title: string, title: string, description: string, keywords: string, image: ?string}
     */
    public function composeForRequest(Request $request): array
    {
        $menu = $this->resolveMenu($request);
        $meta = $menu?->meta()->first();

        return $this->build([
            'title'       => $meta?->title,
            'description' => $meta?->description,
            'keywords'    => $meta?->keywords,
            'image'       => $menu?->getMetaImage(),
        ]);
    }

    /**
     * Qeydin öz meta-sı; sətir yoxdursa qeydin başlıq/təsvirindən qurulur.
     *
     * @return array{site_title: string, title: string, description: string, keywords: string, image: ?string}
     */
    public function composeForModel(Model $item): array
    {
        $meta = method_exists($item, 'meta') ? $item->meta()->first() : null;

        return $this->build([
            'title'       => $meta?->title ?: $this->plain($item->title ?? $item->name ?? null),
            'description' => $meta?->description ?: $this->summarize($item->short_description ?? $item->description ?? null),
            'keywords'    => $meta?->keywords,
            'image'       => $this->modelImage($item, $meta),
        ]);
    }

    /**
     * Controller-in əl ilə verdiyi dəyərlər (menyusu olmayan səhifələr üçün).
     *
     * @param array<string, mixed> $values
     * @return array{site_title: string, title: string, description: string, keywords: string, image: ?string}
     */
    public function composeFromArray(array $values): array
    {
        return $this->build([
            'title'       => $values['title'] ?? null,
            'description' => isset($values['description']) ? $this->summarize((string) $values['description']) : null,
            'keywords'    => $values['keywords'] ?? null,
            'image'       => $values['image'] ?? null,
        ]);
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
            return $defaultMeta->image_url ?? CdnUrl::url($defaultMeta->image);
        }

        // 3. Site logo fallback
        return $siteSettings->logo_light_url ?? $this->defaultImage();
    }


    /**
     * Derlenen meta'yı Blade için paylaşır.
     */
    public static function share(array $meta): void
    {
        view()->share('site_title',       Arr::get($meta, 'site_title', Arr::get($meta, 'title')));
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

    /* ── Daxili ─────────────────────────────────────────────────────────── */

    /**
     * Boş qalan sahələri sayt tənzimləməsi, sonra config ilə doldurur.
     *
     * @param array<string, mixed> $values
     * @return array{site_title: string, title: string, description: string, keywords: string, image: ?string}
     */
    private function build(array $values): array
    {
        try {
            $settings     = SiteSetting::getCached();
            $settingsMeta = $settings?->meta()->first();
        } catch (Throwable) {
            $settings = $settingsMeta = null;
        }

        $defaults = $this->configDefaults();
        $title    = $this->firstFilled($values['title'] ?? null, $settingsMeta?->title, $defaults['title']);

        return [
            'site_title'  => $title,
            'title'       => $title,
            'description' => $this->firstFilled($values['description'] ?? null, $settingsMeta?->description, $defaults['description']),
            'keywords'    => $this->firstFilled($values['keywords'] ?? null, $settingsMeta?->keywords, $defaults['keywords']),
            // Son ehtiyat config-dəki paylaşım şəklidir - əks halda `og:image`
            // boş çap olunur və link WhatsApp/Facebook-da şəkilsiz görünür
            'image'       => ($values['image'] ?? null)
                ?: (!empty($settingsMeta?->image) ? CdnUrl::url($settingsMeta->image) : null)
                ?: ($settings?->logo_light_url ?: null)
                ?: $this->defaultImage(),
        ];
    }

    /**
     * URL-in dil prefiksindən sonrakı seqmentinə uyğun menyu; ana səhifədə
     * `home` menyusu.
     */
    private function resolveMenu(Request $request): ?Menu
    {
        try {
            $segment = $request->segment(2);

            if (empty($segment)) {
                return Menu::getByRouteName('home');
            }

            return Menu::getBySlug($segment);
        } catch (Throwable) {
            return null;
        }
    }

    /** Şəkil sırası: meta sətrinin şəkli → qeydin öz şəkli. */
    private function modelImage(Model $item, ?object $meta): ?string
    {
        if (!empty($meta?->image)) {
            return CdnUrl::url($meta->image);
        }

        foreach (['image_url', 'logo_url', 'icon_url'] as $attribute) {
            $value = $item->{$attribute} ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function defaultImage(): ?string
    {
        $path = config('seo.meta.default_image');

        return $path ? CdnUrl::url((string) $path) : null;
    }

    /** Redaktor məzmununu meta təsvirinə çevirir: teqlər atılır, uzunluq kəsilir. */
    private function summarize(?string $value): ?string
    {
        $plain = $this->plain($value);

        return $plain === null ? null : Str::limit($plain, (int) config('seo.meta.description_limit', 160), '…');
    }

    private function plain(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? '');

        return $plain !== '' ? $plain : null;
    }

    /**
     * @return array{title: string, description: string, keywords: string}
     */
    private function configDefaults(): array
    {
        $defaults = (array) config('seo.meta.defaults', []);
        $row      = $defaults[app()->getLocale()]
            ?? $defaults[config('app.fallback_locale', 'az')]
            ?? [];

        return [
            'title'       => (string) (($row['title'] ?? '') ?: config('app.name', 'Gopanel')),
            'description' => (string) ($row['description'] ?? ''),
            'keywords'    => (string) ($row['keywords'] ?? ''),
        ];
    }

    private function firstFilled(?string ...$values): string
    {
        foreach ($values as $value) {
            if ($value !== null && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }
}
