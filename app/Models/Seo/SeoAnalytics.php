<?php

namespace App\Models\Seo;

use App\Models\BaseModel;
use Illuminate\Support\Facades\Cache;

class SeoAnalytics extends BaseModel
{
    protected $table = 'seo_analytics';

    protected $fillable = [
        'head',
        'body',
        'footer',
        'robots_txt',
        'ai_txt',
        'other',
    ];

    public $logEnabled = false;


    public static function getCached()
    {
        return Cache::rememberForever("seo_analytics", function () {
            return self::latest()->first();
        });
    }
}
