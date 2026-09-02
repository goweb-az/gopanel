<?php

declare(strict_types=1);

namespace App\Queries\Gopanel\Site;

use App\Models\Site\Service;
use Illuminate\Database\Eloquent\Collection;

/**
 * Xidmətlər siyahısı (panel).
 *
 * `sort_order` üzrə sıralanır - panel siyahısı sürüşdürməklə düzülür və
 * saytdakı ardıcıllıq da elə buradan gəlir.
 */
class ServiceQuery
{
    public function ordered(): Collection
    {
        return Service::query()->orderBy('sort_order')->get();
    }
}
