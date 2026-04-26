<?php

namespace App\Models\Analytics;

use App\Models\BaseModel;

class AnalyticsLink extends BaseModel
{
    protected $table = 'analytics_links';

    public $logEnabled = false;

    protected $fillable = [
        'locale',
        'url',
        'slug',
        'hit_count',
        'first_visited_at',
        'last_visited_at',
    ];

    /*
     * @var \Illuminate\Database\Eloquent\Relations\HasMany $clicks
     * Bu linke ait tüm tıklamalar
     */
    public function clicks()
    {
        return $this->hasMany(AnalyticsClick::class, 'link_id');
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
