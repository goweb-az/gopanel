<?php

namespace App\Models\Analytics;

use App\Models\BaseModel;
use App\Traits\Presentable\Analytics\HasAnalyticsAdPlatformPresentation;

class AnalyticsAdPlatform extends BaseModel
{
    use HasAnalyticsAdPlatformPresentation;

    protected $table = 'analytics_ad_platforms';

    public $logEnabled = false;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'hit_count',
        'first_visited_at',
        'last_visited_at',
    ];

    /*
     * @var \Illuminate\Database\Eloquent\Relations\HasMany $adPlatformData
     * Bu platforma ait tüm parametre kayıtları
     */
    public function adPlatformData()
    {
        return $this->hasMany(AnalyticsAdPlatformData::class, 'platform_id');
    }

    /*
     * Ziyaret sayısını artırır ve last_visited_at bilgisini günceller
     */
    public function registerHit(): void
    {
        $this->increment('hit_count');
        $this->last_visited_at = now();

        if (!$this->first_visited_at) {
            $this->first_visited_at = now();
        }

        $this->save();
    }
}
