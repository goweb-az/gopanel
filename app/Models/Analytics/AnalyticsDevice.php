<?php

namespace App\Models\Analytics;

use App\Models\BaseModel;
use App\Traits\Presentable\Analytics\HasAnalyticsDevicePresentation;

class AnalyticsDevice extends BaseModel
{
    use HasAnalyticsDevicePresentation;

    protected $table = 'analytics_devices';

    public $logEnabled = false;

    protected $fillable = [
        'device_type',
        'icon',
        'hit_count',
        'first_visited_at',
        'last_visited_at',
    ];

    /*
     * @var \Illuminate\Database\Eloquent\Relations\HasMany $clicks
     * Bu cihaz üzerinden gelen tüm tıklamalar
     */
    public function clicks()
    {
        return $this->hasMany(AnalyticsClick::class, 'device_id');
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
