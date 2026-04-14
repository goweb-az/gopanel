<?php

namespace App\Enums\Common\Menu;

enum MenuPositionEnum: string
{
    case Header             = 'header';
    case Footer             = 'footer';
    case FooterTruested     = 'footer_truested';
    case FooterCommunity    = 'footer_community';
    case Other              = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Header          => __('Başlıq'),
            self::Footer          => __('Altbilgi'),
            self::FooterTruested  => __('Etibarlı tərəfdaşlar'),
            self::FooterCommunity => __('İcma linkləri'),
            self::Other           => __('Digər'),
        };
    }

    public function className(): string
    {
        return match ($this) {
            self::Header          => 'success',
            self::Footer          => 'dark',
            self::FooterTruested  => 'info',
            self::FooterCommunity => 'warning',
            self::Other           => 'deafult',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
