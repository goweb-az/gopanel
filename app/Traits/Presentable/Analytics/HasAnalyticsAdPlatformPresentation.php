<?php

namespace App\Traits\Presentable\Analytics;

/**
 * `AnalyticsAdPlatform` modelinin göstəriş accessor-u.
 *
 * Niyə: analitika siyahısında loqo kiçik ikon kimi çəkilir; ad DƏYİŞMƏYİB.
 */
trait HasAnalyticsAdPlatformPresentation
{
    public function getLogoImgAttribute()
    {
        if (is_null($this->logo))
            return null;
        return '<img src="' . e($this->logo) . '" width="25" alt="">';
    }
}
