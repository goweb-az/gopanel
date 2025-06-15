<?php

namespace App\Enums\Gopanel;

use App\Models\User\User;
use App\Models\Translations\Translation;

enum ModelList: string
{
    case users                  = User::class;
    case translation            = Translation::class;

    public static function get($const): ModelList|string
    {
        foreach (self::cases() as $enum) {
            if ($enum->name == $const) {
                return $enum;
            }
        }
        return $const;
    }
}
