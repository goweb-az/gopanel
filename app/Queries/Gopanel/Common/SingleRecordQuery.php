<?php

declare(strict_types=1);

namespace App\Queries\Gopanel\Common;

use App\Models\BaseModel;

/**
 * «Tək sətirli səhifə» modelləri üçün oxuma: Haqqımızda, Əlaqə məlumatları,
 * Sayt tənzimləmələri, SEO analitika, llms.txt.
 *
 * NİYƏ ayrıca sinif:
 * Bu modellərin hamısında eyni sətir təkrarlanırdı -
 * `Model::latest()->first() ?? new Model()`. Təkrarın özü problem deyil,
 * problem odur ki, `?? new Model()` unudulanda səhifə boş modeldə deyil,
 * `null` üzərində sınır və panel ağ ekran verir. Qayda bir yerdə saxlanılır.
 *
 * `latest()` işlədilir (id yox): bu cədvəllərdə bir neçə sətir yığıla bilər
 * (köhnə seed, arxivlənmiş nüsxə) və panel HƏMİŞƏ ən sonuncunu göstərməlidir.
 */
class SingleRecordQuery
{
    /**
     * @param  class-string<BaseModel>  $modelClass
     */
    public function __construct(private readonly string $modelClass)
    {
    }

    /** Mövcud sətir, yoxdursa boş model - blade hər iki halda eyni işləyir. */
    public function currentOrNew(): BaseModel
    {
        return $this->current() ?? new $this->modelClass();
    }

    public function current(): ?BaseModel
    {
        return $this->modelClass::query()->latest()->first();
    }
}
