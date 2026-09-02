<?php

declare(strict_types=1);

namespace App\Queries\Gopanel\Navigation;

use App\Models\Navigation\Category;
use Illuminate\Database\Eloquent\Collection;

/**
 * Kateqoriya ağacının panel üçün oxunması.
 *
 * NİYƏ Query sinifi:
 * Ağac sorğusu `with(['children' => fn ($q) => $q->orderBy(...)])` şəklində
 * controller-in içində yazılırdı. Eyni sorğu forma səhifəsində də lazım olur
 * və iki yerdə sıralama fərqli qalanda panel ilə saytda ardıcıllıq üst-üstə
 * düşmürdü. İndi sıralama qaydası tək yerdədir.
 */
class CategoryQuery
{
    /**
     * Kök kateqoriyalar + birinci səviyyə övladlar (sıralanmış).
     *
     * `childrenRecursive` işlədilmir: panel siyahısı iki səviyyə göstərir,
     * rekursiya isə hər səviyyə üçün əlavə sorğu deməkdir.
     */
    public function tree(): Collection
    {
        return Category::query()
            ->with(['children' => fn ($q) => $q->orderBy('sort_order', 'ASC')])
            ->whereNull('parent_id')
            ->orderBy('sort_order', 'ASC')
            ->get();
    }

    /** Forma-da «valideyn» seçimi üçün: yalnız kök kateqoriyalar. */
    public function roots(): Collection
    {
        return Category::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order', 'ASC')
            ->get();
    }
}
