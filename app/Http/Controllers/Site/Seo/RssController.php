<?php

namespace App\Http\Controllers\Site\Seo;

use App\Http\Controllers\Site\SiteController;
use App\Models\Geography\Language;
use App\Services\Seo\RssService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * RSS çıxışları:
 *
 *   /rss-index.opml   → OPML: bütün dillərin feed linkləri
 *   /{dil}/rss.xml    → həmin dilin RSS 2.0 feed-i (RssService)
 */
class RssController extends SiteController
{
    public function __construct()
    {
        parent::__construct();
        $this->setLocale();
    }

    private function setLocale(): void
    {
        $segment = request()->segment(1);

        if ($segment && preg_match('/^[a-z]{2}$/', $segment)) {
            app()->setLocale($segment);
        }
    }

    // OPML feed index – bütün dillərin RSS linklərini sıralayır
    public function index(Request $request)
    {
        return $this->xml('site.feed.rss-index-opml', [
            'feed_languages' => Language::getCachedAll()
                ->filter(fn ($language) => Route::has("site.{$language->code}.rss.single"))
                ->values(),
        ]);
    }

    // Dil bazlı RSS 2.0
    public function single(?string $locale = null)
    {
        $locale = $locale ?: app()->getLocale();
        app()->setLocale($locale);

        $service = app(RssService::class);

        return $this->xml('site.feed.rss-single', [
            'channel' => $service->channel($locale, route("site.{$locale}.rss.single")),
            'items'   => $service->items($locale),
        ]);
    }

    private function xml(string $view, array $data)
    {
        return response()
            ->view($view, $data)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
