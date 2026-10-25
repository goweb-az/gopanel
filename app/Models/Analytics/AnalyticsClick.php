<?php

namespace App\Models\Analytics;

use App\Models\BaseModel;
use App\Traits\Presentable\Analytics\HasAnalyticsClickPresentation;

class AnalyticsClick extends BaseModel
{
    use HasAnalyticsClickPresentation;

    protected $table = 'analytics_clicks';

    public $logEnabled = false;

    protected $fillable = [
        'link_id',
        'device_id',
        'os_id',
        'browser_id',
        'country_id',
        'city_id',
        'language_id',
        'ip_address',
        'latitude',
        'longitude',
        'isp',
        'url',
        'referer',
    ];

    /*
     * @var \Illuminate\Database\Eloquent\Relations\BelongsTo $link
     */
    public function link()
    {
        return $this->belongsTo(AnalyticsLink::class, 'link_id');
    }

    /*
     * @var \Illuminate\Database\Eloquent\Relations\BelongsTo $device
     */
    public function device()
    {
        return $this->belongsTo(AnalyticsDevice::class, 'device_id');
    }

    /*
     * @var \Illuminate\Database\Eloquent\Relations\BelongsTo $operatingSystem
     */
    public function operatingSystem()
    {
        return $this->belongsTo(AnalyticsOperatingSystem::class, 'os_id');
    }

    /*
     * @var \Illuminate\Database\Eloquent\Relations\BelongsTo $browser
     */
    public function browser()
    {
        return $this->belongsTo(AnalyticsBrowser::class, 'browser_id');
    }

    /*
     * @var \Illuminate\Database\Eloquent\Relations\BelongsTo $country
     */
    public function country()
    {
        return $this->belongsTo(AnalyticsCountry::class, 'country_id');
    }

    /*
     * @var \Illuminate\Database\Eloquent\Relations\BelongsTo $city
     */
    public function city()
    {
        return $this->belongsTo(AnalyticsCity::class, 'city_id');
    }

    /*
     * @var \Illuminate\Database\Eloquent\Relations\BelongsTo $language
     */
    public function language()
    {
        return $this->belongsTo(AnalyticsLanguage::class, 'language_id');
    }

    /*
     * @var \Illuminate\Database\Eloquent\Relations\HasOne $utmParameters
     */
    public function utmParameters()
    {
        return $this->hasOne(AnalyticsUtmParameter::class, 'click_id');
    }

    /*
     * @var \Illuminate\Database\Eloquent\Relations\HasMany $adPlatformData
     */
    public function adPlatformData()
    {
        return $this->hasMany(AnalyticsAdPlatformData::class, 'click_id');
    }

    /*
     * @var \Illuminate\Database\Eloquent\Relations\HasMany $eventLogs
     */
    public function eventLogs()
    {
        return $this->hasMany(AnalyticsEventLog::class, 'click_id');
    }
}
