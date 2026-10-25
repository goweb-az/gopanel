<?php

namespace App\Models;

use App\Traits\Activity\LogsAdminActivity;
use App\Traits\Content\HasFiles;
use App\Traits\System\Cacheable;
use App\Traits\System\HasNotFoundMessage;
use App\Traits\System\HasRouteKey;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    use HasRouteKey;
    use HasFiles;
    use LogsAdminActivity;
    use Cacheable;
    // `Model::notFoundMessage()` → «Blog tapılmadı.» - hər modeldə ayrıca `use` yazılmasın.
    use HasNotFoundMessage;

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

            // `sort_order` sütunlu cədvəllər (lookup-lar, slayder...) üçün eyni
            // davranış: yeni sətir siyahının SONUNA düşür. Əks halda hər yeni
            // sətir DB default-u (0) alıb siyahının BAŞINA sıçrayırdı.
            // Yalnız dəyər ümumiyyətlə verilməyibsə (null) işləyir - açıq
            // verilən sıra (0 daxil; seeder, import, ağac) olduğu kimi qalır.
            // `order` budağına toxunulmur (geriyə uyğunluq).
            if (
                in_array('sort_order', $model->getFillable(), true)
                && $model->getAttribute('sort_order') === null
            ) {
                // Scope-suz: arxivlənmiş (soft-deleted) sətir bərpa olunanda
                // yenisi ilə eyni nömrəni almasın.
                $model->sort_order = ((int) $model->newQueryWithoutScopes()->max('sort_order')) + 1;
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
