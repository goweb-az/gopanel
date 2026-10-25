<?php

namespace App\Traits\Presentable\Contact;

use App\Enums\Common\SocialIconTypeEnum;

/**
 * `Social` (sosial şəbəkə linki) modelinin göstəriş accessor-ları.
 *
 * Niyə: ikon dörd növdə saxlanılır və sayt footer-i, panel siyahısı eyni HTML-i
 * istəyir. Adlar DƏYİŞMƏYİB.
 *
 * Qeyd: SVG / font / mətn ikonu admin tərəfindən daxil edilir və qəsdən xam
 * qaytarılır (SVG qaçırılsa kod görünər). Şəkil URL-i və `name` qaçırılır.
 */
trait HasSocialPresentation
{
    public function getIconHtmlAttribute(): string
    {
        if ($this->icon_type === SocialIconTypeEnum::Image) {
            $url = asset($this->icon);
            return '<img src="' . e($url) . '" alt="' . e($this->name) . '">';
        }

        if (in_array($this->icon_type->value, SocialIconTypeEnum::values())) {
            return $this->icon; // SVG, FONT və STRING birbaşa qaytarılır (onsuz da HTML və ya mətndir)
        }

        return '';
    }

    public function getIconValueAttribute(): string
    {
        return $this->icon_type === SocialIconTypeEnum::Image
            ? asset($this->icon)
            : $this->icon;
    }

    public function getIconTypeLabelAttribute(): ?string
    {
        return $this->icon_type?->label();
    }
}
