<?php

namespace App\Http\Controllers\Gopanel\Seo;

use App\Enums\Gopanel\Seo\RedirectMatchTypeEnum;
use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Seo\SiteRedirectSaveRequest;
use App\Models\Seo\SiteRedirect;
use App\Repositories\BaseRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class SiteRedirectController extends GoPanelController
{
    public function __construct(private readonly BaseRepository $repository)
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


    public function save(SiteRedirect $item, SiteRedirectSaveRequest $request)
    {
        try {
            $message = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";

            $item = $this->repository->save($item, $request->payload()->attributes);

            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }
}
