<?php

namespace App\Traits\Presentable\Analytics;

/**
 * `AnalyticsBrowser` modelinin göstəriş accessor-u.
 *
 * Niyə: analitika siyahısında ikon kiçik şəkil kimi çəkilir; ad DƏYİŞMƏYİB.
 */
trait HasAnalyticsBrowserPresentation
{
    public function getIconImgAttribute()
    {
        if (is_null($this->icon))
            return null;
        return '<img src="' . e($this->icon) . '" width="25" alt="">';
    }
}
