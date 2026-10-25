<?php

namespace App\Traits\Presentable\Seo;

use App\Enums\Gopanel\Seo\RedirectMatchTypeEnum;
use App\Helpers\Gopanel\GoPanelHelper;

/**
 * `SiteRedirect` modelinin göstəriş accessor-ları.
 *
 * Niyə: modeldə uyğunluq məntiqi (`matches()`, scope-lar) qalır; siyahıda
 * görünən ad/ton bura ayrılıb. `match_type_name` və `is_active_button` adları
 * DƏYİŞMƏYİB - yönləndirmələr datatable-ı işlədir.
 */
trait HasSiteRedirectPresentation
{
    /** Köhnə ad. Naməlum dəyərdə `from()` istisna atır - davranış saxlanılıb. */
    public function getMatchTypeNameAttribute()
    {
        return RedirectMatchTypeEnum::from($this->match_type)->label() ?? $this->match_type;
    }

    /** Lüğətə uyğun ad; naməlum/boş dəyərdə xam dəyəri qaytarır, istisna atmır. */
    public function getMatchTypeLabelAttribute(): string
    {
        return RedirectMatchTypeEnum::tryFrom((string) $this->match_type)?->label() ?? (string) $this->match_type;
    }

    public function getMatchTypeClassAttribute(): string
    {
        return RedirectMatchTypeEnum::tryFrom((string) $this->match_type)?->className() ?? 'secondary';
    }

    public function getIsActiveButtonAttribute()
    {
        return (new GoPanelHelper)->is_active_btn($this, "is_active", ($this->is_active == "1" ? true : false));
    }
}
