<?php

namespace App\Http\Controllers\Gopanel\Seo;

use App\Enums\Gopanel\Seo\RedirectMatchTypeEnum;
use App\Http\Controllers\GoPanelController;
use App\Models\Seo\SiteRedirect;
use Exception;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;

class SiteRedirectController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        return view("gopanel.pages.seo.site-redirects.index");
    }

    public function getForm(SiteRedirect $item, Request $request)
    {
        try {
            $route = route("gopanel.seo.site-redirects.save", $item);
            $this->response['html'] = View::make('gopanel.pages.seo.site-redirects.partials.form', [
                'item'          => $item,
                'route'         => $route,
                'match_types'   => RedirectMatchTypeEnum::cases()
            ])->render();
            $this->success_response([], "Form yaradıldı");
        } catch (Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }


    public function save(SiteRedirect $item, Request $request)
    {
        try {
            $data       = $request->except(['_token']);
            $message    = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";
            $item       = $this->crudHelper->saveInstance($item, $data);
            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }
}
