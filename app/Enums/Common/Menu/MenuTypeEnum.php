<?php

namespace App\Enums\Common\Menu;

enum MenuTypeEnum: string
{
    case Route = 'route';
    case Static = 'static';
    case Functional = 'functional';
    case Dynamic = 'dynamic';

    public function label(): string
    {
        return match ($this) {
            self::Route => __('Route'),
            self::Static => __('Statik'),
            self::Functional => __('Funksional'),
            self::Dynamic => __('Dinamik'),
        };
    }

    public function className(): string
    {
        return match ($this) {
            self::Route => 'primary',
            self::Static => 'secondary',
            self::Functional => 'info',
            self::Dynamic => 'warning',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
