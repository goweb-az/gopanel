<?php

declare(strict_types=1);

namespace App\Enums\System;

use App\Services\Activity\LogService;

/**
 * Starter-in fayl jurnalı kanalları - `config/logging.php`-dəki xüsusi kanalların güzgüsü.
 *
 * Niyə enum: kanal adı kodda sətir kimi yazılanda hərf səhvi (`'gopanell'`)
 * səssizcə default kanala düşür və log «itir». Enum IDE-də tamamlanır və
 * səhv ad kompilyasiyada görünür.
 *
 * `LogService`-in string kanalları OLDUĞU KİMİ işləyir - bu enum əlavə yoldur,
 * əvəz deyil: `new LogService('sms')` ilə `LogChannelEnum::SMS->log()` eynidir.
 *
 * Törəmə layihədə yeni kanal: `config/logging.php`-yə bənd + bura case.
 * Layihəyə xas kanallar (orders, payments...) starter-ə əlavə edilmir.
 */
enum LogChannelEnum: string
{
    case SYSTEM_ERRORS = 'system-errors';
    case GOPANEL       = 'gopanel';
    case GOPANEL_AUTH  = 'gopanel-auth';
    case MAIL          = 'mail';
    case SMS           = 'sms';
    case EXPORT        = 'export';
    case TRANSACTIONS  = 'transactions';
    case CRON          = 'cron';

    public function label(): string
    {
        return match ($this) {
            self::SYSTEM_ERRORS => 'Sistem xətaları',
            self::GOPANEL       => 'GoPanel',
            self::GOPANEL_AUTH  => 'GoPanel girişi',
            self::MAIL          => 'Email',
            self::SMS           => 'SMS',
            self::EXPORT        => 'Export',
            self::TRANSACTIONS  => 'Tranzaksiyalar',
            self::CRON          => 'Cron (planlı işlər)',
        };
    }

    /** Kanala bağlanmış hazır `LogService` nüsxəsi. */
    public function log(bool $saveDatabase = true, bool $withDetails = false): LogService
    {
        return LogService::channel($this->value, $saveDatabase, $withDetails);
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
}
