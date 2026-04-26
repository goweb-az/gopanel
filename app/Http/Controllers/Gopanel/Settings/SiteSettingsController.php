<?php

namespace App\Http\Controllers\Gopanel\Settings;

use App\Helpers\Gopanel\FileUploader;
use App\Helpers\Gopanel\Site\GoPanelSiteHelper;
use App\Helpers\Gopanel\Site\PageMetaDataHelper;
use App\Http\Controllers\GoPanelController;
use App\Models\Settings\SiteSetting;
use Exception;
use Illuminate\Http\Request;

class SiteSettingsController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        $item = SiteSetting::latest()->first() ?? new SiteSetting();
        return view("gopanel.pages.settings.site_settings.index", compact("item"));
    }



    public function save(SiteSetting $item, Request $request)
    {
        try {
            $message    = !is_null($item) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";
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

        if ($request->hasFile("logo_light")) {
            $data['logo_light']     = FileUploader::toPublic($request->file('logo_light'), 'site-logo', 'logo-light');
        }

        if ($request->hasFile("logo_dark")) {
            $data['logo_dark']      = FileUploader::toPublic($request->file('logo_dark'), 'site-logo', 'logo-dark');
        }

        if ($request->hasFile("gopanel_logo")) {
            $data['gopanel_logo']   = FileUploader::toPublic($request->file('gopanel_logo'), 'site-logo', 'gopanel_logo');
        }

        $item  = $this->crudHelper->saveInstance($item, $data);
        if (isset($item->id)) {
            $metaDataInput = $request->input('meta', []);
            $metaFiles = $request->file('meta', []);
            PageMetaDataHelper::save($item, $metaDataInput, $metaFiles);
        }
        return $item;
    }
}
