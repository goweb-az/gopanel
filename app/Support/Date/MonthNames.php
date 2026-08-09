<?php

declare(strict_types=1);

namespace App\Support\Date;

use Carbon\Carbon;

/**
 * Azərbaycanca ay və həftə günü adlarının vahid mənbəyi.
 *
 * Bu adlar bir neçə bölmədə (vaxt cədvəli, tabel, istehsalat təqvimi, planlı
 * hesabatlar, exportlar) lazım olur - siyahı yalnız burada saxlanılır ki,
 * dəyişiklik hər yerə eyni anda təsir etsin.
 *
 * JS tərəfi üçün eyni məlumat `azLocale` qlobalındadır - biri dəyişəndə
 * digəri də yenilənməlidir.
 */
final class MonthNames
{
    /** @var array<int, string> */
    private const NAMES = [
        1  => 'Yanvar',
        2  => 'Fevral',
        3  => 'Mart',
        4  => 'Aprel',
        5  => 'May',
        6  => 'İyun',
        7  => 'İyul',
        8  => 'Avqust',
        9  => 'Sentyabr',
        10 => 'Oktyabr',
        11 => 'Noyabr',
        12 => 'Dekabr',
    ];

    /** @var array<int, string> ISO: 1=B.e ... 7=Bazar */
    private const WEEK_DAYS_SHORT = [
        1 => 'B.e',
        2 => 'Ç.a',
        3 => 'Ç',
        4 => 'C.a',
        5 => 'C',
        6 => 'Ş',
        7 => 'B',
    ];

    /**
     * @var array<int, string> ISO: 1=Baz.e ... 7=Bazar
     *
     * Orta uzunluq - qrafik oxları üçün: qısa variant oxunmur, tam variant
     * isə oxa sığmır.
     */
    private const WEEK_DAYS_MEDIUM = [
        1 => 'Baz.e',
        2 => 'Ç.ax',
        3 => 'Çər',
        4 => 'C.ax',
        5 => 'Cümə',
        6 => 'Şən',
        7 => 'Bazar',
    ];

    /** @var array<int, string> ISO: 1=B.e ... 7=Bazar */
    private const WEEK_DAYS_LONG = [
        1 => 'Bazar ertəsi',
        2 => 'Çərşənbə axşamı',
        3 => 'Çərşənbə',
        4 => 'Cümə axşamı',
        5 => 'Cümə',
        6 => 'Şənbə',
        7 => 'Bazar',
    ];

    /** @return array<int, string> 1-12 → ay adı */
    public static function all(): array
    {
        return self::NAMES;
    }

    /** Ay nömrəsinə görə ad (yanlış nömrədə boş sətir). */
    public static function label(int $month): string
    {
        return self::NAMES[$month] ?? '';
    }

    /** `2026-07` formatındakı dəyəri "İyul 2026" şəklinə salır. */
    public static function labelForPeriod(string $period): string
    {
        if (!preg_match('/^(\d{4})-(\d{2})$/', $period, $matches)) {
            return $period;
        }

        return self::label((int) $matches[2]) . ' ' . $matches[1];
    }

    /** Tarixdən "İyul 2026" etiketi. */
    public static function labelForDate(Carbon $date): string
    {
        return self::label((int) $date->format('n')) . ' ' . $date->format('Y');
    }

    /**
     * Rübün ayları: nömrə → ad.
     *
     * @return array<int, string> məs. rüb 1 → [1 => 'Yanvar', 2 => 'Fevral', 3 => 'Mart']
     */
    public static function quarterMonths(int $quarter): array
    {
        if ($quarter < 1 || $quarter > 4) {
            return [];
        }

        return array_slice(self::NAMES, ($quarter - 1) * 3, 3, true);
    }

    /**
     * Bütün rüblər: rüb nömrəsi → ayların siyahısı.
     *
     * @return array<int, array<int, string>>
     */
    public static function quarters(): array
    {
        return [
            1 => self::quarterMonths(1),
            2 => self::quarterMonths(2),
            3 => self::quarterMonths(3),
            4 => self::quarterMonths(4),
        ];
    }

    /**
     * Həftə günlərinin qısa adları.
     *
     * Sıra bazar ertəsindən başlayır. Açarsız (0-dan indeksli) massiv qaytarılır -
     * `foreach` ilə sütun başlıqlarında istifadə üçün.
     *
     * @return array<int, string>
     */
    public static function weekDayShortNames(): array
    {
        return array_values(self::WEEK_DAYS_SHORT);
    }

    /**
     * Həftə günlərinin qısa adları ISO açarları ilə (1=B.e … 7=Bazar).
     *
     * @return array<int, string>
     */
    public static function weekDaysShortIndexed(): array
    {
        return self::WEEK_DAYS_SHORT;
    }

    /**
     * Həftə günlərinin tam adları ISO açarları ilə (1=B.e … 7=Bazar).
     *
     * @return array<int, string>
     */
    public static function weekDaysLongIndexed(): array
    {
        return self::WEEK_DAYS_LONG;
    }

    /** ISO gün nömrəsinə görə qısa ad (yanlış nömrədə tire). */
    public static function weekDayShort(int $isoDay): string
    {
        return self::WEEK_DAYS_SHORT[$isoDay] ?? '-';
    }

    /** ISO gün nömrəsinə görə orta uzunluqda ad - qrafik oxları üçün. */
    public static function weekDayMedium(int $isoDay): string
    {
        return self::WEEK_DAYS_MEDIUM[$isoDay] ?? '-';
    }

    /** ISO gün nömrəsinə görə tam ad (yanlış nömrədə tire). */
    public static function weekDayLong(int $isoDay): string
    {
        return self::WEEK_DAYS_LONG[$isoDay] ?? '-';
    }
}
