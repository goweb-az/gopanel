<?php

namespace App\Models\Analytics;

use App\Models\BaseModel;
use App\Traits\Presentable\Analytics\HasAnalyticsOperatingSystemPresentation;

class AnalyticsOperatingSystem extends BaseModel
{
    use HasAnalyticsOperatingSystemPresentation;

    protected $table = 'analytics_operating_systems';

    public $logEnabled = false;

    protected $fillable = [
        'name',
        'icon',
        'hit_count',
        'first_visited_at',
        'last_visited_at',
    ];

    /*
     * @var \Illuminate\Database\Eloquent\Relations\HasMany $clicks
     * Bu işletim sistemi üzerinden gelen tüm tıklamalar
     */
    public function clicks()
    {
        return $this->hasMany(AnalyticsClick::class, 'os_id');
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
