<?php

namespace App\Traits\Presentable\Site;

use Illuminate\Support\Str;

/**
 * `Product` modelinin göstəriş accessor-ları: qiymət, endirim, şəkil hüceyrəsi.
 *
 * Niyə: qiymət formatı (`100.00 ₼`) panel siyahısında və testlərdə eyni
 * olmalıdır; modeldə qalanda hər yeni sütunla model şişirdi. Köhnə adlar
 * DƏYİŞMƏYİB - `ProductDatatable` və `ProductModuleTest` onlardan asılıdır.
 *
 * `*_view` adları köhnədir (HTML ola bilər); yeni `*_formatted` accessor-ları
 * düz mətn qaytarır. Diqqət: `image_view`, `price_view`, `discount_view`
 * sorğusunu `HasFiles::getAttribute()` (BaseModel) əvvəl tutur, çünki həmin
 * sütunlar `$fillable`-dadır və modeldə `$files` var - accessor-lar geriyə
 * uyğunluq üçün saxlanılıb. Yeni kodda `*_formatted` işlədin.
 */
trait HasProductPresentation
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

        return '<img src="' . e($this->image_url) . '" alt="' . e($this->title ?? 'Product') . '" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">';
    }

    public function getPriceViewAttribute(): string
    {
        return number_format((float) $this->price, 2, '.', ' ') . ' ₼';
    }

    public function getDiscountViewAttribute(): string
    {
        if (is_null($this->discount)) {
            return '<span class="text-muted">—</span>';
        }

        return '<span class="text-danger">' . number_format((float) $this->discount, 2, '.', ' ') . ' ₼</span>';
    }

    /** Endirimli qiymət; mənfiyə düşmür. */
    public function getFinalPriceAttribute(): float
    {
        $price = (float) $this->price;
        $discount = (float) ($this->discount ?? 0);

        return max(0, $price - $discount);
    }

    public function getPriceWithDiscountViewAttribute(): string
    {
        $price = number_format((float) $this->price, 2, '.', ' ') . ' ₼';

        if (is_null($this->discount) || (float) $this->discount <= 0) {
            return '<strong>' . $price . '</strong>';
        }

        $final = number_format($this->final_price, 2, '.', ' ') . ' ₼';

        return '<span class="text-decoration-line-through text-muted me-1">' . $price . '</span>'
            . '<strong class="text-danger">' . $final . '</strong>';
    }

    /** Endirim yoxdursa null - düz mətn (API/export üçün). */
    public function getDiscountFormattedAttribute(): ?string
    {
        if (is_null($this->discount)) {
            return null;
        }

        return number_format((float) $this->discount, 2, '.', ' ') . ' ₼';
    }

    public function getFinalPriceFormattedAttribute(): string
    {
        return number_format($this->final_price, 2, '.', ' ') . ' ₼';
    }

    public function getIsActiveBtnAttribute(): string
    {
        return app('gopanel')->toggle_btn($this, 'is_active', $this->is_active == 1);
    }
}
