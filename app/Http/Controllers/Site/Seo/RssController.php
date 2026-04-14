<?php

namespace App\Http\Controllers\Site\Seo;

use App\Http\Controllers\Site\SiteController;
use App\Models\Site\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RssController extends SiteController
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

    // OPML feed index – bütün dillərin RSS linklərini sıralayır
    public function index(Request $request)
    {
        return response()
            ->view('site.feed.rss-index-opml')
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    // Dil bazlı RSS 2.0
    public function single(Request $request)
    {
        $locale = app()->getLocale();

        $items = Cache::remember("rss_items_{$locale}", now()->addHours(12), function () use ($locale) {
            $blogs = Blog::query()
                ->where('is_active', true)
                ->latest()
                ->take(100)
                ->get()
                ->map(function ($blog) use ($locale) {
                    $title = $blog->title ?? '[Başlıqsız]';
                    $desc  = Str::limit(strip_tags($blog->short ?? $blog->description ?? ''), 400);
                    $link  = $blog->single_url ?? null;
                    $date  = $blog->updated_at ?? $blog->created_at;

                    return [
                        'title'       => $title,
                        'description' => $desc,
                        'link'        => $link,
                        'guid'        => $link ?: ('blog-' . $blog->getKey()),
                        'pubDate'     => optional($date)->toRfc2822String(),
                    ];
                });

            return $blogs
                ->filter(fn($i) => !empty($i['link']))
                ->values();
        });

        $channel = [
            'title'       => config('app.name') . ' — ' . strtoupper($locale),
            'link'        => url($locale),
            'description' => 'Son yazılar',
            'language'    => $locale,
            'lastBuild'   => now()->toRfc2822String(),
            'self'        => route("site.{$locale}.rss.single"),
        ];

        return response()
            ->view('site.feed.rss-single', compact('channel', 'items'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
