<?php

namespace App\Http\Controllers\Site;

use Illuminate\Http\Request;

class StaticPageController extends SiteController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function contact(Request $request)
    {
        $this->setSchema("site.schema-markups.contact");
        return view("site.pages.static.contact");
    }

    public function fallback()
    {
        return response()
            ->view('site.pages.errors.404', [], 404);
    }
}
