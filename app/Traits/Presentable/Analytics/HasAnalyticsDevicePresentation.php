<?php

namespace App\Traits\Presentable\Analytics;

/**
 * `AnalyticsDevice` modelinin göstəriş accessor-u.
 *
 * Niyə: cihaz ikonu FA6 adı ilə (`fa-solid`) yazılır, panel isə FA5 işlədir -
 * çevirmə burada edilir ki, panel boş kvadrat göstərməsin. Ad DƏYİŞMƏYİB.
 */
trait HasAnalyticsDevicePresentation
{
    public function getIconFontAttribute()
    {
        if (is_null($this->icon))
            return null;
        $icon = str_replace('fa-solid', 'fas', $this->icon);
        return '<i class="' . e($icon) . '"></i>';
    }
}
