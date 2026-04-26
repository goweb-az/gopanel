<?php

namespace App\Models\Analytics;

use App\Models\BaseModel;

class AnalyticsUtmParameter extends BaseModel
{
    protected $table = 'analytics_utm_parameters';

    public $logEnabled = false;

    protected $fillable = [
        'click_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
    ];

    /*
     * @var \Illuminate\Database\Eloquent\Relations\BelongsTo $click
     * UTM parametrelerinin bağlı olduğu tıklama
     */
    public function click()
    {
        return $this->belongsTo(AnalyticsClick::class, 'click_id');
    }
}
