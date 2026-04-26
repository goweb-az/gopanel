<?php

namespace App\Models\Analytics;

use App\Models\BaseModel;

class AnalyticsBrowser extends BaseModel
{
    protected $table = 'analytics_browsers';

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
     * Bu tarayıcı üzerinden gelen tüm tıklamalar
     */
    public function clicks()
    {
        return $this->hasMany(AnalyticsClick::class, 'browser_id');
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


    public function getIconImgAttribute()
    {
        if (is_null($this->icon))
            return null;
        return '<img src="' . $this->icon . '" width="25" alt="">';
    }
}
