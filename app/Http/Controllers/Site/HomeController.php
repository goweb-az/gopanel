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
        // Baza qovşaqları (Organization + WebSite) - SchemaService
        $this->setSchemaGraph();
        return view("site.pages.home.index", compact("blogs"));
    }
}
