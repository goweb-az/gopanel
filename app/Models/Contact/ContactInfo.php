<?php

namespace App\Models\Contact;

use App\Models\BaseModel;
use App\Traits\Translation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class ContactInfo extends BaseModel
{
    use HasFactory, SoftDeletes, Translation;

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


    public function getMapEmbedUrlAttribute(): ?string
    {
        if (empty($this->map)) {
            return null;
        }

        // artıq embed linkdirsə
        if (str_contains($this->map, '/maps/embed')) {
            return $this->map;
        }

        // koordinatlı Google Maps linki
        if (preg_match('/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $this->map, $matches)) {
            $lat = $matches[1];
            $lng = $matches[2];

            return "https://www.google.com/maps?q={$lat},{$lng}&hl=az&z=16&output=embed";
        }
        return null;
    }
}
