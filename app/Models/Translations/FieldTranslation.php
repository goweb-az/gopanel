<?php

namespace App\Models\Translations;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FieldTranslation extends BaseModel
{

    use HasFactory, SoftDeletes;

    protected $logEnabled = false;

    protected $fillable = [
        'model_type',
        'model_id',
        'locale',
        'key',
        'value'
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if ($model->key == 'slug' && !empty($model->value)) {
                $baseSlug   = Str::slug($model->value, "-", $model->locale);
                $slug       = $baseSlug;
                $i          = 1;

                while (self::where('key', 'slug')
                    ->where('value', $slug)
                    ->where('locale', $model->locale)
                    ->where('model_type', $model->model_type)
                    ->exists()
                ) {
                    $slug = $baseSlug . '-' . $i;
                    $i++;
                }

                $model->value = $slug;
            }
        });
    }


    public function model()
    {
        return $this->morphTo();
    }

    public static function getBySlug($slug, $locale = null)
    {

        $locale = $locale ?? app()->currentLocale();

        return Cache::remember("slug_{$slug}_{$locale}", now()->addDays(30), function () use ($slug, $locale) {
            return self::with("model")
                ->where("key", "slug")
                ->where("locale", $locale)
                ->where("value", $slug)
                ->first();
        });
    }
}
