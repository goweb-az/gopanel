<?php

namespace App\Models;

use App\Helpers\Common\ActivityLogHelper;
use App\Traits\HasRouteKey;
use App\Traits\Translation;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BaseModel extends Model
{
    use HasRouteKey;
    use LogsActivity {
        shouldLogEvent as traitShouldLogEvent;
    }

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



    protected function shouldLogEvent(string $eventName): bool
    {
        if ($this->logEnabled === false) {
            return false;
        }

        $modelName = class_basename($this);

        if (!config("custom.activity_messages.{$modelName}")) {
            return false;
        }

        return $this->traitShouldLogEvent($eventName);
    }

    public function getActivitylogOptions(): LogOptions
    {
        if ($this->logEnabled === false) {
            return LogOptions::defaults()
                ->dontSubmitEmptyLogs()
                ->logOnly([]);
        }

        return LogOptions::defaults()
            ->logOnly($this->getFillable())
            ->useLogName(class_basename($this))
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        // Causer-ı əllə təyin et: əvvəlcə gopanel, sonra web guard
        if (Auth::guard('gopanel')->check()) {
            $activity->causer()->associate(Auth::guard('gopanel')->user());
        } elseif (Auth::guard('web')->check()) {
            $activity->causer()->associate(Auth::guard('web')->user());
        }

        ActivityLogHelper::resolveDescription($this, $activity, $eventName);
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

            if (property_exists($this, 'fillable') && is_array($this->fillable)) {
                if (substr($key, -5) == '_view') {
                    $field = substr($key, 0, -5);
                    if (in_array($field, $this->fillable)) {
                        return $this->getFieldView($field);
                    }
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


    public function getFieldView($field)
    {
        if (is_null($this->{$field})) return null;

        $ext = pathinfo($this->{$field}, PATHINFO_EXTENSION);
        $ext = strtolower($ext);
        $path = $this->getFileUrl($field);
        // Sadəcə şəkil formatları üçün (PNG, JPG, JPEG, GIF, WEBP)
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
            return '<a href="' . $path . '" target="_blank"><img src="' . $path . '" width="50"></a>';
        }

        return '<a href="' . $path . '" target="_blank">Fayla bax</a>';
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
