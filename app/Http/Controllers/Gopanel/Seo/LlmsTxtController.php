<?php

namespace App\Http\Controllers\Gopanel\Seo;

use App\Http\Controllers\GoPanelController;
use App\Models\Seo\LlmsTxt;
use Illuminate\Http\Request;

class LlmsTxtController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $item = LlmsTxt::latest()->first() ?? new LlmsTxt();
        return view("gopanel.pages.seo.llms-txt.index", compact("item"));
    }

    public function save(LlmsTxt $item, Request $request)
    {
        try {
            $message = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";
            $data = $request->except(['_token']);
            $item = $this->crudHelper->saveInstance($item, $data);
            $this->success_response($item, $message);
            \Illuminate\Support\Facades\Cache::forget('llms_txt');
        } catch (\Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
    }
}
