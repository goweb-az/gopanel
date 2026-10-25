<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Models\Geography\Language;
use App\Models\Navigation\Menu;
use App\Models\Translations\FieldTranslation;
use App\Support\Cache\CacheKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Saytın publik səhifələrinin TƏK mənbəyi: menyu səhifələri + detal səhifəsi
 * olan məzmun modelləri (`config/seo/content.php`).
 *
 * NİYƏ ayrıca sinif: sitemap, RSS, Markdown variantı və `/llms-full.txt`
 * eyni suala cavab verir - «hansı səhifələr publikdir və ünvanı nədir?».
 * Hər biri bunu özü hesablasaydı, biri `is_active` yoxlamasını, digəri slug
 * yoxlamasını unudardı və sitemap-da olan səhifə Markdown-da 404 verərdi.
 * Qaydalar burada bir dəfə yazılır:
 *
 *   - qeyd yalnız `is_active`/`status` doğrudursa və silinməyibsə publikdir;
 *   - ünvan `/{dil}/{slug}` qəlibidir (`DynamicContentController` ilə eyni);
 *   - həmin dildə slug tərcüməsi yoxdursa səhifə də yoxdur;
 *   - menyu səhifəsi yalnız route-u qeydiyyatdan keçibsə (`config/site/menu_routes.php`)
 *     mövcuddur - əks halda HTML tərəf də onu açmır.
 *
 * Oxu təzə sorğu ilə gedir (model keşi yox): `Blog::getCachedAll()` kimi
 * keşlər panel dəyişikliyindən sonra günlərlə köhnə qala bilir, maşın fayllarında
 * isə deaktiv edilmiş yazı görünməməlidir. Ağır sənədləri çağıran tərəf özü
 * `fingerprint()` ilə keşləyir.
 */
class PublicContentService
{
    /**
     * Konfiqurasiyadakı mövcud modellər.
     *
     * @return array<class-string<Model>, array<string, mixed>>
     */
    public function models(): array
    {
        $models = [];

        foreach ((array) config('seo.content.models', []) as $class => $options) {
            if (is_string($class) && class_exists($class)) {
                $models[$class] = is_array($options) ? $options : [];
            }
        }

        return $models;
    }

    /**
     * Modelin publik qeydləri (ən yenisi başda).
     *
     * @param  class-string<Model>  $class
     * @return Collection<int, Model>
     */
    public function items(string $class, ?int $limit = null): Collection
    {
        try {
            /** @var Model $instance */
            $instance = new $class();

            if (!Schema::hasTable($instance->getTable())) {
                return collect();
            }

            $query = $class::query();

            if (in_array('is_active', $instance->getFillable(), true)) {
                $query->where('is_active', true);
            }

            if (in_array('status', $instance->getFillable(), true)) {
                $query->where('status', true);
            }

            $limit ??= (int) ($this->models()[$class]['limit'] ?? 1000);

            return $query->latest($instance->getCreatedAtColumn() ?? 'id')
                ->limit(max(1, $limit))
                ->get()
                ->filter(fn (Model $item): bool => $this->isPublic($item))
                ->values();
        } catch (Throwable $e) {
            report($e);

            return collect();
        }
    }

    /**
     * Qeyd publikdirmi - mövcuddur, silinməyib, aktivdir.
     *
     * Markdown/feed **HTML-dən çox məzmun açmamalıdır**: `SiteController::check_item()`
     * deaktiv qeydə 404 verir, burada da eyni qayda.
     */
    public function isPublic(?Model $item): bool
    {
        if ($item === null || !$item->exists) {
            return false;
        }

        if (in_array(SoftDeletes::class, class_uses_recursive($item), true) && $item->trashed()) {
            return false;
        }

        $active = $item->getAttributes()['is_active'] ?? $item->getAttributes()['status'] ?? true;

        return (bool) $active;
    }

    /**
     * Qeydin detal səhifəsi varmı - `DynamicContentController` onu
     * `$model->controller` sinfinin `single()` metodu ilə açır.
     */
    public function isRoutable(Model $item): bool
    {
        $controller = property_exists($item, 'controller') ? $item->controller : null;

        return is_string($controller) && $controller !== '' && class_exists($controller)
            && method_exists($controller, 'single');
    }

    /**
     * Tərcümə sahəsinin həmin dildəki dəyəri.
     *
     * `Translation` trait-i `translations` əlaqəsini onsuz da yükləyir; burada
     * yüklənmiş kolleksiyadan oxunur ki, siyahıda hər sahə üçün ayrıca sorğu
     * getməsin. Trait-i olmayan model üçün adi atribut qaytarılır.
     */
    public function translated(Model $item, string $key, string $locale): ?string
    {
        if ($item->relationLoaded('translations')) {
            $row = $item->getRelation('translations')
                ->first(fn ($t) => $t->key === $key && $t->locale === $locale);
            $value = $row?->value;
        } elseif (method_exists($item, 'getTranslation') && property_exists($item, 'translatedAttributes')) {
            $value = $item->getTranslation($key, $locale, true);
        } else {
            $value = $item->getAttributes()[$key] ?? null;
        }

        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }

    public function slug(Model $item, string $locale): ?string
    {
        return $this->translated($item, 'slug', $locale);
    }

    /** Qeydin başlığı: `title` → `name`. */
    public function title(Model $item, string $locale): ?string
    {
        return $this->translated($item, 'title', $locale) ?? $this->translated($item, 'name', $locale);
    }

    /** Detal səhifəsinin tam ünvanı (`/{dil}/{slug}`); slug yoxdursa `null`. */
    public function url(Model $item, string $locale): ?string
    {
        $slug = $this->slug($item, $locale);

        return $slug === null ? null : url("{$locale}/{$slug}");
    }

    /**
     * `/{dil}/{slug}` ünvanının sahibi olan publik məzmun qeydi.
     *
     * HTML tərəfdəki `FieldTranslation::getBySlug()` ilə eyni axtarışdır, lakin
     * nəticə keşlənmir: keşdəki model obyekti deaktiv ediləndən sonra da
     * «aktiv» qalırdı. Menyular burada axtarılmır - onlar `findMenu()`-dadır.
     */
    public function findBySlug(string $slug, string $locale): ?Model
    {
        if ($slug === '' || !Schema::hasTable('field_translations')) {
            return null;
        }

        $owners = FieldTranslation::query()
            ->select(['model_type', 'model_id'])
            ->where('key', 'slug')
            ->where('locale', $locale)
            ->where('value', $slug)
            ->where('model_type', '!=', Menu::class)
            ->limit(5)
            ->get();

        foreach ($owners as $owner) {
            $class = $owner->model_type;

            if (!is_string($class) || !class_exists($class) || !is_subclass_of($class, Model::class)) {
                continue;
            }

            $item = $class::query()->find($owner->model_id);

            if ($item !== null && $this->isRoutable($item) && $this->isPublic($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * HTML səhifəsi olan menyu bəndləri (ana səhifədən başqa).
     *
     * `routes/web.php` menyunu yalnız `config` (route sinfi) varsa qeyd edir;
     * konfiqurasiyasız menyu `/{dil}/{slug}` catch-all-a düşür və orada açılmır.
     *
     * @return Collection<int, Menu>
     */
    public function menuPages(string $locale): Collection
    {
        try {
            return Menu::getRoutes($locale)
                ->filter(function (Menu $menu): bool {
                    return $menu->config !== null
                        && $menu->route_name !== 'home'
                        && is_string($menu->route_slug)
                        && trim($menu->route_slug) !== '';
                })
                ->values();
        } catch (Throwable $e) {
            report($e);

            return collect();
        }
    }

    public function findMenu(string $slug, string $locale): ?Menu
    {
        return $this->menuPages($locale)->first(fn (Menu $menu): bool => $menu->route_slug === $slug);
    }

    /** Menyunun həmin dildəki başlığı. */
    public function menuTitle(Menu $menu, string $locale): string
    {
        return (string) ($menu->route_title ?: $this->translated($menu, 'title', $locale) ?: $menu->route_name);
    }

    /** Menyu səhifəsinin tam ünvanı. */
    public function menuUrl(Menu $menu, string $locale): string
    {
        return url("{$locale}/" . trim((string) $menu->route_slug, '/'));
    }

    /**
     * Siyahısı bu menyu səhifəsində göstərilən model (`list_menu`).
     *
     * @return class-string<Model>|null
     */
    public function modelForMenu(Menu $menu): ?string
    {
        foreach ($this->models() as $class => $options) {
            if (($options['list_menu'] ?? null) === $menu->route_name) {
                return $class;
            }
        }

        return null;
    }

    /** Sitemap üçün `lastmod`. */
    public function lastModified(Model $item): string
    {
        $date = $item->updated_at ?? $item->created_at;

        return $date ? $date->format('Y-m-d') : date('Y-m-d');
    }

    /**
     * Yayım tarixi: əvvəlcə redaktorun verdiyi `date_time`, sonra sistem tarixi.
     *
     * `date_time` bəzi modellərdə cast olunmayıb (mətn sütunu) - birbaşa
     * `->toRfc2822String()` çağırmaq xəta verərdi, ona görə Carbon-a çevrilir;
     * pozuq dəyərdə qeyd itmir, sadəcə sistem tarixi işlədilir.
     */
    public function publishedAt(Model $item): ?Carbon
    {
        $raw = $item->getAttributes()['date_time'] ?? null;

        if (!empty($raw)) {
            try {
                return Carbon::parse($raw);
            } catch (Throwable) {
                // tanınmayan format - aşağıdakı sistem tarixinə düşür
            }
        }

        $date = $item->updated_at ?? $item->created_at;

        return $date ? Carbon::parse($date) : null;
    }

    /**
     * Aktiv dil kodları; cədvəl boşdursa `app.locale`.
     *
     * @return array<int, string>
     */
    public function locales(): array
    {
        try {
            $codes = Schema::hasTable('languages')
                ? Language::getCachedAll()->pluck('code')->filter()->values()->all()
                : [];
        } catch (Throwable) {
            $codes = [];
        }

        return $codes !== [] ? $codes : [(string) config('app.locale', 'az')];
    }

    public function isActiveLocale(string $locale): bool
    {
        return in_array($locale, $this->locales(), true);
    }

    /**
     * Publik məzmunun barmaq izi - bütün keş qruplarının hazırkı versiyaları.
     *
     * Keş açarına qatılır. `CacheInvalidationServiceProvider` model (və onun
     * tərcüməsi - FieldTranslation sahibinin qrupunu təmizləyir) dəyişəndə
     * qrupun versiyasını artırır (`config/custom/cache.php` → model_groups):
     * yazı/menyu/tənzimləmə/meta redaktə olunanda açar dəyişir və köhnə sənəd
     * TTL-i gözləmədən yararsız olur. DB sorğusu getmir - versiyalar keşdədir.
     *
     * Bütün qruplar daxil edilir, çünki Markdown/sitemap sənədi menyu, səhifə,
     * tənzimləmə, əlaqə və meta datasını birlikdə göstərir; yeni qrup config-ə
     * əlavə olunanda avtomatik nəzərə alınır.
     */
    public function fingerprint(): string
    {
        $parts = [];

        foreach ((array) config('custom.cache.groups', []) as $group) {
            $parts[] = CacheKey::custom((string) $group, 'v', [], false);
        }

        return md5(implode('|', $parts));
    }
}
