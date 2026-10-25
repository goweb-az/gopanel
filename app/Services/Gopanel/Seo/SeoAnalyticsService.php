<?php

declare(strict_types=1);

namespace App\Services\Gopanel\Seo;

use App\DTOs\Gopanel\ContentPayload;
use App\Models\Seo\SeoAnalytics;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Cache;

/**
 * «SEO və analitika» sətrinin (head/body/footer kodları, robots.txt, ai.txt,
 * ads.txt) saxlanması.
 *
 * NİYƏ servis: sayt bu sətri `SeoAnalytics::getCached()` ilə `rememberForever`
 * (`seo_analytics` açarı) oxuyur. Əvvəl controller yalnız bazaya yazırdı -
 * panel yeni mətni göstərir, `/robots.txt` və head kodları isə keş əl ilə
 * təmizlənənə qədər köhnəsini verirdi, heç bir xəta olmadan. Yazma və keşin
 * silinməsi bir yerdə saxlanılır ki, ayrılmasın (`LlmsTxtService` ilə eyni qəlib).
 */
class SeoAnalyticsService
{
    public function __construct(private readonly BaseRepository $repository)
    {
    }

    public function save(SeoAnalytics $item, ContentPayload $payload): SeoAnalytics
    {
        /** @var SeoAnalytics $item */
        $item = $this->repository->save($item, $payload->attributes);

        Cache::forget('seo_analytics');

        return $item;
    }
}
