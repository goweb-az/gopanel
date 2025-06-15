<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\MetaData;
use App\Traits\Translation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Blog extends BaseModel
{
    use HasFactory, SoftDeletes, Translation, MetaData;

    protected $logEnabled = false;

    protected $fillable = [
        'image',
        'date_time',
        'is_active',
        'views'
    ];
    protected $files = ['image'];
    public $slug_key = 'title';
    public $translatedAttributes = ['title', 'description', 'slug'];

    public function getShortDescriptionAttribute()
    {
        return Str::limit(strip_tags($this->description), 50);
    }

    public function getShortDescriptionSiteAttribute()
    {
        return Str::limit(html_entity_decode(strip_tags($this->description)), 100);
    }


    public static function getBySlug($slug, $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return Cache::remember("site_blog_{$locale}_{$slug}", now()->addDays(5), function () use ($slug, $locale) {
            return self::whereHas('translations', function ($query) use ($slug, $locale) {
                $query->where('key', 'slug')
                    ->where('value', $slug)
                    ->where('locale', $locale);
            })->first();
        });
    }

    public function incrementViews()
    {
        $this->increment('views');
        $this->save();
    }


    public function getFormattedDateTimeAttribute()
    {
        if (empty($this->date_time))
            return $this->date_time;
        return Carbon::parse($this->date_time)->locale(app()->getLocale())->isoFormat('D MMMM YYYY');
    }
}
