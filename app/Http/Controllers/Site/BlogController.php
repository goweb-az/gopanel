<?php

namespace App\Http\Controllers\Site;

use App\Models\Site\Blog;
use App\Services\Site\Seo\MetaService;
use Illuminate\Http\Request;

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
        $this->setSchema("site.schema-markups.blog.list", ['schema_blogs' => $list]);
        return view("site.pages.blog.index", compact("list"));
    }


    public function single(Blog $blog)
    {
        // dd($blog->randomAttachmentCard());
        $this->check_item($blog);
        $blog->incrementViews();
        $this->meta_share($blog);
        $this->setSchema("site.schema-markups.blog.single", ['schema_blog' => $blog]);
        return view("site.pages.blog.single", compact("blog"));
    }
}
