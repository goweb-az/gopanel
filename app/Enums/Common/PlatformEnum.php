<?php

declare(strict_types=1);

namespace App\Enums\Common;

/**
 * Məzmunun hansı platformada göstərildiyi (`sliders.platform`, `banners.platform`, `faqs.platform`...).
 *
 * Niyə enum: eyni üç dəyər bir neçə layihədə sərbəst sətir kimi yazılırdı
 * (`'web'`, `'website'`, `'site'`) və API filtri bir cədvəldə işləyib digərində
 * boş qaytarırdı. `BOTH` ayrıca dəyərdir - «hər ikisi» üçün iki sətir saxlanmır.
 *
 * Sorğu süzgəci: `whereIn('platform', PlatformEnum::visibleOn(PlatformEnum::MOBILE))`.
 */
enum PlatformEnum: string
{
    case WEBSITE = 'website';
    case MOBILE  = 'mobile';
    case BOTH    = 'both';

    public function label(): string
    {
        return match ($this) {
            self::WEBSITE => 'Sayt',
            self::MOBILE  => 'Mobil tətbiq',
            self::BOTH    => 'Hər ikisi',
        };
    }

    /** Bootstrap badge rəngi (panel siyahıları). */
    public function color(): string
    {
        return match ($this) {
            self::WEBSITE => 'primary',
            self::MOBILE  => 'info',
            self::BOTH    => 'secondary',
        };
    }

    /** Boxicons sinfi. */
    public function icon(): string
    {
        return match ($this) {
            self::WEBSITE => 'bx bx-globe',
            self::MOBILE  => 'bx bx-mobile-alt',
            self::BOTH    => 'bx bx-layer',
        };
    }

    /**
     * Bu platformada göstərilən dəyərlər - `BOTH` həmişə daxildir.
     *
     * @return list<string>
     */
    public static function visibleOn(self $platform): array
    {
        return $platform === self::BOTH
            ? self::values()
            : [$platform->value, self::BOTH->value];
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<string, string> value => label (select üçün). */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }
}
