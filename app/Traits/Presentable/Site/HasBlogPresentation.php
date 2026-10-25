<?php

namespace App\Traits\Presentable\Site;

use App\Support\Date\LocalizedDateFormatter;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * `Blog` modelinin göstəriş accessor-ları (panel siyahısı + sayt kartı).
 *
 * Niyə: modeldə tərcümə/meta/keş məntiqi qalır, mətn qısaltma və tarix
 * formatı bura ayrılıb. Köhnə adlar DƏYİŞMƏYİB (`short_description`,
 * `short_description_site`, `formatted_date_time`, `single_url` sayt
 * view-larında, `is_active_btn` datatable-da işlədilir).
 *
 * Qeyd: `image_view` sorğusunu `HasFiles::getAttribute()` (BaseModel) əvvəl
 * tutur - `image` həm `$files`-da, həm `$fillable`-dadır. Accessor geriyə
 * uyğunluq üçün saxlanılıb.
 */
trait HasBlogPresentation
{
    public function getShortDescriptionAttribute()
    {
        return Str::limit(strip_tags($this->description), 50);
    }

    public function getShortDescriptionSiteAttribute()
    {
        return Str::limit(html_entity_decode(strip_tags($this->description)), 100);
    }

    public function getImageViewAttribute(): string
    {
        if (empty($this->image_url)) {
            return '<span class="text-muted">Şəkil yoxdur</span>';
        }

        return '<img src="' . e($this->image_url) . '" alt="' . e($this->title ?? 'Blog') . '" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">';
    }

    public function getIsActiveBtnAttribute(): string
    {
        return app('gopanel')->toggle_btn($this, 'is_active', $this->is_active == 1);
    }

    /** Köhnə ad və format («5 oktyabr 2026»); yeni kod `date_time_formatted` işlətsin. */
    public function getFormattedDateTimeAttribute()
    {
        if (empty($this->date_time)) {
            return $this->date_time;
        }

        return Carbon::parse($this->date_time)->locale(app()->getLocale())->isoFormat('D MMMM YYYY');
    }

    /** Lüğətə uyğun ad; format ortaq `LocalizedDateFormatter`-dən gəlir. */
    public function getDateTimeFormattedAttribute(): ?string
    {
        return LocalizedDateFormatter::date($this->date_time);
    }

    public function getSingleUrlAttribute(): ?string
    {
        $locale = app()->getLocale();
        $slug = $this->getTranslation('slug', $locale, true);

        if (!$slug) {
            return null;
        }

        return url("{$locale}/{$slug}");
    }
}
