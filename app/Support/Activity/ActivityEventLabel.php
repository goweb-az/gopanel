<?php

declare(strict_types=1);

namespace App\Support\Activity;

use Illuminate\Support\Str;

/**
 * Activity log hadisə açarının insan dilindəki qarşılığı və rəngi - TƏK mənbə.
 *
 * `activity_log.event` sütunu texniki `snake_case` açar saxlayır
 * (`status_changed`) və filtr sorğusu da həmin açarla gedir. İstifadəçiyə isə
 * texniki açar göstərilmir: cədvəl, süzgəc və detal pəncərəsi eyni siyahıdan -
 * `config/activitylog.php` → `event_names` - oxumalıdır.
 *
 * Niyə: etiket bir yerdə config-dən (süzgəc), başqa yerdə `match`-dən (cədvəl)
 * gələndə `match` yalnız `created/updated/deleted` tanıyır, qalan hadisələr
 * cədvəldə xam açar kimi görünür. Törəmə layihə yeni hadisəni config-ə yazır -
 * kod dəyişmir.
 */
final class ActivityEventLabel
{
    /**
     * Rəng ailələri (Bootstrap sinif adı). Siyahıda olmayan hadisə neytral
     * (`secondary`) qalır. Yalnız ümumi hadisələr - layihəyə xas hadisənin
     * rəngi `config/activitylog.php` → `event_colors` ilə əlavə olunur.
     *
     * @var array<string, list<string>>
     */
    private const COLORS = [
        'success' => [
            'created', 'restored', 'approved', 'completed', 'verified',
            'logged_in', 'unblocked', 'imported',
        ],
        'danger' => [
            'deleted', 'force_deleted', 'rejected', 'cancelled', 'failed',
            'blocked', 'login_failed', 'unauthorized',
        ],
        'warning' => [
            'updated', 'status_changed', 'sorted', 'moved',
            'translation_updated', 'role_changed', 'permissions_changed',
            'password_changed', 'password_reset', 'logged_out',
        ],
    ];

    /**
     * Süzgəc siyahısı: açar texniki, dəyər göstərilən etiketdir.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return (array) config('activitylog.event_names', []);
    }

    /**
     * Naməlum hadisə də oxunaqlı qalır: `Str::headline()` xam `snake_case`-i
     * heç olmasa sözlərə ayırır.
     */
    public static function for(?string $event): string
    {
        $event = trim((string) $event);

        if ($event === '') {
            return '-';
        }

        return self::all()[$event] ?? Str::headline($event);
    }

    /** `success` / `danger` / `warning` / `secondary`. */
    public static function color(?string $event): string
    {
        $event = trim((string) $event);

        $custom = (array) config('activitylog.event_colors', []);

        if (isset($custom[$event]) && is_string($custom[$event])) {
            return $custom[$event];
        }

        foreach (self::COLORS as $color => $events) {
            if (in_array($event, $events, true)) {
                return $color;
            }
        }

        return 'secondary';
    }
}
