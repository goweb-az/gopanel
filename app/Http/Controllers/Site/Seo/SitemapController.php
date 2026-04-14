<?php

namespace App\Http\Controllers\Site\Seo;

use App\Http\Controllers\Site\SiteController;
use App\Models\Navigation\Menu;
use App\Models\Site\Blog;
use Illuminate\Http\Request;

class SitemapController extends SiteController
{

    public function __construct()
    {
        parent::__construct();
        $this->setLocale();
    }

    private function setLocale(): void
    {
        if (request()->segment(1)) {
            app()->setLocale(request()->segment(1));
        }
    }

    public function index(Request $request)
    {
        return response()
            ->view('site.feed.sitemap')
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function single()
    {
        $sitemap_menus = Menu::getRoutes(app()->getLocale());

        $blogs = Blog::query()
            ->where('is_active', true)
            ->latest()
            ->get();

        return response()
            ->view('site.feed.sitemap-single', compact(
                'sitemap_menus',
                'blogs',
            ))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
