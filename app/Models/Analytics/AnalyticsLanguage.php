<?php

namespace App\Models\Analytics;

use App\Models\BaseModel;

class AnalyticsLanguage extends BaseModel
{
    protected $table = 'analytics_languages';

    public $logEnabled = false;

    protected $fillable = [
        'code',
        'name',
        'flag',
        'hit_count',
        'first_visited_at',
        'last_visited_at',
    ];

    /*
     * @var \Illuminate\Database\Eloquent\Relations\HasMany $clicks
     * Bu dil üzerinden gelen tüm tıklamalar
     */
    public function clicks()
    {
        return $this->hasMany(AnalyticsClick::class, 'language_id');
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
