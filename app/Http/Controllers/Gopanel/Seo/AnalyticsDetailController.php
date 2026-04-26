<?php

namespace App\Http\Controllers\Gopanel\Seo;


use App\Http\Controllers\GoPanelController;
use App\Models\Analytics\AnalyticsBrowser;
use App\Models\Analytics\AnalyticsDevice;
use App\Models\Analytics\AnalyticsOperatingSystem;
use Exception;
use Illuminate\Http\Request;

class AnalyticsDetailController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function devices(Request $request)
    {
        return view('gopanel.pages.analytics.detail.devices');
    }

    public function operating_systems(Request $request)
    {
        return view('gopanel.pages.analytics.detail.operating_systems');
    }

    public function browsers(Request $request)
    {
        return view('gopanel.pages.analytics.detail.browsers');
    }

    public function countries(Request $request)
    {
        return view('gopanel.pages.analytics.detail.countries');
    }

    public function cities(Request $request)
    {
        return view('gopanel.pages.analytics.detail.cities');
    }

    public function languages(Request $request)
    {
        return view('gopanel.pages.analytics.detail.languages');
    }

    public function clicks(Request $request)
    {
        $devices        = AnalyticsDevice::all();
        $operations     = AnalyticsOperatingSystem::all();
        $browsers       = AnalyticsBrowser::all();
        return view('gopanel.pages.analytics.detail.clicks', compact("devices", 'operations', "browsers"));
    }

    public function links(Request $request)
    {
        return view('gopanel.pages.analytics.detail.links');
    }

    public function utm_parameters(Request $request)
    {
        return view('gopanel.pages.analytics.detail.utm_parameters');
    }

    public function ad_platforms(Request $request)
    {
        return view('gopanel.pages.analytics.detail.ad_platforms');
    }

    public function ad_platform_data(Request $request)
    {
        return view('gopanel.pages.analytics.detail.ad_platform_data');
    }
}
