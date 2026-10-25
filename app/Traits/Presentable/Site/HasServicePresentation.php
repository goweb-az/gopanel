<?php

namespace App\Traits\Presentable\Site;

use App\Enums\Common\SocialIconTypeEnum;
use Illuminate\Support\Str;

/**
 * `Service` modelinin göstəriş accessor-ları (ikon/şəkil hüceyrəsi, qısa mətn).
 *
 * Niyə: ikon dörd növdə saxlanılır (`SocialIconTypeEnum`) və hər növün HTML-i
 * fərqlidir - blade-də şərt yazılmasın deyə hazır verilir. Adlar DƏYİŞMƏYİB.
 *
 * Qeyd: SVG ikon (`<svg...>`) admin tərəfindən daxil edilir və qəsdən xam
 * çap olunur - qaçırılsa ikon əvəzinə kod görünər.
 */
trait HasServicePresentation
{
    public function getShortDescriptionViewAttribute(): string
    {
        return Str::limit(strip_tags($this->short_description ?? ''), 80);
    }

    public function getImageViewAttribute(): string
    {
        if (empty($this->image_url)) {
            return '<span class="text-muted">Şəkil yoxdur</span>';
        }

        return '<img src="' . e($this->image_url) . '" alt="' . e($this->title ?? 'Service') . '" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">';
    }

    public function getIconViewAttribute(): string
    {
        if (empty($this->icon)) {
            return '<span class="text-muted">İkon yoxdur</span>';
        }

        if ($this->icon_type === SocialIconTypeEnum::Image) {
            return '<img src="' . e(asset($this->icon)) . '" alt="' . e($this->title ?? 'Service') . '" style="width:34px;height:34px;object-fit:contain;">';
        }

        if (str_starts_with(trim($this->icon), '<')) {
            return '<span style="font-size:20px;">' . $this->icon . '</span>';
        }

        return '<i class="' . e($this->icon) . '" style="font-size:20px;"></i>';
    }

    /** Formada ikon sahəsinin dəyəri (şəkil üçün tam URL). */
    public function getIconValueAttribute(): string
    {
        return $this->icon_type === SocialIconTypeEnum::Image
            ? asset($this->icon)
            : (string) $this->icon;
    }
}
