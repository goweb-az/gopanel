<?php

namespace App\Models\Analytics;

use App\Models\BaseModel;

class AnalyticsCountry extends BaseModel
{
    protected $table = 'analytics_countries';

    public $logEnabled = false;

    protected $fillable = [
        'name',
        'iso_code',
        'flag',
        'hit_count',
        'first_visited_at',
        'last_visited_at',
    ];

    /*
     * @var \Illuminate\Database\Eloquent\Relations\HasMany $cities
     * Bu ülkeye bağlı tüm şehirler
     */
    public function cities()
    {
        return $this->hasMany(AnalyticsCity::class, 'country_id');
    }

    /*
     * @var \Illuminate\Database\Eloquent\Relations\HasMany $clicks
     * Bu ülke üzerinden gelen tüm tıklamalar
     */
    public function clicks()
    {
        return $this->hasMany(AnalyticsClick::class, 'country_id');
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


    public function getFlagImgAttribute()
    {
        if (is_null($this->flag))
            return null;
        return '<img src="' . $this->flag . '" width="25" alt="">';
    }
}
