<?php

namespace App\Http\Controllers\Site\Seo;

use App\Http\Controllers\Site\SiteController;
use App\Models\Seo\LlmsTxt;
use App\Models\Seo\SeoAnalytics;
use App\Services\Seo\Markdown\MarkdownPageService;
use App\Services\Seo\MachineFilesService;
use Illuminate\Http\Request;
use Throwable;

/*
 * Mətn formatlı maşın faylları:
 *
 *   /robots.txt     → seo_analytics.robots_txt (boşdursa default mətn)
 *   /ai.txt         → seo_analytics.ai_txt
 *   /ads.txt        → seo_analytics.ads_txt   (boşdursa 404)
 *   /llms.txt       → llms_txts.content       (GoPanel → SEO → LLMs.txt)
 *   /llms-full.txt  → bütün Markdown sənədləri (MarkdownPageService::full())
 *
 * Hamısı `text/plain; charset=UTF-8` ilə verilir - charset olmadan bəzi
 * botlar Azərbaycan hərflərini ISO-8859-1 kimi oxuyur.
 *
 * Əvvəl `seo_analytics` sətri olmayanda (təzə quraşdırma) bu controller
 * `null->robots_txt` ilə 500 verirdi - indi hər sahə null-a davamlıdır.
 */

class TxtController extends SiteController
{
    /** @var SeoAnalytics|null Geriyə uyğunluq üçün saxlanılır */
    public $seoAnalytics;

    public function __construct()
    {
        parent::__construct();
        $this->seoAnalytics = app(MachineFilesService::class)->analytics();
    }

    public function robots(Request $request)
    {
        $content = trim((string) ($this->seoAnalytics?->robots_txt ?? ''));

        return $this->plain($content !== '' ? $content . "\n" : app(MachineFilesService::class)->defaultRobots());
    }

    public function ai(Request $request)
    {
        return $this->plain($this->seoAnalytics?->ai_txt ?? '');
    }

    /**
     * ads.txt yazılmayıbsa 404 - boş fayl reklam şəbəkələri tərəfindən «heç bir
     * satıcı icazəli deyil» kimi oxuna bilər; yazılmayıbsa fayl olmamalıdır.
     */
    public function ads(Request $request)
    {
        $content = trim((string) ($this->seoAnalytics?->ads_txt ?? ''));

        if ($content === '') {
            abort(404);
        }

        return $this->plain($content . "\n");
    }

    public function llms(Request $request)
    {
        try {
            $content = LlmsTxt::getCached()?->content;
        } catch (Throwable) {
            $content = null;
        }

        return $this->plain($content ?? '');
    }

    /**
     * `config('seo.markdown.llms_full.enabled')` false-dursa 404.
     */
    public function llmsFull(Request $request, MarkdownPageService $markdown)
    {
        if (!config('seo.markdown.llms_full.enabled', true)) {
            abort(404);
        }

        return $this->plain($markdown->full())
            // Eyni məzmun HTML səhifələrdədir - indeks yalnız onlara düşsün
            ->header('X-Robots-Tag', 'noindex, follow');
    }

    private function plain(?string $content)
    {
        return response((string) $content, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
