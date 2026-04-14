<?php

namespace App\Models\Navigation;

use App\Enums\Common\Menu\MenuTypeEnum;
use App\Enums\Common\Menu\MenuPositionEnum;
use App\Helpers\Site\MenuHelper;
use App\Models\BaseModel;
use App\Models\Translations\FieldTranslation;
use App\Traits\MetaData;
use App\Traits\Translation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class Menu extends BaseModel
{
    use HasFactory, SoftDeletes, Translation, MetaData;

    protected $fillable = [
        'parent_id',
        'type',
        'position',
        'route_name',
        'function_name',
        'menuable_type',
        'menuable_id',
        'sort_order',
        'is_active',
        'is_dropdown',
    ];

    public $translatedAttributes = ['title', 'description', 'slug'];

    protected $casts = [
        'is_active'     => 'boolean',
        'is_dropdown'   => 'boolean',
    ];


    protected static function booted()
    {
        static::addGlobalScope('sort_order', function ($query) {
            $query->orderBy('sort_order', 'asc');
        });

        static::creating(function ($model) {
            // sort_order her yeni kayıtta +1 olsun
            $maxSort = static::max('sort_order');
            $model->sort_order = $maxSort ? $maxSort + 1 : 1;
        });
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->where("is_active", true);
    }

    public function menuable()
    {
        return $this->morphTo();
    }

    // public function translations()
    // {
    //     return $this->morphMany(FieldTranslation::class, 'model');
    // }

    public static function getBySlug($slug, $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return Cache::remember("site_menu_by_slug_{$locale}_{$slug}", now()->addDays(30), function () use ($slug, $locale) {
            return self::whereHas('translations', function ($query) use ($slug, $locale) {
                $query->where('key', 'slug')
                    ->where('value', $slug)
                    ->where('locale', $locale);
            })->first();
        });
    }


    public static function getByRouteName($route_name, $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return Cache::rememberForever("site_menu_by_route_name_{$locale}_{$route_name}", function () use ($route_name, $locale) {
            return self::where("route_name", $route_name)->first();
        });
    }


    public function getMenuTypeAttribute()
    {
        return MenuTypeEnum::from($this->type)->label() ?? $this->type;
    }

    public function getMenuPositionAttribute()
    {
        return MenuPositionEnum::from($this->position)->label() ?? $this->position;
    }

    public static function getView()
    {
        return Cache::remember("site_menu_view", now()->addDays(30), function () {
            return self::whereNull("parent_id")
                ->where("is_active", true)
                ->with(['children' => fn($q) => $q->orderBy('sort_order', 'ASC')])
                ->orderBy("sort_order", "ASC")
                ->get();
        });
    }


    public static function getRoutes($locale)
    {
        if (!Schema::hasTable('menus')) {
            return collect();
        }

        return Cache::remember("site_menu_routes_newx_{$locale}", now()->addDays(30), function () use ($locale) {
            return self::query()
                ->select(
                    'menus.*',
                    'ft_slug.value as route_slug',
                    'ft_title.value as route_title',
                    'ft_description.value as route_description'
                )
                ->where("menus.is_active", true)
                ->leftJoin('field_translations as ft_slug', function ($join) use ($locale) {
                    $join->on('menus.id', '=', 'ft_slug.model_id')
                        ->where('ft_slug.model_type', '=', self::class)
                        ->where('ft_slug.key', '=', 'slug')
                        ->where('ft_slug.locale', '=', $locale);
                })
                ->leftJoin('field_translations as ft_title', function ($join) use ($locale) {
                    $join->on('menus.id', '=', 'ft_title.model_id')
                        ->where('ft_title.model_type', '=', self::class)
                        ->where('ft_title.key', '=', 'title')
                        ->where('ft_title.locale', '=', $locale);
                })
                ->leftJoin('field_translations as ft_description', function ($join) use ($locale) {
                    $join->on('menus.id', '=', 'ft_description.model_id')
                        ->where('ft_description.model_type', '=', self::class)
                        ->where('ft_description.key', '=', 'description')
                        ->where('ft_description.locale', '=', $locale);
                })
                ->get();
        });
    }


    /**
     * Sayt üçün menyu itemləri (header/footer göstərmək üçün)
     */
    public static function getSiteMenu($locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        if (!\Illuminate\Support\Facades\Schema::hasTable('menus')) {
            return collect();
        }

        return Cache::remember("site_menu_view_{$locale}", now()->addDays(30), function () use ($locale) {
            return self::query()
                ->where('is_active', true)
                ->whereNull('parent_id')
                ->with(['children' => function ($q) {
                    $q->where('is_active', true)->orderBy('sort_order', 'asc');
                }])
                ->orderBy('sort_order', 'asc')
                ->get();
        });
    }


    public function getConfigAttribute(): ?array
    {
        $routes = config("site.menu_routes");
        return $routes[$this->route_name] ?? null;
    }


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

    public function callFunction($type = 'desktop')
    {
        $function = $this->function_name;

        if ($function && method_exists(MenuHelper::class, $function)) {
            return MenuHelper::$function($this, $type);
        }

        return null;
    }

    private function getCurrentSlugData($code, $slug)
    {
        return Cache::rememberForever("site_field_translations_current_{$code}_{$slug}", function () use ($code, $slug) {
            return FieldTranslation::where('locale', $code)
                ->where('key', 'slug')
                ->where('value', $slug)
                ->first();
        });
    }

    private function getNewSlugData($currentLang, $translation, $slug)
    {
        return Cache::rememberForever("site_field_translations_new_{$currentLang}_{$slug}", function () use ($currentLang, $translation) {
            return FieldTranslation::where('locale', $currentLang)
                ->where('key', 'slug')
                ->where('model_type', $translation->model_type)
                ->where('model_id', $translation->model_id)
                ->first();
        });
    }
}
