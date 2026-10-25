<?php

namespace App\Http\Controllers\Site\Seo;

use App\Http\Controllers\Site\SiteController;
use App\Models\Geography\Language;
use App\Services\Seo\SitemapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Sitemap çıxışları:
 *
 *   /sitemap.xml          → indeks: hər dilin öz sitemap-ına keçid
 *   /{dil}/sitemap.xml    → həmin dilin bütün URL-ləri (SitemapService)
 *
 * Ünvanlar dil qrupundan kənarda, `routes/web.php`-də aktiv dillər üzrə qeyd
 * olunub; route `->defaults('locale', ...)` ilə dili özü ötürür. Siyahı
 * servisdə qurulur - blade yalnız çap edir.
 */
class SitemapController extends SiteController
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

    public function index(Request $request)
    {
        return $this->xml('site.feed.sitemap', ['sitemap_languages' => $this->languages()]);
    }

    public function single(?string $locale = null)
    {
        $locale = $locale ?: app()->getLocale();
        app()->setLocale($locale);

        return $this->xml('site.feed.sitemap-single', [
            'urls' => app(SitemapService::class)->urls($locale),
        ]);
    }

    /**
     * Aktiv dillər - yalnız sitemap route-u qeydiyyatdan keçənlər (dil sonradan
     * əlavə olunubsa route keşi köhnə ola bilər; olmayan route-a `route()`
     * istisna atardı).
     */
    private function languages()
    {
        return Language::getCachedAll()
            ->filter(fn ($language) => Route::has("site.{$language->code}.sitemap.single"))
            ->values();
    }

    private function xml(string $view, array $data)
    {
        return response()
            ->view($view, $data)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
