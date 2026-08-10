<?php

declare(strict_types=1);

namespace App\Support\Gopanel;

/**
 * GoPanel bölmələrinin yuxarısındakı info kartı - tək qəlib.
 *
 * Kart üç hissədən ibarətdir: böyük dəyər, köhnə dövrlə müqayisə nişanı və
 * kiçik sparkline. Blade-də heç bir hesablama olmasın deyə faiz, ox istiqaməti
 * və rəng burada hesablanır.
 */
final class StatCard
{
    /**
     * @param  array{
     *     label: string,
     *     value: string|int|float,
     *     icon?: string,
     *     color?: string,
     *     hint?: string|null,
     *     current?: float|int|null,
     *     previous?: float|int|null,
     *     series?: array<int, int|float>,
     *     money?: bool
     * }  $args
     * @return array<string, mixed>
     */
    public static function make(array $args): array
    {
        $current  = $args['current'] ?? null;
        $previous = $args['previous'] ?? null;
        $money    = (bool) ($args['money'] ?? false);

        return [
            'label' => (string) $args['label'],
            'value' => self::formatValue($args['value'], $money),
            'icon'  => (string) ($args['icon'] ?? 'fas fa-chart-bar'),
            'color' => (string) ($args['color'] ?? 'primary'),
            'hint'  => $args['hint'] ?? null,
            'trend' => self::trend($current, $previous, $money),
            'spark' => array_values(array_map('floatval', $args['series'] ?? [])),
            // Kart üzərinə klik - uyğun bölməyə (filtrlənmiş) keçid
            'url'   => $args['url'] ?? null,
        ];
    }

    /**
     * Bir neçə kartı birdən qurur.
     *
     * @param  array<int, array<string, mixed>>  $cards
     * @return array<int, array<string, mixed>>
     */
    public static function collection(array $cards): array
    {
        return array_map(static fn (array $card) => self::make($card), $cards);
    }

    /**
     * Köhnə dövrlə müqayisə: faiz, istiqamət, rəng və izah mətni.
     *
     * @return array<string, mixed>|null
     */
    private static function trend(float|int|null $current, float|int|null $previous, bool $money): ?array
    {
        if ($current === null || $previous === null) {
            return null;
        }

        $current  = (float) $current;
        $previous = (float) $previous;
        $note     = 'əvvəlki dövr: ' . self::formatValue($previous, $money);

        if ($previous == 0.0 && $current == 0.0) {
            return [
                'direction' => 'flat',
                'percent'   => '0%',
                'class'     => 'secondary',
                'icon'      => 'fas fa-minus',
                'note'      => $note,
            ];
        }

        if ($previous == 0.0) {
            return [
                'direction' => 'up',
                'percent'   => 'yeni',
                'class'     => 'success',
                'icon'      => 'fas fa-arrow-up',
                'note'      => $note,
            ];
        }

        $percent = round((($current - $previous) / abs($previous)) * 100, 1);

        if (abs($percent) < 0.05) {
            return [
                'direction' => 'flat',
                'percent'   => '0%',
                'class'     => 'secondary',
                'icon'      => 'fas fa-minus',
                'note'      => $note,
            ];
        }

        $up = $percent > 0;

        return [
            'direction' => $up ? 'up' : 'down',
            'percent'   => ($up ? '+' : '') . self::trimNumber($percent) . '%',
            'class'     => $up ? 'success' : 'danger',
            'icon'      => $up ? 'fas fa-arrow-up' : 'fas fa-arrow-down',
            'note'      => $note,
        ];
    }

    private static function formatValue(string|int|float $value, bool $money): string
    {
        if (is_string($value)) {
            return $value;
        }

        return $money
            ? number_format((float) $value, 2) . ' ₼'
            : number_format((float) $value);
    }

    private static function trimNumber(float $number): string
    {
        return rtrim(rtrim(number_format($number, 1, '.', ''), '0'), '.');
    }
}
