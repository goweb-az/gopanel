<?php

namespace App\Models;

use App\Traits\Activity\LogsAdminActivity;
use App\Traits\Content\HasFiles;
use App\Traits\System\Cacheable;
use App\Traits\System\HasRouteKey;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    use HasRouteKey;
    use HasFiles;
    use LogsAdminActivity;
    use Cacheable;

    // Hansi modelde loglanmasi istenmirse false edilmelidir
    protected $logEnabled = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (in_array('order', $model->getFillable())) {
                $maxOrder = static::max('order');
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function getModelClass()
    {
        return get_class($this);
    }

    public function incrementViews()
    {
        $this->increment('views');
        $this->save();
    }
}
