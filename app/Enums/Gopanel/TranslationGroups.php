<?php

namespace App\Enums\Gopanel;

enum TranslationGroups: string
{
    case TITLE    = 'title';
    case CONTENT  = 'content';


    public function getLabel(): string
    {
        return match ($this) {
            self::TITLE => 'Başlıq',
            self::CONTENT => 'Məzmun',
        };
    }
}
