<?php

namespace App\Models\Seo;

use App\Models\BaseModel;
use Illuminate\Support\Facades\Cache;

class LlmsTxt extends BaseModel
{
    protected $table = 'llms_txts';

    protected $fillable = [
        'content',
    ];

    public $logEnabled = false;

    public static function getCached(): ?self
    {
        return Cache::rememberForever("llms_txt", function () {
            return self::latest()->first();
        });
    }
}
