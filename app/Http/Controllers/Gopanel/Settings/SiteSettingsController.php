<?php

namespace App\Http\Controllers\Gopanel\Settings;

use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Settings\SiteSettingsSaveRequest;
use App\Models\Settings\SiteSetting;
use App\Queries\Gopanel\Common\SingleRecordQuery;
use App\Services\Gopanel\Settings\SiteSettingsService;
use Illuminate\Http\Request;

class SiteSettingsController extends GoPanelController
{
    public function __construct(private readonly SiteSettingsService $service)
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        $item = (new SingleRecordQuery(SiteSetting::class))->currentOrNew();

        return view("gopanel.pages.settings.site_settings.index", compact("item"));
    }


    public function save(SiteSetting $item, SiteSettingsSaveRequest $request)
    {
        try {
            $message = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";

            $item = $this->service->save($item, $request->payload(), $request->fileFields());

            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }
}
