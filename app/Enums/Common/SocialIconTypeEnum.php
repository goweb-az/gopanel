<?php

namespace App\Enums\Common;

enum SocialIconTypeEnum: string
{
    case Svg = 'svg';
    case Image = 'image';
    case Font = 'font';
    case String = 'string';

    public function label(): string
    {
        return match ($this) {
            self::Svg => __('SVG'),
            self::Image => __('Şəkil'),
            self::Font => __('Font İkon'),
            self::String => __('Yazı'),
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
