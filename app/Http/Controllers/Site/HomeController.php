<?php

namespace App\Http\Controllers\Site;

use Illuminate\Http\Request;

class HomeController extends SiteController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $blogs = \App\Models\Site\Blog::getCachedAll();
        $this->setSchema("site.schema-markups.home");
        return view("site.pages.home.index", compact("blogs"));
    }
}
