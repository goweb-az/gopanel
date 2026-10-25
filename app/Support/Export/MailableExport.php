<?php

declare(strict_types=1);

namespace App\Support\Export;

/**
 * İxrac tərifi parametrlərdən özünü qura bilir.
 *
 * Süzgəc əsaslı siyahı ixracları (`new XExport($filters)`) bunu implement
 * ETMİR - `ExportRegistry` onları standart yolla qurur. İnterfeys yalnız bir
 * sətrə bağlı sənədlər (qaimə, kart) üçündür: onlar konstruktorda model
 * gözləyir və `uid`-i özləri tapmalıdır.
 *
 * NİYƏ `firstOrFail()` tövsiyə olunur: silinmiş sətrin açarı ilə növbəyə
 * düşən iş sükutla boş fayl göndərməməlidir.
 */
interface MailableExport
{
    /**
     * @param  array<string, mixed>  $params
     */
    public static function resolveExport(array $params): static;
}
