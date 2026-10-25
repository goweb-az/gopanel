<?php

namespace App\Traits\Presentable\Analytics;

/**
 * `AnalyticsOperatingSystem` modelinin göstəriş accessor-u.
 *
 * Niyə: analitika siyahısında ikon kiçik şəkil kimi çəkilir; ad DƏYİŞMƏYİB.
 */
trait HasAnalyticsOperatingSystemPresentation
{
    public function getIconImgAttribute()
    {
        if (is_null($this->icon))
            return null;
        return '<img src="' . e($this->icon) . '" width="25" alt="">';
    }
}
