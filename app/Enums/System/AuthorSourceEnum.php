<?php

declare(strict_types=1);

namespace App\Enums\System;

/**
 * `record_authors.source` - sətrin hansı kanaldan yarandığı.
 *
 * Niyə lazımdır: «kim əlavə etdi» sualının cavabı həmişə insan deyil - sətir
 * idxaldan, xarici inteqrasiyadan və ya cron-dan yarana bilər. Bu hallarda
 * `admin_id`/`user_id` boş qalır və siyahıda mənbənin adı göstərilir
 * («-» əvəzinə «İdxal»).
 *
 * Konsol/idxal axını mənbəni açıq verir:
 * `RecordAuthorService::using(AuthorSourceEnum::IMPORT, fn () => ...)`.
 */
enum AuthorSourceEnum: string
{
    /** GoPanel - `gopanel` guard-ı doludur. */
    case PANEL = 'panel';

    /** Sayt istifadəçisi - `web`/`api` guard-ı doludur. */
    case CUSTOMER = 'customer';

    /** Xarici sistem (ERP, 1C, partnyor API-si). */
    case INTEGRATION = 'integration';

    /** Kütləvi idxal (Excel, feed, köçürmə command-i). */
    case IMPORT = 'import';

    /** Cron, queue, seeder - heç bir guard yoxdur. */
    case SYSTEM = 'system';

    public function label(): string
    {
        return match ($this) {
            self::PANEL       => 'Panel',
            self::CUSTOMER    => 'Müştəri',
            self::INTEGRATION => 'İnteqrasiya',
            self::IMPORT      => 'İdxal',
            self::SYSTEM      => 'Sistem',
        };
    }

    /**
     * Admin/istifadəçi bağlanmayanda siyahıda görünən ad.
     */
    public function fallbackName(): string
    {
        return match ($this) {
            self::INTEGRATION => 'İnteqrasiya',
            self::IMPORT      => 'İdxal',
            default           => 'Sistem',
        };
    }
}
