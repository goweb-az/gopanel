<?php

namespace App\Models\Geography;

use App\Models\BaseModel;
use App\Traits\System\AddUuid;
use DateInterval;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Language extends BaseModel
{
    use AddUuid, HasFactory, SoftDeletes;

    protected $logEnabled = false;

    protected $fillable = [
        'country_id',
        'code',
        'name',
        'sort_order',
        'is_active',
        'is_show',
        'default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_show' => 'boolean',
        'default' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            static::clearLanguageCache();
        });

        static::deleted(function () {
            static::clearLanguageCache();
        });
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function getUpperCodeAttribute()
    {
        return mb_strtoupper($this->code, 'UTF-8');
    }

    public static function getCachedAll(DateTimeInterface|DateInterval|int|null $ttl = null): Collection
    {
        $instance = new static();

        return Cache::remember("site_" . $instance->getTable(), $ttl ?? now()->addDays(5), function () use ($instance) {
            return $instance->newQuery()
                ->where('is_active', true)
                ->orderByDesc('default')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        });
    }

    public static function clearLanguageCache(): void
    {
        $instance = new static();
        Cache::forget("site_" . $instance->getTable());
    }

    public static function getDefault(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->where('default', true)
            ->orderBy('id')
            ->first();
    }

    public static function getDefaultCode(string $fallback = 'az'): string
    {
        return static::getDefault()?->code ?? $fallback;
    }

    public static function ensureSingleDefault(self $item): void
    {
        if (!$item->default) {
            return;
        }

        static::query()
            ->whereKeyNot($item->getKey())
            ->update(['default' => false]);
    }

    public static function ensureFallbackDefault(string $fallback = 'az'): void
    {
        if (static::query()->where('default', true)->exists()) {
            return;
        }

        $language = static::query()
            ->where('is_active', true)
            ->where('code', $fallback)
            ->first();

        if (!$language) {
            $language = static::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();
        }

        if ($language) {
            $language->forceFill([
                'default' => true,
                'is_active' => true,
            ])->save();
        }
    }

    public static function getActiveCodesForRouteRegex(): string
    {
        $codes = self::getCachedAll()->pluck('code')->toArray();

        return implode('|', $codes) ?: 'az';
    }

    public function switchLanguage(): string
    {
        $currentUrl = url()->current();
        $baseUrl = url('/');
        $path = str_replace($baseUrl, '', $currentUrl);
        $path = ltrim($path, '/');
        $segments = $path ? explode('/', $path) : [];
        $activeCodes = self::getCachedAll()->pluck('code')->toArray();

        if (!empty($segments) && in_array($segments[0], $activeCodes, true)) {
            $segments[0] = $this->code;
        } else {
            array_unshift($segments, $this->code);
        }

        return $baseUrl . '/' . implode('/', $segments);
    }
}
