<?php

namespace App\Http\Controllers\Gopanel\Seo;

use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Seo\SeoAnalyticsSaveRequest;
use App\Models\Seo\SeoAnalytics;
use App\Queries\Gopanel\Common\SingleRecordQuery;
use App\Repositories\BaseRepository;
use App\Services\Gopanel\Seo\SeoAnalyticsService;
use App\Services\Seo\MachineFilesService;
use Illuminate\Http\Request;

class SeoAnalyticsController extends GoPanelController
{
    public function __construct(private readonly BaseRepository $repository)
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        $item = (new SingleRecordQuery(SeoAnalytics::class))->currentOrNew();

        $fields = [
            'head'       => 'Head',
            'body'       => 'Body',
            'footer'     => 'Footer',
            'robots_txt' => 'Robots txt',
            'ai_txt'     => 'Ai txt',
            'ads_txt'    => 'Ads txt',
            'other'      => 'Digər',
        ];

        // Saytın bütün maşın faylları - admin hamısını bir yerdə görsün
        $machineFiles = app(MachineFilesService::class)->links();

        return view("gopanel.pages.seo.seo-analytics.index", compact("item", "fields", "machineFiles"));
    }


    public function save(SeoAnalytics $item, SeoAnalyticsSaveRequest $request)
    {
        try {
            $message = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";

            // Servis keşi də təmizləyir - əks halda /robots.txt köhnə mətni verirdi
            $item = app(SeoAnalyticsService::class)->save($item, $request->payload());

            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }
}
