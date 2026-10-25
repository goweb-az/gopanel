<?php

namespace App\Models\Site;

use App\Models\BaseModel;
use App\Traits\Content\MetaData;
use App\Traits\Content\Translation;
use DateInterval;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use App\Traits\Presentable\Site\HasBlogPresentation;

class Blog extends BaseModel
{
    use HasFactory, SoftDeletes, Translation, MetaData, HasBlogPresentation;

    protected $logEnabled = false;

    protected $fillable = [
        'image',
        'date_time',
        'is_active',
        'views',
    ];

    protected $files = ['image'];

    public $slug_key = 'title';
    public $translatedAttributes = ['title', 'description', 'slug'];
    public $controller = \App\Http\Controllers\Site\BlogController::class;

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

    public static function getCachedAll(DateTimeInterface|DateInterval|int|null $ttl = null): Collection
    {
        $locale = app()->getLocale();

        return Cache::remember("site_blogs_all_{$locale}", $ttl ?? now()->addDays(5), function () {
            return self::where('is_active', true)->latest()->get();
        });
    }
}
