<?php

declare(strict_types=1);

namespace App\Enums\System;

/**
 * `record_authors.end_reason` - rolun niyə bitdiyi.
 *
 * Niyə saxlanılır: qeyd silinmir, ona görə «Orxan S. 14:20 - 17:40 arası
 * işləyib» sətrinin yanında bitmə səbəbi də göstərilir - «özü buraxdı» ilə
 * «başqası əlindən aldı» fərqli hadisələrdir.
 */
enum AuthorEndReasonEnum: string
{
    /** Admin işi özü buraxdı. */
    case RELEASED = 'released';

    /** Başqa admin üzərinə götürdü. */
    case REASSIGNED = 'reassigned';

    /** Təyinat köhnəlmişdi (TTL keçib) və başqası götürdü. */
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::RELEASED   => 'Buraxıldı',
            self::REASSIGNED => 'Üzərinə götürüldü',
            self::EXPIRED    => 'Vaxtı keçdi',
        };
    }
}
