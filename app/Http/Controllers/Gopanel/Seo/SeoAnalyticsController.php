<?php

namespace App\Http\Controllers\Gopanel\Seo;


use App\Http\Controllers\GoPanelController;
use App\Models\Seo\SeoAnalytics;
use Exception;
use Illuminate\Http\Request;

class SeoAnalyticsController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        $item = SeoAnalytics::latest()->first() ?? new SeoAnalytics();
        $fields = [
            'head'       => 'Head',
            'body'       => 'Body',
            'footer'     => 'Footer',
            'robots_txt' => 'Robots txt',
            'ai_txt'     => 'Ai txt',
            'other'      => 'Digər',
        ];
        return view("gopanel.pages.seo.seo-analytics.index", compact("item", "fields"));
    }



    public function save(SeoAnalytics $item, Request $request)
    {
        try {
            $message    = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";
            $this->saveData($item, $request);
            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }


    private function saveData($item, $request)
    {
        $data       = $request->except(['_token']);
        $item  = $this->crudHelper->saveInstance($item, $data);
        return $item;
    }
}
