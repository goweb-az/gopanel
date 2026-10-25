<?php

namespace App\Traits\Presentable\User;

/**
 * `User` (sayt istifadəçisi) modelinin göstəriş accessor-ları.
 *
 * Niyə: `Admin`-də `full_name` sütundur, `User`-də isə yalnız `name` var -
 * jurnal (`Activity::causer_name`) və bildirişlər iki modeli eyni adla
 * oxuya bilsin deyə. Törəmə layihə `surname` sütunu əlavə edəndə avtomatik
 * birləşir.
 *
 * `full_info` (ad | müştəri nömrəsi) starter-də yoxdur - `customer_number`
 * sütunu törəmə layihəyə aiddir.
 */
trait HasUserPresentation
{
    public function getFullNameAttribute(): string
    {
        return trim((string) $this->name . ' ' . (string) ($this->surname ?? ''));
    }
}
