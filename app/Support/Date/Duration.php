<?php

declare(strict_types=1);

namespace App\Support\Date;

/**
 * Dəqiqə ilə saxlanılan müddətlərin oxunaqlı formaya salınması.
 *
 * ÖNƏMLİ: bu yalnız GÖSTƏRİŞ qatıdır. Bazada və bütün hesablamalarda müddət
 * həmişə tam dəqiqə olaraq qalır (late_minutes, early_minutes, overtime_minutes,
 * worked_minutes və s.) - burada heç bir hesablama məntiqi dəyişmir.
 *
 * Qayda:
 *   0-59 dəqiqə   → "45 dəq"
 *   60+ dəqiqə    → "3.3 saat"   (198 dəq)
 *   1440+ dəqiqə  → "1.5 gün"    (2160 dəq)
 *
 * Onluq hissə maksimum 2 rəqəmdir və sondakı sıfırlar atılır: 120 → "2 saat".
 */
final class Duration
{
    public const MINUTES_IN_HOUR = 60;
    public const MINUTES_IN_DAY  = 1440;

    /** Uzun vahid adları. */
    private const UNITS_LONG = [
        'minute' => 'dəq',
        'hour'   => 'saat',
        'day'    => 'gün',
    ];

    /** Qısa (dar sütun / badge) vahid adları. */
    private const UNITS_SHORT = [
        'minute' => 'dq',
        'hour'   => 's',
        'day'    => 'g',
    ];

    /**
     * Əsas format: 198 → "3.3 saat".
     *
     * @param int|float|string|null $minutes Dəqiqə (bazadakı dəyər)
     * @param string                $empty   0 və ya boş dəyər üçün qaytarılacaq mətn
     */
    public static function human(mixed $minutes, string $empty = '0 dəq'): string
    {
        $total = self::normalize($minutes);

        if ($total <= 0) {
            return $empty;
        }

        return self::format($total, self::UNITS_LONG);
    }

    /**
     * Qısa format: 198 → "3.3 s". Dar sütunlar və badge-lər üçün.
     */
    public static function short(mixed $minutes, string $empty = '0 dq'): string
    {
        $total = self::normalize($minutes);

        if ($total <= 0) {
            return $empty;
        }

        return self::format($total, self::UNITS_SHORT);
    }

    /**
     * 0 olduqda boş sətir - sahənin ümumiyyətlə göstərilməməsi üçün.
     */
    public static function humanOrEmpty(mixed $minutes): string
    {
        return self::human($minutes, '');
    }

    /**
     * Dəqiq bölünmüş format: 95 → "1 saat 35 dəq".
     *
     * Onluq deyil, tam vahidlərlə göstərmək lazım olan yerlər üçün saxlanılır
     * (məs. iş vaxtı cəmi). Standart göstəriş üçün human() istifadə edilir.
     */
    public static function precise(mixed $minutes, string $empty = '0 dəq'): string
    {
        $total = self::normalize($minutes);

        if ($total <= 0) {
            return $empty;
        }

        $parts = [];

        $days = intdiv($total, self::MINUTES_IN_DAY);
        if ($days > 0) {
            $parts[] = $days . ' gün';
            $total  -= $days * self::MINUTES_IN_DAY;
        }

        $hours = intdiv($total, self::MINUTES_IN_HOUR);
        if ($hours > 0) {
            $parts[] = $hours . ' saat';
            $total  -= $hours * self::MINUTES_IN_HOUR;
        }

        if ($total > 0) {
            $parts[] = $total . ' dəq';
        }

        return implode(' ', $parts);
    }

    /**
     * Saat:dəqiqə formatı: 198 → "03:18". Tabel və cədvəl sütunları üçün.
     */
    public static function clock(mixed $minutes, string $empty = '00:00'): string
    {
        $total = self::normalize($minutes);

        if ($total <= 0) {
            return $empty;
        }

        return sprintf('%02d:%02d', intdiv($total, self::MINUTES_IN_HOUR), $total % self::MINUTES_IN_HOUR);
    }

    /**
     * Saat ədədi (onluq): 198 → 3.3. Excel/qrafik üçün - mətn deyil, rəqəm lazım olanda.
     */
    public static function hours(mixed $minutes, int $decimals = 2): float
    {
        return round(self::normalize($minutes) / self::MINUTES_IN_HOUR, $decimals);
    }

    /**
     * Vahidə görə format - ortaq hissə.
     *
     * @param array<string, string> $units
     */
    private static function format(int $total, array $units): string
    {
        if ($total < self::MINUTES_IN_HOUR) {
            return $total . ' ' . $units['minute'];
        }

        if ($total < self::MINUTES_IN_DAY) {
            return self::number($total / self::MINUTES_IN_HOUR) . ' ' . $units['hour'];
        }

        return self::number($total / self::MINUTES_IN_DAY) . ' ' . $units['day'];
    }

    /**
     * 3.30 → "3.3", 2.00 → "2", 2.25 → "2.25".
     */
    private static function number(float $value): string
    {
        $text = number_format(round($value, 2), 2, '.', '');

        return rtrim(rtrim($text, '0'), '.');
    }

    /**
     * Mənfi, boş və mətn dəyərləri təhlükəsiz şəkildə tam dəqiqəyə çevirir.
     */
    private static function normalize(mixed $minutes): int
    {
        if ($minutes === null || $minutes === '' || !is_numeric($minutes)) {
            return 0;
        }

        $total = (int) round((float) $minutes);

        return $total > 0 ? $total : 0;
    }
}
