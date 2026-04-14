<?php

namespace App\Models\Settings;

use App\Models\BaseModel;
use App\Traits\MetaData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends BaseModel
{
    use HasFactory, SoftDeletes, MetaData;

    protected $logEnabled = true;

    protected $fillable = [
        'site_status',
        'login_status',
        'register_status',
        'payment_status',
        'logo_light',
        'logo_dark',
        'mail_logo',
        'gopanel_logo',
    ];

    protected $files = ['logo_light', 'logo_dark', 'mail_logo', 'gopanel_logo'];


    public static function getCached($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return Cache::rememberForever("site_settings{$locale}", function () {
            return self::latest()->first();
        });
    }
}
