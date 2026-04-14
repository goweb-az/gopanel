<?php

namespace App\Enums\Gopanel\Seo;

/*
 * @var RedirectMatchTypeEnum $this
 * Sayt yönləndirmələrində mənbə uyğunluq tiplərini təyin edir.
 */

enum RedirectMatchTypeEnum: string
{
    case EXACT    = 'exact';
    case PREFIX   = 'prefix';
    case CONTAINS = 'contains';
    case REGEX    = 'regex';

    /**
     * Ekranda göstəriləcək etiket
     */
    public function label(): string
    {
        return match ($this) {
            self::EXACT    => 'Tam uyğunluq',
            self::PREFIX   => 'Başlanğıcla uyğunluq',
            self::CONTAINS => 'Mətnin içində',
            self::REGEX    => 'Regex nümunəsi',
        };
    }

    /**
     * Bootstrap className (admin paneldə rənglənmə üçün)
     */
    public function className(): string
    {
        return match ($this) {
            self::EXACT    => 'primary',
            self::PREFIX   => 'info',
            self::CONTAINS => 'warning',
            self::REGEX    => 'danger',
        };
    }

    /**
     * Enum value-larını array olaraq qaytarır.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
