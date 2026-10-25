<?php

declare(strict_types=1);

namespace App\Support\Date;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Siyahı səhifələrindəki «Tarix aralığı» süzgəcinin TƏK mənbəyi.
 *
 * Süzgəc dəyəri `flatpickr` range rejimindən `Y-m-d+-+Y-m-d` formatında gəlir.
 *
 * ⚠️ Default aralıq YOXDUR: süzgəc seçilməyibsə heç bir tarix şərti qoyulmur.
 * Törəmə layihədə default «son 30 gün» idi və bu, gələcək tarixə yazılan qeydi
 * (məzuniyyət, kampaniya) siyahıda gizlədirdi - qeyd bazada yaranırdı, amma
 * `start_date <= bu gün` şərtinə düşmədiyi üçün görünmürdü. Böyük cədvəldə
 * default aralıq lazımdırsa onu çağıran yer açıq şəkildə verir.
 *
 * Dashboard «bu dövr / əvvəlki dövr» müqayisəsi üçün bu yox, `PeriodRange` işlədilir.
 */
final class FilterDateRange
{
    /** flatpickr `onChange`-in yazdığı ayırıcı - JS tərəfi ilə eyni olmalıdır */
    public const SEPARATOR = '+-+';

    /**
     * Süzgəc dəyərini `[from, to]`-ya çevirir.
     * Dəyər yoxdursa və ya formatı pozulubsa `[null, null]` = məhdudiyyət yoxdur.
     *
     * @return array{0: ?string, 1: ?string}
     */
    public static function parse(?string $value): array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return [null, null];
        }

        $parts = explode(self::SEPARATOR, $value);

        if (count($parts) !== 2) {
            return [null, null];
        }

        try {
            return [
                Carbon::parse(trim($parts[0]))->format('Y-m-d'),
                Carbon::parse(trim($parts[1]))->format('Y-m-d'),
            ];
        } catch (\Throwable $e) {
            return [null, null];
        }
    }

    /**
     * KƏSİŞMƏ semantikası: dövr seçilmiş aralıqla toxunursa sətir görünür.
     * Aralığı bütövlükdə əhatə edən (əvvəl başlayıb sonra bitən) dövr də daxildir.
     *
     * Aralıq boşdursa sorğuya toxunulmur - bütün sətirlər qalır.
     */
    public static function applyOverlap(
        Builder $query,
        ?string $from,
        ?string $to,
        string $startColumn = 'start_date',
        string $endColumn = 'end_date'
    ): void {
        if (empty($from) || empty($to)) {
            return;
        }

        $query->where($startColumn, '<=', $to)->where($endColumn, '>=', $from);
    }

    /** Export başlıqları üçün «01.09.2026 - 30.09.2026»; aralıq yoxdursa boş sətir. */
    public static function label(?string $from, ?string $to): string
    {
        if (empty($from) || empty($to)) {
            return '';
        }

        return Carbon::parse($from)->format('d.m.Y') . ' - ' . Carbon::parse($to)->format('d.m.Y');
    }
}
