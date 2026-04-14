<?php

namespace App\Http\Controllers\Site;


use App\Http\Controllers\Controller;
use App\Services\Site\Seo\MetaService;
use App\Services\Site\SiteService;
use Illuminate\Support\Facades\View;

class SiteController extends Controller
{
    public SiteService $siteService;

    public function __construct()
    {
        parent::__construct();
        $this->siteService          = new SiteService();
        $this->response['redirect'] = false;
        $this->setSchema(false);
    }


    public function check_item($item)
    {
        if (!is_null($item?->is_active) && $item?->is_active == 0) {
            abort(404);
        }
    }



    public function meta_share($item)
    {
        $meta = $item?->meta()?->first();
        if ($meta)
            MetaService::sharePageMeta($meta->toArray());
    }


    protected function setSchema(string|false $view = false, array $data = []): void
    {
        $schemaView = false;

        if ($view && View::exists($view))
            $schemaView = $view;
        View::share(array_merge(
            ['schema_markup_source' => $schemaView],
            $data
        ));
    }
}
