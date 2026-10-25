<?php

namespace App\Traits\Presentable\Navigation;

use App\Enums\Common\SocialIconTypeEnum;

/**
 * `Category` modelinin göstəriş accessor-ları (ikon hüceyrəsi + forma dəyəri).
 *
 * Niyə: kateqoriya ağacı və siyahı ikonu eyni cür çəkməlidir; blade-də şərt
 * yazılmasın deyə hazır HTML verilir. Adlar DƏYİŞMƏYİB.
 */
trait HasCategoryPresentation
{
    /** GoPanel siyahısında ikonun hazır HTML-i. */
    public function getIconViewAttribute(): string
    {
        if (empty($this->icon)) {
            return '<i class="fas fa-tag text-muted"></i>';
        }

        if ($this->icon_type === SocialIconTypeEnum::Image) {
            return '<img src="' . e(asset($this->icon)) . '" alt="category" style="width:20px;height:20px;object-fit:contain;">';
        }

        return '<i class="' . e($this->icon) . '"></i>';
    }

    /** Formada ikon sahəsinin dəyəri (şəkil üçün tam URL). */
    public function getIconValueAttribute(): string
    {
        return $this->icon_type === SocialIconTypeEnum::Image
            ? asset($this->icon)
            : (string) $this->icon;
    }
}
