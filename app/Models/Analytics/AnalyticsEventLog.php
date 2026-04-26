<?php

namespace App\Models\Analytics;

use App\Models\BaseModel;

class AnalyticsEventLog extends BaseModel
{
    protected $table = 'analytics_event_logs';

    public $logEnabled = false;

    protected $fillable = [
        'click_id',
        'event_type',
        'event_value',
    ];

    /*
     * @var \Illuminate\Database\Eloquent\Relations\BelongsTo $click
     * Bu olayın bağlı olduğu tıklama
     */
    public function click()
    {
        return $this->belongsTo(AnalyticsClick::class, 'click_id');
    }
}
