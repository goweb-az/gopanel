<?php

namespace App\Traits\Presentable\Analytics;

/**
 * `AnalyticsLanguage` modelinin göstəriş accessor-u.
 *
 * Niyə: analitika siyahısında bayraq kiçik şəkil kimi çəkilir; ad DƏYİŞMƏYİB.
 */
trait HasAnalyticsLanguagePresentation
{
    public function getFlagImgAttribute()
    {
        if (is_null($this->flag))
            return null;
        return '<img src="' . e($this->flag) . '" width="25" alt="">';
    }
}
