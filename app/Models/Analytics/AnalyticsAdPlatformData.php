<?php

namespace App\Models\Analytics;

use App\Models\BaseModel;

class AnalyticsAdPlatformData extends BaseModel
{
    protected $table = 'analytics_ad_platform_data';

    public $logEnabled = false;

    protected $fillable = [
        'click_id',
        'platform_id',
        'param_key',
        'param_value',
    ];

    /*
     * @var \Illuminate\Database\Eloquent\Relations\BelongsTo $click
     * Bu parametrelerin bağlı olduğu tıklama
     */
    public function click()
    {
        return $this->belongsTo(AnalyticsClick::class, 'click_id');
    }

    /*
     * @var \Illuminate\Database\Eloquent\Relations\BelongsTo $platform
     * Parametrenin ait olduğu reklam platformu
     */
    public function platform()
    {
        return $this->belongsTo(AnalyticsAdPlatform::class, 'platform_id');
    }
}
