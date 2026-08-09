<?php

declare(strict_types=1);

namespace App\Support\Gopanel;

/**
 * Kənar ikon dəstlərini GoPanel-in Font Awesome 5 dəstinə çevirir.
 *
 * NİYƏ LAZIMDIR: sayt/mobil şablonlar çox vaxt Phosphor (`ph-duotone ph-*`),
 * Tabler (`ti ti-*`) və ya Bootstrap Icons işlədir; GoPanel isə **Font Awesome 5**
 * yükləyir. Ortaq tərif fayllarından (məs. `config/gopanel/sidebar_menu_list.php`,
 * settings qrupları) gələn ikon adı GoPanel-də olduğu kimi çap edilsə boş
 * kvadrat görünür.
 *
 * Bu xəritə ikon adındakı açar sözə görə FA5 qarşılığını qaytarır - yeni qrup
 * əlavə olunanda burada bir sətir kifayətdir, tapılmasa təhlükəsiz default var.
 *
 * FA5 sinifləri onsuz da olduğu kimi ötürülür - ikiqat çevirmə olmur.
 */
final class PanelIconMap
{
    /** @var array<string,string> ikon adında axtarılan açar söz => FA5 sinfi */
    private const MAP = [
        'gear'           => 'fas fa-cogs',
        'cog'            => 'fas fa-cogs',
        'qr-code'        => 'fas fa-qrcode',
        'qrcode'         => 'fas fa-qrcode',
        'clock'          => 'fas fa-user-clock',
        'timer'          => 'fas fa-stopwatch',
        'device-mobile'  => 'fas fa-mobile-alt',
        'mobile'         => 'fas fa-mobile-alt',
        'bell'           => 'fas fa-bell',
        'user'           => 'fas fa-users',
        'buildings'      => 'fas fa-building',
        'building'       => 'fas fa-building',
        'credit-card'    => 'fas fa-credit-card',
        'money'          => 'fas fa-coins',
        'shield'         => 'fas fa-shield-alt',
        'calendar'       => 'fas fa-calendar-alt',
        'chart'          => 'fas fa-chart-line',
        'file'           => 'fas fa-file-alt',
        'lock'           => 'fas fa-lock',
        'map'            => 'fas fa-map-marker-alt',
        'signature'      => 'fas fa-signature',
        'bank'           => 'fas fa-university',
        'address-book'   => 'fas fa-address-book',
        'identification' => 'fas fa-id-card',
    ];

    private const FALLBACK = 'fas fa-sliders-h';

    public static function fontAwesome(?string $icon): string
    {
        $icon = trim((string) $icon);

        if ($icon === '') {
            return self::FALLBACK;
        }

        // Onsuz da FA sinfidirsə toxunmuruq
        if (str_starts_with($icon, 'fa') || str_contains($icon, ' fa-')) {
            return $icon;
        }

        foreach (self::MAP as $needle => $faClass) {
            if (str_contains($icon, $needle)) {
                return $faClass;
            }
        }

        return self::FALLBACK;
    }

    /**
     * Tərif massivindəki `icon` açarını yerində çevirir.
     *
     * @param  array<int,array<string,mixed>>  $groups
     * @return array<int,array<string,mixed>>
     */
    public static function convertGroups(array $groups): array
    {
        return array_map(static function (array $group): array {
            $group['icon'] = self::fontAwesome($group['icon'] ?? null);

            return $group;
        }, $groups);
    }
}
