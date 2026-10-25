<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Services\Cache\CacheService;
use App\Support\Cache\CacheKey;

/**
 * `/{dil}/sitemap.xml` üçün URL siyahısını qurur.
 *
 * NİYƏ servis: əvvəllər sorğu controller-də, filtr və döngü isə blade-də idi -
 * yeni model əlavə etmək üçün həm controller-ə, həm view-a toxunmaq lazım
 * gəlirdi və blade-də `is_null($blog->single_url)` kimi məntiq yığılırdı.
 * İndi siyahı burada massiv kimi qurulur, blade yalnız çap edir.
 *
 * Mənbələr (`PublicContentService` - Markdown və RSS ilə eyni qaydalar):
 *   - ana səhifə `/{dil}`;
 *   - route-u qeydiyyatdan keçmiş aktiv menyu səhifələri;
 *   - `config/seo/content.php`-dəki modellərin publik qeydləri.
 *
 * Yeni bölmə üçün kod yazılmır - modeli `config/seo/content.php`-ə əlavə etmək
 * kifayətdir. Nəticə keşlənir; açar bütün keş qruplarının versiyasını daşıyır,
 * yəni paneldə məzmun dəyişəndə sitemap da yenilənir.
 */
class SitemapService
{
    public function __construct(private readonly PublicContentService $content)
    {
    }

    /**
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    public function urls(string $locale): array
    {
        return CacheService::remember(
            CacheKey::seo('sitemap', ['l' => $locale, 'fp' => $this->content->fingerprint()]),
            fn (): array => $this->build($locale),
        );
    }

    /**
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    private function build(string $locale): array
    {
        $today = date('Y-m-d');
        $menu  = (array) config('seo.content.menu', []);

        $urls = [[
            'loc'        => url($locale),
            'lastmod'    => $today,
            'changefreq' => 'daily',
            'priority'   => '1.00',
        ]];

        foreach ($this->content->menuPages($locale) as $page) {
            $urls[] = [
                'loc'        => $this->content->menuUrl($page, $locale),
                'lastmod'    => $page->updated_at?->format('Y-m-d') ?? $today,
                'changefreq' => (string) ($menu['changefreq'] ?? 'weekly'),
                'priority'   => (string) ($menu['priority'] ?? '0.80'),
            ];
        }

        foreach ($this->content->models() as $class => $options) {
            foreach ($this->content->items($class) as $item) {
                if (!$this->content->isRoutable($item)) {
                    continue;
                }

                // Həmin dildə slug tərcüməsi yoxdursa səhifə də açılmır
                $loc = $this->content->url($item, $locale);

                if ($loc === null) {
                    continue;
                }

                $urls[] = [
                    'loc'        => $loc,
                    'lastmod'    => $this->content->lastModified($item),
                    'changefreq' => (string) ($options['changefreq'] ?? 'weekly'),
                    'priority'   => (string) ($options['priority'] ?? '0.70'),
                ];
            }
        }

        return $urls;
    }
}
