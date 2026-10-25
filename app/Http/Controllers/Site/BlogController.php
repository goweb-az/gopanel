<?php

namespace App\Http\Controllers\Site;

use App\Models\Navigation\Menu;
use App\Models\Site\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class BlogController extends SiteController
{


    public function __construct()
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        $list = Blog::query()
            ->where("is_active", true)
            ->latest()
            ->paginate(9)
            ->withQueryString();

        $this->setSchemaGraph([
            $this->schema()->itemList($list, $this->listTitle(), fn (Blog $blog) => $blog->single_url),
            $this->schema()->breadcrumb($this->trail()),
        ]);

        return view("site.pages.blog.index", compact("list"));
    }


    public function single(Blog $blog)
    {
        $this->check_item($blog);
        $blog->incrementViews();
        // Meta sətri yoxdursa başlıq/təsvirdən qurulur (MetaService::composeForModel)
        $this->setPageMeta($blog);
        $this->setSchemaGraph([
            $this->schema()->blogPosting($blog, $blog->single_url),
            $this->schema()->breadcrumb(array_merge($this->trail(), [
                ['name' => $blog->title, 'url' => $blog->single_url],
            ])),
        ]);
        return view("site.pages.blog.single", compact("blog"));
    }


    /** Naviqasiya yolu: ana səhifə → bloq siyahısı. */
    private function trail(): array
    {
        $locale = app()->getLocale();
        $list   = "site.{$locale}.blog.index";

        return [
            ['name' => __('Əsas səhifə'), 'url' => url($locale)],
            ['name' => $this->listTitle(), 'url' => Route::has($list) ? route($list) : null],
        ];
    }

    private function listTitle(): string
    {
        return (string) (Menu::getByRouteName('blogs')?->title ?: __('Bloq'));
    }
}
