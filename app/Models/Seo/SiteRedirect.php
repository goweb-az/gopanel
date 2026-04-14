<?php

namespace App\Models\Seo;

use App\Enums\Gopanel\Seo\RedirectMatchTypeEnum;
use App\Helpers\Gopanel\GoPanelHelper;
use App\Models\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/* 
 * @var SiteRedirect $this
 * Yönlendirme kuralları (dil bazlı, exact/prefix/contains/regex).
 */

class SiteRedirect extends BaseModel
{
    protected $table = 'site_redirects';

    protected $logEnabled = false;

    protected $fillable = [
        'locale',
        'source',
        'match_type',
        'regex_flags',
        'target',
        'http_code',
        'is_active',
        'priority',
        'starts_at',
        'ends_at',
        'hits',
        'last_hit_at',
        'notes',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'priority'   => 'integer',
        'http_code'  => 'integer',
        'hits'       => 'integer',
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
    ];

    /* Aktif ve tarih aralığında olanlar */
    public function scopeActive(Builder $q): Builder
    {
        $now = Carbon::now();
        return $q->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopeForLocale(Builder $q, ?string $locale): Builder
    {
        // locale null olan (her dil) veya spesifik dili kapsasın
        return $q->where(function ($q) use ($locale) {
            $q->whereNull('locale');
            if ($locale) {
                $q->orWhere('locale', $locale);
            }
        });
    }

    /**
     * Verilen path veya tam URL, locale ve host’a göre eşleşir.
     * DÖNÜŞ: eşleşirse target + http_code; aksi halde null.
     */
    public function matches(string $urlOrPath, ?string $locale = null): bool
    {
        $source = $this->source;

        switch ($this->match_type) {
            case 'exact':
                return $urlOrPath === $source;
            case 'prefix':
                return str_starts_with($urlOrPath, $source);
            case 'contains':
                return str_contains($urlOrPath, $source);
            case 'regex':
                $flags = $this->regex_flags ?: 'i';
                // Güvenli regex sınırlandırması
                $pattern = '/' . str_replace('/', '\/', $source) . '/' . $flags;
                return @preg_match($pattern, $urlOrPath) === 1;
            default:
                return false;
        }
    }

    public function registerHit(): void
    {
        $this->increment('hits');
        $this->forceFill(['last_hit_at' => now()])->saveQuietly();
    }


    public function getMatchTypeNameAttribute()
    {
        return RedirectMatchTypeEnum::from($this->match_type)->label() ?? $this->match_type;
    }

    public function getIsActiveButtonAttribute()
    {
        return (new GoPanelHelper)->is_active_btn($this, "is_active", ($this->is_active == "1" ? true : false));
    }
}
