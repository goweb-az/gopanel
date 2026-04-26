<?php

namespace App\Models\Analytics;

use App\Models\BaseModel;

class AnalyticsCity extends BaseModel
{
    protected $table = 'analytics_cities';

    public $logEnabled = false;

    protected $fillable = [
        'country_id',
        'name',
        'hit_count',
        'first_visited_at',
        'last_visited_at',
    ];

    /*
     * @var \Illuminate\Database\Eloquent\Relations\BelongsTo $country
     * Şehrin bağlı olduğu ülke
     */
    public function country()
    {
        return $this->belongsTo(AnalyticsCountry::class, 'country_id');
    }

    /*
     * @var \Illuminate\Database\Eloquent\Relations\HasMany $clicks
     * Bu şehirden gelen tüm tıklamalar
     */
    public function clicks()
    {
        return $this->hasMany(AnalyticsClick::class, 'city_id');
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
