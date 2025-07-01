<?php

namespace App\Models\Settings;

use App\Models\BaseModel;
use App\Traits\MetaData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiteSetting extends BaseModel
{
    use HasFactory, SoftDeletes, MetaData;

    protected $logEnabled = false;

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
}
