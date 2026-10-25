<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Models\Seo\SeoAnalytics;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Saytın maşın üçün fayllarının (robots.txt, ai.txt, ads.txt, llms.txt,
 * llms-full.txt, sitemap, RSS, Markdown indeksi) siyahısı.
 *
 * NİYƏ: bu fayllar brauzerdə açılmır və səhv olanda heç kim görmür - admin
 * çox vaxt onların mövcudluğundan belə xəbərsizdir. Panel (SEO və analitika
 * səhifəsi) siyahını buradan alıb hazır keçidlər kimi göstərir; yeni maşın
 * faylı əlavə olunanda yalnız bu siyahı yenilənir.
 *
 * Yalnız qeydiyyatdan keçmiş route-lar qaytarılır - route silinsə panel
 * 404-ə keçid göstərmir.
 */
class MachineFilesService
{
    /**
     * @return array<int, array{title: string, url: string, description: string, source: string}>
     */
    public function links(): array
    {
        $files = [
            ['robots.txt', 'robots.txt', 'Axtarış botları üçün icazələr və sitemap ünvanı.', 'Aşağıdakı «Robots txt» sahəsi'],
            ['ai.txt', 'ai.txt', 'AI botları üçün saytın qısa təsviri və maşın resursları.', 'Aşağıdakı «Ai txt» sahəsi'],
            ['ads.txt', 'ads.txt', 'Reklam satıcılarının siyahısı (AdSense və s.). Boşdursa 404 verir.', 'Aşağıdakı «Ads txt» sahəsi'],
            ['llms.txt', 'llms.txt', 'LLM-lər üçün saytın Markdown təqdimatı.', 'SEO → LLMs.txt'],
            ['llms-full.txt', 'llms-full.txt', 'Bütün publik səhifələrin Markdown mətni bir faylda.', 'Avtomatik (config/seo/markdown.php)'],
            ['markdown.index', 'markdown-index.md', 'Hər səhifənin `.md` variantının siyahısı.', 'Avtomatik'],
            ['sitemap.index', 'sitemap.xml', 'Sitemap indeksi - hər dilin öz sitemap-ı.', 'Avtomatik (config/seo/content.php)'],
            ['site.rss.index', 'rss-index.opml', 'Bütün dillərin RSS feed-ləri (OPML).', 'Avtomatik (config/seo/content.php)'],
        ];

        $links = [];

        foreach ($files as [$route, $title, $description, $source]) {
            if (!Route::has($route)) {
                continue;
            }

            $links[] = [
                'title'       => $title,
                'url'         => route($route),
                'description' => $description,
                'source'      => $source,
            ];
        }

        foreach (app(PublicContentService::class)->locales() as $locale) {
            foreach (['sitemap' => 'sitemap.xml', 'rss' => 'rss.xml'] as $type => $file) {
                $name = "site.{$locale}.{$type}.single";

                if (Route::has($name)) {
                    $links[] = [
                        'title'       => "{$locale}/{$file}",
                        'url'         => route($name),
                        'description' => strtoupper($locale) . ' dili üçün ' . ($type === 'rss' ? 'RSS feed' : 'sitemap') . '.',
                        'source'      => 'Avtomatik',
                    ];
                }
            }
        }

        return $links;
    }

    /**
     * Default robots.txt - paneldə yazılmayıbsa sayt boş fayl verməsin.
     * Seeder də eyni mətni yazır (`SeoMachineFilesSeeder`).
     */
    public function defaultRobots(): string
    {
        return implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /gopanel',
            '',
            'Sitemap: ' . url('/sitemap.xml'),
            '',
        ]);
    }

    /** `seo_analytics` sətri (cədvəl yoxdursa və ya boşdursa `null`). */
    public function analytics(): ?SeoAnalytics
    {
        try {
            $item = SeoAnalytics::getCached();

            return $item instanceof SeoAnalytics ? $item : null;
        } catch (Throwable) {
            return null;
        }
    }
}
