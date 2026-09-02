<?php

declare(strict_types=1);

namespace App\Queries\Gopanel\Contact;

use App\Models\Contact\Social;
use Illuminate\Database\Eloquent\Collection;

/**
 * Sosial şəbəkə linkləri (panel siyahısı).
 *
 * Sıralama burada yazılmır: `Social` modelində `sort_order` üzrə qlobal scope
 * var. Sorğunun yeri yenə də Query sinifidir ki, sabah filtr (aktiv/deaktiv)
 * lazım olanda controller-ə yox, bura əlavə olunsun.
 */
class SocialQuery
{
    public function all(): Collection
    {
        return Social::query()->get();
    }
}
