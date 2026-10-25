<?php

namespace App\Models\Contact;

use App\Models\BaseModel;
use App\Traits\Content\Translation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use App\Traits\Presentable\Contact\HasContactInfoPresentation;

class ContactInfo extends BaseModel
{
    use HasFactory, SoftDeletes, Translation, HasContactInfoPresentation;

    protected $table = 'contact_info';

    protected $fillable = [
        'phone',
        'mobile',
        'whatsapp',
        'support_whatsapp',
        'sales_whatsapp',
        'info_email',
        'support_email',
        'map',
    ];

    public $translatedAttributes = [
        'page_title',
        'page_description',
        'adress',
    ];

    public static function getCached(): ?self
    {
        return Cache::rememberForever('contact_info', function () {
            return self::with('translations')->first();
        });
    }
}
