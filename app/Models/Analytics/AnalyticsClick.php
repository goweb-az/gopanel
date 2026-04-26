<?php

namespace App\Models\Analytics;

use App\Models\BaseModel;

class AnalyticsClick extends BaseModel
{
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


    public function getUrlLinkAttribute()
    {
        if (empty($this->url)) {
            return null;
        }

        $url = $this->url;
        $max = 35;

        $short = strlen($url) > $max
            ? substr($url, 0, $max) . '...'
            : $url;

        return '<a href="' . e($url) . '" target="_blank" rel="noopener noreferrer">' . e($short) . '</a>';
    }

    public function getRefererLinkAttribute()
    {
        if (empty($this->referer)) {
            return null;
        }

        $referer = $this->referer;
        $max = 35;

        $short = strlen($referer) > $max
            ? substr($referer, 0, $max) . '...'
            : $referer;

        return '<a href="' . e($referer) . '" target="_blank" rel="noopener noreferrer">' . e($short) . '</a>';
    }


    public function getDeviceLinkAttribute()
    {
        return '<a href="' . route("gopanel.analytics.detail.clicks", ['device_id' => $this->device_id]) . '">' . $this?->device?->device_type . '</a>';
    }

    public function getOperatingLinkAttribute()
    {
        return '<a href="' . route("gopanel.analytics.detail.clicks", ['os_id' => $this->os_id]) . '">' . $this?->operatingSystem?->name . '</a>';
    }

    public function getBrowserLinkAttribute()
    {
        return '<a href="' . route("gopanel.analytics.detail.clicks", ['browser_id' => $this->browser_id]) . '">' . $this?->browser?->name . '</a>';
    }

    public function getCountryLinkAttribute()
    {
        return '<a href="' . route("gopanel.analytics.detail.clicks", ['country_id' => $this->country_id]) . '">' . $this?->country?->name . '</a>';
    }

    public function getCityLinkAttribute()
    {
        return '<a href="' . route("gopanel.analytics.detail.clicks", ['city_id' => $this->city_id]) . '">' . $this?->city?->name . '</a>';
    }

    public function getLanguageLinkAttribute()
    {
        return '<a href="' . route("gopanel.analytics.detail.clicks", ['language_id' => $this->language_id]) . '">' . $this?->language?->name . '</a>';
    }

    public function getIpAddressClickAttribute()
    {
        return '<span class="toSearch">' . $this?->ip_address . '</span>';
    }
}
