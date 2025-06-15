<?php

namespace App\Models;

use App\Traits\Translation;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BaseModel extends Model
{
    use LogsActivity;

    // Hansi modelde loglanmas istenmirse false edilmelidir 
    protected $logEnabled = false;


    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('translations', function (Builder $builder) {
            if (property_exists($builder->getModel(), 'translatedAttributes') && !empty($builder->getModel()->translatedAttributes)) {
                $builder->with('translations');
            }
        });

        static::creating(function ($model) {
            if (in_array('order', $model->getFillable())) {
                $maxOrder = static::max('order');
                $model->order = $maxOrder + 1;
            }
        });
    }



    public function getActivitylogOptions(): LogOptions
    {
        // icaze yoxdursa loglama
        if (!$this->logEnabled) {
            return LogOptions::defaults()->logOnly([]);
        }

        return LogOptions::defaults()
            ->logOnly($this->getFillable()) // Modelin doldurula bilən sahələrini qeyd edin
            ->useLogName(class_basename($this)) // Günlük adı kimi modelin adını istifadə edin
            ->logOnlyDirty() // Yalnız dəyişdirilmiş sahələri qeyd edin
            ->dontSubmitEmptyLogs(); // Heç bir dəyişiklik edilmirsə, jurnalın yaradılması
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->description = class_basename($this) . " modelində {$eventName} əməliyyatı aparıldı.";
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'causer');
    }


    public function getModelClass()
    {
        return get_class($this);
    }


    public function getAttribute($key)
    {
        if (property_exists($this, 'files') && is_array($this->files)) {
            if (substr($key, -4) == '_url') {
                $fileAttribute = substr($key, 0, -4);
                if (in_array($fileAttribute, $this->files)) {
                    return $this->getFileUrl($fileAttribute);
                }
            }
        }

        return parent::getAttribute($key);
    }


    /**
     * Faylin yolunu qaytar
     * 
     * @param  string  $file
     * @return string
     */
    public function getFileUrl($file)
    {
        if (is_null($this->{$file}))
            return null;
        if (file_exists(public_path($this->{$file})))
            return url($this->{$file});
        return $this->{$file};
    }

    public static function getCachedAll()
    {
        $instance = new static();
        return Cache::remember("site_" . $instance->getTable(), now()->addDays(5), function () use ($instance) {
            $query = $instance->newQuery();
            if (in_array('is_active', $instance->getFillable())) {
                $query->where("is_active", true);
            }
            if (in_array('status', $instance->getFillable())) {
                $query->where("status", true);
            }
            if (in_array('slug', $instance->translatedAttributes ?? [])) {
                $query->whereHas('translations', function ($subQuery) {
                    $subQuery->where('key', 'slug')->whereNotNull('value');
                });
            }
            if (in_array('order', $instance->getFillable())) {
                $query->orderBy("order", "ASC");
            } else {
                $query->orderBy("id", "DESC");
            }
            return $query->get();
        });
    }


    public function incrementViews()
    {
        $this->increment('views');
        $this->save();
    }
}
