<?php

namespace App\Http\Controllers\Site;

use App\Models\Navigation\Menu;
use Illuminate\Http\Request;

class StaticPageController extends SiteController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function contact(Request $request)
    {
        $menu = Menu::getByRouteName('contact');

        $this->setSchemaGraph([
            $this->schema()->webPage('ContactPage', $menu?->title, $menu?->description),
        ]);

        return view("site.pages.static.contact");
    }

    public function fallback()
    {
        // 404 səhifəsi indekslənməməlidir
        $this->setPageRobots('noindex, follow');

        return response()
            ->view('site.pages.errors.404', [], 404);
    }
}
