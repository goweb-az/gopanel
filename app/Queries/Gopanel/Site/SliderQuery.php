<?php

declare(strict_types=1);

namespace App\Queries\Gopanel\Site;

use App\Models\Site\Slider;
use Illuminate\Database\Eloquent\Collection;

/**
 * Slayder siyahısı (panel).
 *
 * Sıralama `sort_order` üzrədir - siyahı sürüşdürməklə düzülür (`sortable`)
 * və istifadəçinin gördüyü ardıcıllıq saytdakı ilə eyni olmalıdır. `id` üzrə
 * sıralama bu bağlantını qırır.
 */
class SliderQuery
{
    public function ordered(): Collection
    {
        return Slider::query()->orderBy('sort_order', 'ASC')->get();
    }
}
