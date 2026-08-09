<?php

declare(strict_types=1);

namespace App\Support\Date;

use Carbon\Carbon;

/**
 * Bir günün başlanğıc/son anı - indeksdən istifadə edən tarix süzgəci üçün.
 *
 * NİYƏ LAZIMDIR: `whereDate('operation_at', $gun)` sorğusu sütunun üstünə
 * `DATE()` funksiyası qoyur və MySQL `(company_id, operation_at)` kompozit
 * indeksindən istifadə EDƏ BİLMİR - şirkətin bütün tarixçəsini oxuyub hər
 * sətir üçün funksiyanı hesablayır. Ölçüm (15 740 sətirlik şirkət):
 *
 *   whereDate  → key=attendance_company_id_foreign          skan ≈ 26 122 sətir
 *   aralıq     → key=attendance_company_operation_at_index  skan ≈    151 sətir
 *
 * Ona görə tarix süzgəcləri həmişə `>= başlanğıc AND <= son` formasında yazılır.
 */
final class DayRange
{
    /**
     * @return array{0: Carbon, 1: Carbon} [günün başlanğıcı, günün sonu]
     */
    public static function of(Carbon|string|null $date = null): array
    {
        $day = $date instanceof Carbon ? $date->copy() : Carbon::parse($date ?? 'now');

        return [$day->copy()->startOfDay(), $day->copy()->endOfDay()];
    }

    /**
     * @return array{0: Carbon, 1: Carbon} [bu günün başlanğıcı, sonu]
     */
    public static function today(): array
    {
        return self::of(Carbon::now());
    }
}
