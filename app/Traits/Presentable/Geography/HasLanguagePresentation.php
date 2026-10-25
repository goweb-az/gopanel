<?php

namespace App\Traits\Presentable\Geography;

/**
 * `Language` modelinin göstəriş accessor-ları.
 *
 * Niyə: dil seçicisində kod böyük hərflə (`AZ`) göstərilir; `mb_` funksiyası
 * ilə, çünki dil kodu gələcəkdə ASCII olmayan ola bilər. Ad DƏYİŞMƏYİB.
 */
trait HasLanguagePresentation
{
    public function getUpperCodeAttribute()
    {
        return mb_strtoupper($this->code, 'UTF-8');
    }
}
